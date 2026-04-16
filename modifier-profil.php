<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('profil.php');
verify_csrf_or_fail('profil.php');
$user = current_user($pdo);
$pseudo = trim((string)($_POST['pseudo'] ?? ''));
$nom = trim((string)($_POST['nom'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$current = (string)($_POST['password_current'] ?? '');
$new = (string)($_POST['password_new'] ?? '');
$confirm = (string)($_POST['password_confirm'] ?? '');
if ($pseudo === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('flash_error', 'Pseudo et adresse e-mail valides sont obligatoires.');
    redirect('profil.php');
}
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
$stmt->execute([$email, (int)$user['id']]);
if ($stmt->fetch()) {
    set_flash('flash_error', 'Cette adresse e-mail est déjà utilisée.');
    redirect('profil.php');
}
try {
    if ($new !== '' || $confirm !== '' || $current !== '') {
        if (!password_verify($current, $user['password'])) {
            throw new RuntimeException('Le mot de passe actuel est incorrect.');
        }
        if (strlen($new) < 8) {
            throw new RuntimeException('Le nouveau mot de passe doit contenir au moins 8 caractères.');
        }
        if ($new !== $confirm) {
            throw new RuntimeException('La confirmation du nouveau mot de passe est incorrecte.');
        }
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET pseudo = ?, nom = ?, email = ?, password = ? WHERE id = ?');
        $stmt->execute([$pseudo, $nom !== '' ? $nom : null, $email, $hash, (int)$user['id']]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET pseudo = ?, nom = ?, email = ? WHERE id = ?');
        $stmt->execute([$pseudo, $nom !== '' ? $nom : null, $email, (int)$user['id']]);
    }
    $_SESSION['pseudo'] = $pseudo;
    set_flash('flash_success', 'Profil mis à jour avec succès.');
} catch (Throwable $e) {
    set_flash('flash_error', $e->getMessage());
}
redirect('profil.php');
