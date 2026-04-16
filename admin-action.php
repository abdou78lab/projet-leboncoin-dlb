<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin.php');
verify_csrf_or_fail('admin.php');
$action = trim((string)($_POST['action'] ?? ''));
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$currentAdminId = (int)$_SESSION['user_id'];
if ($userId <= 0) { set_flash('message_admin', 'Utilisateur invalide.'); redirect('admin.php'); }
$stmt = $pdo->prepare('SELECT id, role, actif FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) { set_flash('message_admin', 'Utilisateur introuvable.'); redirect('admin.php'); }
if ($userId === $currentAdminId && in_array($action, ['desactiver', 'supprimer'], true)) { set_flash('message_admin', 'Vous ne pouvez pas vous désactiver ou vous supprimer vous-même.'); redirect('admin.php'); }
try {
    switch ($action) {
        case 'promouvoir':
            if ($user['role'] !== 'admin') {
                $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
                $stmt->execute([$userId]);
                set_flash('message_admin', 'Utilisateur promu administrateur.');
            }
            break;
        case 'desactiver':
            $stmt = $pdo->prepare('UPDATE users SET actif = 0 WHERE id = ?');
            $stmt->execute([$userId]);
            set_flash('message_admin', 'Compte désactivé.');
            break;
        case 'reactiver':
            $stmt = $pdo->prepare('UPDATE users SET actif = 1 WHERE id = ?');
            $stmt->execute([$userId]);
            set_flash('message_admin', 'Compte réactivé.');
            break;
        case 'supprimer':
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $pdo->commit();
            set_flash('message_admin', 'Utilisateur supprimé.');
            break;
        default:
            set_flash('message_admin', 'Action inconnue.');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    set_flash('message_admin', 'Erreur : ' . $e->getMessage());
}
redirect('admin.php');
