<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_WEB', 'uploads');

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_or_fail(string $fallback = 'index.php'): void {
    $posted = $_POST['csrf_token'] ?? '';
    $session = $_SESSION['csrf_token'] ?? '';
    if (!$posted || !$session || !hash_equals($session, $posted)) {
        $_SESSION['flash_error'] = 'Erreur de sécurité : formulaire invalide ou expiré.';
        redirect($fallback);
    }
}

function set_flash(string $key, string $message): void {
    $_SESSION[$key] = $message;
}

function flash(string $key): ?string {
    if (!isset($_SESSION[$key])) return null;
    $msg = $_SESSION[$key];
    unset($_SESSION[$key]);
    return is_string($msg) ? $msg : null;
}

function pull_array(string $key): array {
    if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) return [];
    $v = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $v;
}

function old(array $source, string $key, string $default = ''): string {
    return isset($source[$key]) ? (string)$source[$key] : $default;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login(): void {
    if (!is_logged_in()) {
        set_flash('flash_error', 'Vous devez être connecté pour accéder à cette page.');
        redirect('auth.php?mode=connexion');
    }
}

function require_admin(): void {
    if (!is_logged_in() || !is_admin()) {
        set_flash('flash_error', 'Accès réservé aux administrateurs.');
        redirect('auth.php?mode=connexion');
    }
}

function current_user(PDO $pdo): ?array {
    if (!is_logged_in()) return null;
    static $cached = null;
    if ($cached !== null) return $cached;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $cached = $stmt->fetch() ?: null;
    return $cached;
}

function unread_count(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function image_url(?string $file): string {
    if (!$file) return 'https://via.placeholder.com/800x600?text=Sans+image';
    return UPLOAD_WEB . '/' . rawurlencode($file);
}

function state_label(string $etat): string {
    return match ($etat) {
        'neuf' => 'Neuf',
        'bon' => 'Bon état',
        'correct' => 'État correct',
        default => ucfirst($etat),
    };
}

function handle_uploaded_image(array $file, ?string $oldFile = null): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $oldFile;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erreur lors de l’envoi de l’image.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format image non autorisé.');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }

    $filename = uniqid('annonce_', true) . '.' . $allowed[$mime];
    $dest = UPLOAD_DIR . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Impossible d’enregistrer l’image.');
    }

    if ($oldFile && is_file(UPLOAD_DIR . '/' . $oldFile)) {
        @unlink(UPLOAD_DIR . '/' . $oldFile);
    }

    return $filename;
}

function delete_image_file(?string $file): void {
    if ($file && is_file(UPLOAD_DIR . '/' . $file)) {
        @unlink(UPLOAD_DIR . '/' . $file);
    }
}

function format_price($price): string {
    return number_format((float)$price, 0, ',', ' ') . ' €';
}

function render_navbar(string $search = ''): void {
    global $pdo;
    $user = current_user($pdo);
    $nbUnread = $user ? unread_count($pdo, (int)$user['id']) : 0;
    include __DIR__ . '/navbar.php';
}
