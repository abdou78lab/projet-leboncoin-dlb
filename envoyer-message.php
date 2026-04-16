<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = "Vous devez être connecté.";
    header('Location: auth.php?mode=connexion');
    exit;
}

$fromUserId = (int) $_SESSION['user_id'];
$annonceId = isset($_POST['annonce_id']) ? (int) $_POST['annonce_id'] : 0;
$toUserId = isset($_POST['to_user_id']) ? (int) $_POST['to_user_id'] : 0;
$contenu = trim($_POST['contenu'] ?? '');

if ($annonceId <= 0 || $toUserId <= 0 || $contenu === '') {
    $_SESSION['flash_error'] = "Message invalide.";
    header('Location: messagerie.php');
    exit;
}

if ($fromUserId === $toUserId) {
    $_SESSION['flash_error'] = "Vous ne pouvez pas vous envoyer un message à vous-même.";
    header('Location: messagerie.php');
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO messages (annonce_id, sender_id, receiver_id, contenu, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->execute([$annonceId, $fromUserId, $toUserId, $contenu]);

$_SESSION['flash_success'] = "Message envoyé.";
header('Location: messagerie.php?annonce_id=' . $annonceId . '&user_id=' . $toUserId);
exit;