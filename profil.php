<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$user = current_user($pdo);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM annonces WHERE user_id = ?');
$stmt->execute([(int)$user['id']]);
$nbAnnonces = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM favoris WHERE user_id = ?');
$stmt->execute([(int)$user['id']]);
$nbFavoris = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT * FROM annonces WHERE user_id = ? ORDER BY created_at DESC, id DESC');
$stmt->execute([(int)$user['id']]);
$mesAnnonces = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT a.*, u.pseudo FROM favoris f JOIN annonces a ON a.id = f.annonce_id JOIN users u ON u.id = a.user_id WHERE f.user_id = ? ORDER BY f.created_at DESC');
$stmt->execute([(int)$user['id']]);
$mesFavoris = $stmt->fetchAll();

$flashSuccess = flash('flash_success');
$flashError = flash('flash_error');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mon profil – DansLeBueno</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
<?php render_navbar(); ?>
<section class="page-hero"><div class="container-xl"><h1 class="mb-0">Mon profil</h1></div></section>
<div class="container-xl pb-5">
  <?php if ($flashSuccess): ?><div class="alert alert-success"><?= h($flashSuccess) ?></div><?php endif; ?>
  <?php if ($flashError): ?><div class="alert alert-danger"><?= h($flashError) ?></div><?php endif; ?>

  <div class="profile-header-card bg-white rounded-4 shadow-sm p-4 d-flex gap-3 align-items-center mb-4">
    <div class="profile-avatar-lg"><?= h(strtoupper(substr((string)$user['pseudo'],0,1))) ?></div>
    <div class="profile-name-block flex-grow-1">
      <div class="user-name"><?= h($user['pseudo']) ?></div>
      <div class="user-email"><?= h($user['email']) ?></div>
      <div class="text-muted mt-1" style="font-size:.82rem;">Membre depuis <?= date('F Y', strtotime((string)$user['created_at'])) ?></div>
      <div class="d-flex gap-3 mt-2"><span><strong><?= $nbAnnonces ?></strong> annonces</span><span><strong><?= $nbFavoris ?></strong> favoris</span></div>
    </div>
  </div>

  <ul class="nav nav-tabs-custom nav-tabs mb-4" id="profileTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#panel-infos" type="button">Mes informations</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#panel-annonces" type="button">Mes annonces <span class="badge bg-secondary ms-1 rounded-pill"><?= $nbAnnonces ?></span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#panel-favoris" type="button">Mes favoris <span class="badge bg-secondary ms-1 rounded-pill"><?= $nbFavoris ?></span></button></li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="panel-infos">
      <div class="row"><div class="col-12 col-lg-7">
        <div class="bg-white rounded-4 shadow-sm p-4">
          <h2 class="section-title mb-4">Modifier mes informations</h2>
          <form action="modifier-profil.php" method="POST">
            <div class="mb-3"><label class="form-label">Pseudo</label><input type="text" class="form-control" name="pseudo" value="<?= h($user['pseudo']) ?>" required maxlength="60"></div>
            <div class="mb-3"><label class="form-label">Nom</label><input type="text" class="form-control" name="nom" value="<?= h((string)($user['nom'] ?? '')) ?>" maxlength="80"></div>
            <div class="mb-3"><label class="form-label">Adresse e-mail</label><input type="email" class="form-control" name="email" value="<?= h($user['email']) ?>" required></div>
            <hr class="my-4">
            <h6 class="fw-bold mb-3">Changer le mot de passe</h6>
            <div class="mb-3"><label class="form-label">Mot de passe actuel</label><input type="password" class="form-control" name="password_current"></div>
            <div class="mb-3"><label class="form-label">Nouveau mot de passe</label><input type="password" class="form-control" name="password_new" minlength="8"></div>
            <div class="mb-4"><label class="form-label">Confirmer le nouveau mot de passe</label><input type="password" class="form-control" name="password_confirm"></div>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <button type="submit" class="btn btn-primary-orange">Enregistrer</button>
          </form>
        </div>
      </div></div>
    </div>

    <div class="tab-pane fade" id="panel-annonces">
      <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="section-title mb-0">Mes annonces</h2><a href="deposer-annonce.php" class="btn btn-primary-orange btn-sm rounded-pill"><i class="bi bi-plus-lg me-1"></i>Nouvelle annonce</a></div>
      <div class="row g-4" id="mes-annonces">
        <?php if (!$mesAnnonces): ?><div class="col-12"><div class="bg-white rounded-4 shadow-sm p-5 text-center text-muted">Vous n’avez encore publié aucune annonce.</div></div><?php endif; ?>
        <?php foreach ($mesAnnonces as $a): ?>
          <div class="col-12 col-md-6 col-xl-4">
            <div class="card annonce-card h-100">
              <?php if (!empty($a['image'])): ?><img src="<?= h(image_url($a['image'])) ?>" class="card-img-top" alt="<?= h($a['titre']) ?>"><?php else: ?><div class="img-placeholder"><i class="bi bi-image"></i></div><?php endif; ?>
              <div class="card-body d-flex flex-column">
                <span class="badge-etat <?= h($a['etat']) ?> mb-2"><?= h(state_label($a['etat'])) ?></span>
                <h3 class="card-title"><?= h($a['titre']) ?></h3>
                <div class="annonce-price"><?= h(format_price($a['prix'])) ?></div>
                <div class="text-muted mt-1" style="font-size:.82rem;"><?= date('d/m/Y', strtotime((string)$a['created_at'])) ?></div>
                <div class="d-flex gap-2 mt-auto pt-3">
                  <a href="detail-annonce.php?id=<?= (int)$a['id'] ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">Voir</a>
                  <a href="deposer-annonce.php?id=<?= (int)$a['id'] ?>" class="btn btn-primary-orange btn-sm flex-grow-1">Modifier</a>
                </div>
                <form action="supprimer-annonce.php" method="POST" class="mt-2" onsubmit="return confirm('Supprimer cette annonce ?');">
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                  <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                  <button class="btn btn-outline-danger btn-sm w-100" type="submit">Supprimer</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="tab-pane fade" id="panel-favoris">
      <div class="row g-4" id="mes-favoris">
        <?php if (!$mesFavoris): ?><div class="col-12"><div class="bg-white rounded-4 shadow-sm p-5 text-center text-muted">Aucun favori pour le moment.</div></div><?php endif; ?>
        <?php foreach ($mesFavoris as $a): ?>
          <div class="col-12 col-md-6 col-xl-4">
            <div class="card annonce-card h-100">
              <?php if (!empty($a['image'])): ?><img src="<?= h(image_url($a['image'])) ?>" class="card-img-top" alt="<?= h($a['titre']) ?>"><?php else: ?><div class="img-placeholder"><i class="bi bi-image"></i></div><?php endif; ?>
              <div class="card-body d-flex flex-column">
                <span class="badge-etat <?= h($a['etat']) ?> mb-2"><?= h(state_label($a['etat'])) ?></span>
                <h3 class="card-title"><?= h($a['titre']) ?></h3>
                <div class="annonce-price"><?= h(format_price($a['prix'])) ?></div>
                <div class="text-muted mt-1" style="font-size:.82rem;">Vendeur : <?= h($a['pseudo']) ?></div>
                <div class="d-flex gap-2 mt-auto pt-3">
                  <a href="detail-annonce.php?id=<?= (int)$a['id'] ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">Voir</a>
                  <form action="toggle-favori.php" method="POST" class="flex-grow-1">
                    <input type="hidden" name="annonce_id" value="<?= (int)$a['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <button class="btn btn-outline-danger btn-sm w-100" type="submit">Retirer</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
