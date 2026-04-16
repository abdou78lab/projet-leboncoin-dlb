<?php
require_once __DIR__ . '/includes/bootstrap.php';

$q = trim((string)($_GET['q'] ?? ''));
$prixMax = trim((string)($_GET['prix_max'] ?? ''));
$etats = $_GET['etat'] ?? [];
$allowedEtats = ['neuf', 'bon', 'correct'];
$etats = array_values(array_intersect($allowedEtats, is_array($etats) ? $etats : []));

$sql = "SELECT a.*, u.pseudo FROM annonces a JOIN users u ON u.id = a.user_id WHERE 1=1";
$params = [];
if ($q !== '') {
    $sql .= " AND (a.titre LIKE ? OR a.description LIKE ? OR u.pseudo LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($prixMax !== '' && is_numeric($prixMax)) {
    $sql .= " AND a.prix <= ?";
    $params[] = (float)$prixMax;
}
if ($etats) {
    $placeholders = implode(',', array_fill(0, count($etats), '?'));
    $sql .= " AND a.etat IN ($placeholders)";
    array_push($params, ...$etats);
}
$sql .= " ORDER BY a.created_at DESC, a.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$annonces = $stmt->fetchAll();

$flashSuccess = flash('flash_success');
$flashError = flash('flash_error');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dernières annonces – DansLeBueno</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
<?php render_navbar($q); ?>

<section class="page-hero">
  <div class="container-xl">
    <h1 class="mb-0">Dernières annonces</h1>
    <div class="text-muted mt-2" style="font-size:.9rem;"><?= count($annonces) ?> annonce(s) trouvée(s)</div>
  </div>
</section>

<div class="container-xl pb-5">
  <?php if ($flashSuccess): ?><div class="alert alert-success"><?= h($flashSuccess) ?></div><?php endif; ?>
  <?php if ($flashError): ?><div class="alert alert-danger"><?= h($flashError) ?></div><?php endif; ?>

  <div class="row g-4">
    <aside class="col-12 col-lg-3">
      <div class="form-card bg-white rounded-4 shadow-sm p-4">
        <h2 class="section-title mb-3">Filtres</h2>
        <form method="GET" action="index.php" class="d-grid gap-3">
          <div>
            <label class="form-label">Recherche</label>
            <input type="search" class="form-control" name="q" value="<?= h($q) ?>" placeholder="Ex : iPhone, vélo...">
          </div>
          <div>
            <label class="form-label">État</label>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="etat[]" value="neuf" id="f-neuf" <?= in_array('neuf', $etats, true) ? 'checked' : '' ?>><label class="form-check-label" for="f-neuf">Neuf</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="etat[]" value="bon" id="f-bon" <?= in_array('bon', $etats, true) ? 'checked' : '' ?>><label class="form-check-label" for="f-bon">Bon état</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="etat[]" value="correct" id="f-correct" <?= in_array('correct', $etats, true) ? 'checked' : '' ?>><label class="form-check-label" for="f-correct">État correct</label></div>
          </div>
          <div>
            <label class="form-label">Prix maximum</label>
            <input type="number" class="form-control" name="prix_max" min="0" value="<?= h($prixMax) ?>" placeholder="Ex : 500">
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-orange flex-grow-1">Appliquer</button>
            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>
    </aside>

    <section class="col-12 col-lg-9">
      <div class="row g-4">
        <?php if (!$annonces): ?>
          <div class="col-12">
            <div class="bg-white rounded-4 shadow-sm p-5 text-center text-muted">
              <i class="bi bi-search" style="font-size:2rem;"></i>
              <p class="mt-3 mb-0">Aucune annonce ne correspond à votre recherche.</p>
            </div>
          </div>
        <?php endif; ?>

        <?php foreach ($annonces as $a): ?>
          <div class="col-12 col-md-6 col-xl-4">
            <a href="detail-annonce.php?id=<?= (int)$a['id'] ?>" class="card annonce-card h-100">
              <?php if (!empty($a['image'])): ?>
                <img src="<?= h(image_url($a['image'])) ?>" class="card-img-top" alt="<?= h($a['titre']) ?>">
              <?php else: ?>
                <div class="img-placeholder"><i class="bi bi-image"></i></div>
              <?php endif; ?>
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <span class="badge-etat <?= h($a['etat']) ?>"><?= h(state_label($a['etat'])) ?></span>
                  <span class="text-muted" style="font-size:.75rem;"><?= date('d/m/Y', strtotime((string)$a['created_at'])) ?></span>
                </div>
                <h3 class="card-title"><?= h($a['titre']) ?></h3>
                <div class="annonce-price mt-2"><?= h(format_price($a['prix'])) ?></div>
                <div class="mt-auto pt-3 text-muted" style="font-size:.82rem;">Vendeur : <?= h($a['pseudo']) ?></div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
