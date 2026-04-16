<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
$user = current_user($pdo);
$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE u.pseudo LIKE ? OR u.nom LIKE ? OR u.email LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
}
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users u $where");
$stmt->execute($params);
$nbUsersFiltered = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($nbUsersFiltered / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

$stats = [
    'nb_users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'nb_annonces' => (int)$pdo->query('SELECT COUNT(*) FROM annonces')->fetchColumn(),
    'nb_messages' => (int)$pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn(),
    'nb_desactives' => (int)$pdo->query('SELECT COUNT(*) FROM users WHERE actif = 0')->fetchColumn(),
];

$sql = "SELECT u.*, COUNT(a.id) AS nb_annonces FROM users u LEFT JOIN annonces a ON a.user_id = u.id $where GROUP BY u.id ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $i => $value) { $stmt->bindValue($i + 1, $value, PDO::PARAM_STR); }
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$utilisateurs = $stmt->fetchAll();

$latestAds = $pdo->query('SELECT a.id, a.titre, a.prix, a.etat, a.created_at, u.pseudo FROM annonces a JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 8')->fetchAll();
$messageAdmin = flash('message_admin');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Administration – DansLeBueno</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
<nav class="navbar navbar-danslebueno fixed-top">
  <div class="container-fluid px-3 px-lg-4 d-flex align-items-center">
    <a href="index.php" class="navbar-brand me-3"><span class="logo-text"><span class="logo-dans">dans</span><span class="logo-le">le</span><span class="logo-bueno">bueno</span></span></a>
    <span class="badge rounded-pill ms-1" style="background:var(--color-orange);font-size:.75rem;padding:.3rem .7rem;"><i class="bi bi-shield-lock me-1"></i>Admin</span>
    <div class="ms-auto d-flex align-items-center gap-3"><a href="index.php" class="text-muted" style="font-size:.85rem;"><i class="bi bi-box-arrow-left me-1"></i>Retour au site</a><a href="profil.php" class="nav-icon-link"><i class="bi bi-person-circle"></i><span><?= h($user['pseudo']) ?> (Admin)</span></a></div>
  </div>
</nav>
<div class="d-flex">
  <aside class="admin-sidebar d-none d-md-block" style="width:220px;flex-shrink:0;">
    <div class="admin-brand"><i class="bi bi-speedometer2 me-2"></i>Dashboard Admin</div>
    <nav>
      <a href="#section-stats" class="admin-nav-link active"><i class="bi bi-bar-chart-line"></i>Vue d'ensemble</a>
      <a href="#section-users" class="admin-nav-link"><i class="bi bi-people"></i>Utilisateurs</a>
      <a href="#section-annonces" class="admin-nav-link"><i class="bi bi-megaphone"></i>Annonces</a>
      <div style="border-top:1px solid rgba(255,255,255,.08);margin:1rem 1.3rem;"></div>
      <a href="index.php" class="admin-nav-link"><i class="bi bi-house"></i>Site public</a>
    </nav>
  </aside>
  <main class="admin-main flex-grow-1">
    <?php if ($messageAdmin): ?><div class="alert alert-success"><?= h($messageAdmin) ?></div><?php endif; ?>
    <section id="section-stats" class="mb-5">
      <h2 class="section-title mb-3">Vue d'ensemble</h2>
      <div class="row g-3">
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon sky"><i class="bi bi-people-fill"></i></div><div><div class="stat-value"><?= $stats['nb_users'] ?></div><div class="stat-label">Utilisateurs</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon orange"><i class="bi bi-megaphone-fill"></i></div><div><div class="stat-value"><?= $stats['nb_annonces'] ?></div><div class="stat-label">Annonces</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon green"><i class="bi bi-chat-fill"></i></div><div><div class="stat-value"><?= $stats['nb_messages'] ?></div><div class="stat-label">Messages</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon red"><i class="bi bi-person-x-fill"></i></div><div><div class="stat-value"><?= $stats['nb_desactives'] ?></div><div class="stat-label">Désactivés</div></div></div></div>
      </div>
    </section>

    <section id="section-users" class="mb-5">
      <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2"><h2 class="section-title mb-0">Gestion des utilisateurs</h2>
        <form method="GET" style="max-width:260px;width:100%;"><input type="search" class="form-control form-control-sm rounded-pill" placeholder="Rechercher un utilisateur…" name="q" value="<?= h($search) ?>"></form>
      </div>
      <div class="bg-white rounded-3 shadow-sm overflow-hidden">
        <div class="table-responsive">
          <table class="table table-admin mb-0"><thead><tr><th>#</th><th>Utilisateur</th><th>E-mail</th><th>Inscription</th><th>Annonces</th><th>Statut</th><th class="text-center">Actions</th></tr></thead><tbody>
          <?php foreach ($utilisateurs as $index => $u): $isSelf = (int)$u['id'] === (int)$user['id']; ?>
            <tr class="<?= (int)$u['actif'] === 0 ? 'table-light' : '' ?>">
              <td class="text-muted" style="font-size:.8rem;"><?= $offset + $index + 1 ?></td>
              <td><div class="d-flex align-items-center gap-2"><div class="conv-avatar" style="width:32px;height:32px;font-size:.8rem;"><?= h(strtoupper(substr((string)($u['pseudo'] ?: 'U'),0,1))) ?></div><div><div class="fw-bold" style="font-size:.88rem;<?= (int)$u['actif'] === 0 ? 'text-decoration:line-through;color:var(--color-neutral-500);' : '' ?>"><?= h($u['pseudo']) ?></div></div></div></td>
              <td style="font-size:.85rem;"><?= h($u['email']) ?></td>
              <td style="font-size:.82rem;color:var(--color-neutral-700);"><?= date('d/m/Y', strtotime((string)$u['created_at'])) ?></td>
              <td class="text-center"><?= (int)$u['nb_annonces'] ?></td>
              <td><?php if ($u['role'] === 'admin'): ?><span class="badge-user-status admin">Admin</span><?php elseif ((int)$u['actif'] === 0): ?><span class="badge-user-status desactive">Désactivé</span><?php else: ?><span class="badge-user-status actif">Actif</span><?php endif; ?></td>
              <td><div class="d-flex justify-content-center gap-1 flex-wrap">
                <?php if ($isSelf): ?><span class="text-muted" style="font-size:.78rem;">(vous)</span><?php else: ?>
                  <?php if ($u['role'] !== 'admin'): ?><form action="admin-action.php" method="POST"><input type="hidden" name="action" value="promouvoir"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button class="btn btn-outline-warning btn-sm rounded-pill" type="submit"><i class="bi bi-shield-plus"></i></button></form><?php endif; ?>
                  <form action="admin-action.php" method="POST"><input type="hidden" name="action" value="<?= (int)$u['actif'] === 1 ? 'desactiver' : 'reactiver' ?>"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button class="btn <?= (int)$u['actif'] === 1 ? 'btn-outline-secondary' : 'btn-outline-success' ?> btn-sm rounded-pill" type="submit"><i class="bi <?= (int)$u['actif'] === 1 ? 'bi-person-dash' : 'bi-person-check' ?>"></i></button></form>
                  <form action="admin-action.php" method="POST" onsubmit="return confirm('Supprimer définitivement cet utilisateur ?');"><input type="hidden" name="action" value="supprimer"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button class="btn btn-outline-danger btn-sm rounded-pill" type="submit"><i class="bi bi-trash"></i></button></form>
                <?php endif; ?>
              </div></td>
            </tr>
          <?php endforeach; ?>
          </tbody></table>
        </div>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top" style="font-size:.8rem;color:var(--color-neutral-500);"><span>Affichage de <?= $nbUsersFiltered ? $offset + 1 : 0 ?> à <?= min($offset + $perPage, $nbUsersFiltered) ?> sur <?= $nbUsersFiltered ?> utilisateurs</span>
          <nav><ul class="pagination pagination-sm mb-0">
            <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>"><i class="bi bi-chevron-left"></i></a></li><?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?><li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $p ?>&q=<?= urlencode($search) ?>"><?= $p ?></a></li><?php endfor; ?>
            <?php if ($page < $totalPages): ?><li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>"><i class="bi bi-chevron-right"></i></a></li><?php endif; ?>
          </ul></nav>
        </div>
      </div>
    </section>

    <section id="section-annonces">
      <h2 class="section-title mb-3">Dernières annonces publiées</h2>
      <div class="bg-white rounded-4 shadow-sm p-3 p-lg-4">
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Titre</th><th>Vendeur</th><th>État</th><th>Prix</th><th>Date</th><th></th></tr></thead><tbody>
        <?php foreach ($latestAds as $ad): ?>
          <tr><td><?= h($ad['titre']) ?></td><td><?= h($ad['pseudo']) ?></td><td><span class="badge-etat <?= h($ad['etat']) ?>"><?= h(state_label($ad['etat'])) ?></span></td><td><?= h(format_price($ad['prix'])) ?></td><td><?= date('d/m/Y', strtotime((string)$ad['created_at'])) ?></td><td><a href="detail-annonce.php?id=<?= (int)$ad['id'] ?>" class="btn btn-outline-secondary btn-sm">Voir</a></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
      </div>
    </section>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
