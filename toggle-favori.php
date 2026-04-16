<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
verify_csrf_or_fail('index.php');
$annonceId = isset($_POST['annonce_id']) ? (int)$_POST['annonce_id'] : 0;
if ($annonceId <= 0) { set_flash('flash_error', 'Annonce invalide.'); redirect('index.php'); }
$stmt = $pdo->prepare('SELECT 1 FROM favoris WHERE user_id = ? AND annonce_id = ?');
$stmt->execute([(int)$_SESSION['user_id'], $annonceId]);
if ($stmt->fetchColumn()) {
    $stmt = $pdo->prepare('DELETE FROM favoris WHERE user_id = ? AND annonce_id = ?');
    $stmt->execute([(int)$_SESSION['user_id'], $annonceId]);
    set_flash('flash_success', 'Annonce retirée de vos favoris.');
} else {
    $stmt = $pdo->prepare('INSERT INTO favoris (user_id, annonce_id) VALUES (?, ?)');
    $stmt->execute([(int)$_SESSION['user_id'], $annonceId]);
    set_flash('flash_success', 'Annonce ajoutée à vos favoris.');
}
redirect('detail-annonce.php?id=' . $annonceId);
