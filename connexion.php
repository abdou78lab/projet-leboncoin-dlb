<?php
require_once __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('auth.php?mode=connexion');
verify_csrf_or_fail('auth.php?mode=connexion');
$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$_SESSION['auth_old_login'] = ['email' => $email];
$_SESSION['auth_active_tab'] = 'connexion';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    $_SESSION['auth_error_login'] = 'Veuillez saisir un e-mail valide et votre mot de passe.';
    redirect('auth.php?mode=connexion');
}
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['auth_error_login'] = 'Identifiants incorrects.';
    redirect('auth.php?mode=connexion');
}
if ((int)$user['actif'] !== 1) {
    $_SESSION['auth_error_login'] = 'Votre compte a été désactivé.';
    redirect('auth.php?mode=connexion');
}
unset($_SESSION['auth_old_login'], $_SESSION['auth_active_tab']);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['pseudo'] = $user['pseudo'];
set_flash('flash_success', 'Connexion réussie.');
redirect($user['role'] === 'admin' ? 'admin.php' : 'index.php');
