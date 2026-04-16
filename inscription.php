<?php
require_once __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('auth.php?mode=inscription');
verify_csrf_or_fail('auth.php?mode=inscription');
$pseudo = trim((string)($_POST['pseudo'] ?? ''));
$nom = trim((string)($_POST['nom'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$passwordConfirm = (string)($_POST['password_confirm'] ?? '');
$cgu = isset($_POST['cgu']);
$_SESSION['auth_old_register'] = ['pseudo' => $pseudo, 'nom' => $nom, 'email' => $email];
$_SESSION['auth_active_tab'] = 'inscription';
if ($pseudo === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    $_SESSION['auth_error_register'] = 'Merci de remplir correctement tous les champs obligatoires.';
    redirect('auth.php?mode=inscription');
}
if ($password !== $passwordConfirm) {
    $_SESSION['auth_error_register'] = 'Les mots de passe ne correspondent pas.';
    redirect('auth.php?mode=inscription');
}
if (!$cgu) {
    $_SESSION['auth_error_register'] = 'Vous devez accepter les conditions d’utilisation.';
    redirect('auth.php?mode=inscription');
}
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $_SESSION['auth_error_register'] = 'Cette adresse e-mail est déjà utilisée.';
    redirect('auth.php?mode=inscription');
}
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (pseudo, nom, email, password, role, actif) VALUES (?, ?, ?, ?, "user", 1)');
$stmt->execute([$pseudo, $nom !== '' ? $nom : null, $email, $hash]);
unset($_SESSION['auth_old_register'], $_SESSION['auth_active_tab']);
set_flash('flash_success', 'Compte créé avec succès. Vous pouvez maintenant vous connecter.');
redirect('auth.php?mode=connexion');
