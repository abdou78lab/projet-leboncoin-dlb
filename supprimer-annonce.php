<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
verify_csrf_or_fail('index.php');
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM annonces WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$annonce = $stmt->fetch();
if (!$annonce) { set_flash('flash_error', 'Annonce introuvable.'); redirect('index.php'); }
if ((int)$annonce['user_id'] !== (int)$_SESSION['user_id'] && !is_admin()) {
    set_flash('flash_error', 'Action non autorisée.');
    redirect('detail-annonce.php?id=' . $id);
}
delete_image_file($annonce['image'] ?? null);
$stmt = $pdo->prepare('DELETE FROM annonces WHERE id = ?');
$stmt->execute([$id]);
set_flash('flash_success', 'Annonce supprimée.');
redirect('profil.php#mes-annonces');
