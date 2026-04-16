<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$user = current_user($pdo);
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
$editing = $id > 0;
$annonce = [
    'id' => 0,
    'titre' => '',
    'prix' => '',
    'etat' => 'bon',
    'description' => '',
    'image' => null,
    'user_id' => (int)$user['id'],
];

if ($editing) {
    $stmt = $pdo->prepare('SELECT * FROM annonces WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        set_flash('flash_error', 'Annonce introuvable.');
        redirect('index.php');
    }
    if ((int)$found['user_id'] !== (int)$user['id'] && !is_admin()) {
        set_flash('flash_error', 'Vous ne pouvez pas modifier cette annonce.');
        redirect('index.php');
    }
    $annonce = $found;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail($editing ? 'deposer-annonce.php?id=' . $id : 'deposer-annonce.php');
    $titre = trim((string)($_POST['titre'] ?? ''));
    $prix = trim((string)($_POST['prix'] ?? ''));
    $etat = trim((string)($_POST['etat'] ?? 'bon'));
    $description = trim((string)($_POST['description'] ?? ''));
    $supprimerImage = isset($_POST['supprimer_image']);

    $annonce['titre'] = $titre;
    $annonce['prix'] = $prix;
    $annonce['etat'] = $etat;
    $annonce['description'] = $description;

    if ($titre === '' || strlen($titre) < 3) $errors[] = 'Le titre doit contenir au moins 3 caractères.';
    if ($prix === '' || !is_numeric($prix) || (float)$prix < 0) $errors[] = 'Le prix est invalide.';
    if (!in_array($etat, ['neuf', 'bon', 'correct'], true)) $errors[] = 'État invalide.';
    if ($description === '' || strlen($description) < 10) $errors[] = 'La description doit contenir au moins 10 caractères.';

    if (!$errors) {
        try {
            $image = $annonce['image'] ?? null;
            if ($supprimerImage) {
                delete_image_file($image);
                $image = null;
            }
            if (isset($_FILES['image'])) {
                $image = handle_uploaded_image($_FILES['image'], $supprimerImage ? null : $image);
            }

            if ($editing) {
                $stmt = $pdo->prepare('UPDATE annonces SET titre = ?, prix = ?, etat = ?, description = ?, image = ? WHERE id = ?');
                $stmt->execute([$titre, (float)$prix, $etat, $description, $image, $id]);
                set_flash('flash_success', 'Annonce mise à jour avec succès.');
                redirect('detail-annonce.php?id=' . $id);
            } else {
                $stmt = $pdo->prepare('INSERT INTO annonces (user_id, titre, prix, etat, description, image) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([(int)$user['id'], $titre, (float)$prix, $etat, $description, $image]);
                $newId = (int)$pdo->lastInsertId();
                set_flash('flash_success', 'Annonce créée avec succès.');
                redirect('detail-annonce.php?id=' . $newId);
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $editing ? 'Modifier' : 'Déposer' ?> une annonce – DansLeBueno</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
<?php render_navbar(); ?>
<section class="page-hero"><div class="container-xl"><h1 class="mb-0"><?= $editing ? 'Modifier mon annonce' : 'Déposer une annonce' ?></h1></div></section>
<div class="container-xl pb-5">
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form action="deposer-annonce.php<?= $editing ? '?id=' . (int)$annonce['id'] : '' ?>" method="POST" enctype="multipart/form-data" class="row g-4">
    <div class="col-12 col-lg-8">
      <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
        <h2 class="section-title mb-4">Informations principales</h2>
        <div class="mb-3"><label class="form-label">Titre</label><input type="text" class="form-control" name="titre" maxlength="120" required value="<?= h((string)$annonce['titre']) ?>"></div>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Prix</label><input type="number" class="form-control" name="prix" min="0" step="0.01" required value="<?= h((string)$annonce['prix']) ?>"></div>
          <div class="col-md-6"><label class="form-label d-block">État</label>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach (['neuf' => 'Neuf', 'bon' => 'Bon état', 'correct' => 'État correct'] as $val => $label): ?>
                <div><input type="radio" class="btn-check" name="etat" id="etat-<?= h($val) ?>" value="<?= h($val) ?>" <?= $annonce['etat'] === $val ? 'checked' : '' ?>><label class="btn btn-outline-secondary rounded-pill" for="etat-<?= h($val) ?>"><?= h($label) ?></label></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-4 shadow-sm p-4">
        <h2 class="section-title mb-4">Description</h2>
        <textarea class="form-control" name="description" rows="8" required><?= h((string)$annonce['description']) ?></textarea>
      </div>
    </div>
    <div class="col-12 col-lg-4">
      <div class="bg-white rounded-4 shadow-sm p-4">
        <h2 class="section-title mb-4">Photo</h2>
        <?php if (!empty($annonce['image'])): ?>
          <img src="<?= h(image_url($annonce['image'])) ?>" class="img-fluid rounded-3 mb-3" alt="Image actuelle">
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="supprimer_image" id="suppImg"><label class="form-check-label" for="suppImg">Supprimer l'image actuelle</label></div>
        <?php endif; ?>
        <input type="file" class="form-control" name="image" accept="image/*">
        <div class="form-text mt-2">Formats autorisés : JPG, PNG, WEBP, GIF.</div>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$annonce['id'] ?>"><?php endif; ?>
        <div class="d-grid gap-2 mt-4">
          <button type="submit" class="btn btn-primary-orange"><?= $editing ? 'Enregistrer les modifications' : 'Publier l’annonce' ?></button>
          <a href="<?= $editing ? 'detail-annonce.php?id=' . (int)$annonce['id'] : 'index.php' ?>" class="btn btn-outline-secondary">Annuler</a>
        </div>
      </div>
    </div>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
