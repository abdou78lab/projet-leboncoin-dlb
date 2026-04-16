<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    set_flash('flash_error', 'Annonce invalide.');
    redirect('index.php');
}

$stmt = $pdo->prepare('
    SELECT a.*, u.pseudo, u.email, u.created_at AS user_created_at, u.id AS seller_id
    FROM annonces a
    JOIN users u ON u.id = a.user_id
    WHERE a.id = ?
    LIMIT 1
');
$stmt->execute([$id]);
$annonce = $stmt->fetch();

if (!$annonce) {
    set_flash('flash_error', 'Annonce introuvable.');
    redirect('index.php');
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM annonces WHERE user_id = ?');
$stmt->execute([(int) $annonce['seller_id']]);
$nbAnnoncesVendeur = (int) $stmt->fetchColumn();

$isOwner = is_logged_in() && (int) $_SESSION['user_id'] === (int) $annonce['user_id'];

$isFavori = false;
if (is_logged_in()) {
    $stmt = $pdo->prepare('SELECT 1 FROM favoris WHERE user_id = ? AND annonce_id = ?');
    $stmt->execute([(int) $_SESSION['user_id'], $id]);
    $isFavori = (bool) $stmt->fetchColumn();
}

$flashSuccess = flash('flash_success');
$flashError = flash('flash_error');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= h($annonce['titre']) ?> – DansLeBueno</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
<?php render_navbar(); ?>

<section class="page-hero">
  <div class="container-xl">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
        <li class="breadcrumb-item active"><?= h($annonce['titre']) ?></li>
      </ol>
    </nav>
    <h1 class="mb-0"><?= h($annonce['titre']) ?></h1>
  </div>
</section>

<div class="container-xl pb-5">
  <?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?= h($flashSuccess) ?></div>
  <?php endif; ?>

  <?php if ($flashError): ?>
    <div class="alert alert-danger"><?= h($flashError) ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-12 col-lg-7">
      <div class="bg-white rounded-4 shadow-sm p-3 p-lg-4 mb-4">
        <?php if (!empty($annonce['image'])): ?>
          <img
            src="<?= h(image_url($annonce['image'])) ?>"
            class="img-fluid rounded-4 w-100"
            alt="<?= h($annonce['titre']) ?>"
            style="max-height:520px;object-fit:cover;"
          >
        <?php else: ?>
          <div class="img-placeholder rounded-4" style="height:420px;">
            <i class="bi bi-image"></i>
          </div>
        <?php endif; ?>
      </div>

      <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
        <h2 class="section-title">Description</h2>
        <p class="mb-0" style="white-space:pre-line;"><?= h($annonce['description']) ?></p>
      </div>

      <?php if (!$isOwner && is_logged_in()): ?>
        <div class="bg-white rounded-4 shadow-sm p-4" id="contact-vendeur">
          <h2 class="section-title mb-3">Contacter le vendeur</h2>
          <p class="text-muted mb-3">
            Envoyez un premier message au vendeur à propos de cette annonce.
          </p>

          <form action="envoyer-message.php" method="POST">
            <input type="hidden" name="annonce_id" value="<?= (int) $annonce['id'] ?>">
            <input type="hidden" name="to_user_id" value="<?= (int) $annonce['seller_id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

            <div class="mb-3">
              <textarea
                name="contenu"
                class="form-control"
                rows="5"
                placeholder="Bonjour, votre annonce m'intéresse..."
                required
              ></textarea>
            </div>

            <button type="submit" class="btn btn-primary-orange">
              <i class="bi bi-send me-1"></i>Envoyer le message
            </button>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-12 col-lg-5">
      <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
          <div>
            <span class="badge-etat <?= h($annonce['etat']) ?> mb-2 d-inline-block">
              <?= h(state_label($annonce['etat'])) ?>
            </span>
            <div class="annonce-price"><?= h(format_price($annonce['prix'])) ?></div>
          </div>
          <div class="text-muted text-end" style="font-size:.85rem;">
            Publiée le<br><?= date('d/m/Y', strtotime((string) $annonce['created_at'])) ?>
          </div>
        </div>

        <div class="d-grid gap-2">
          <?php if ($isOwner): ?>
            <a href="modifier-annonce.php?id=<?= (int) $annonce['id'] ?>" class="btn btn-primary-orange">
              <i class="bi bi-pencil me-1"></i>Modifier l’annonce
            </a>

            <a
              href="supprimer-annonce.php?id=<?= (int) $annonce['id'] ?>"
              class="btn btn-outline-danger"
              onclick="return confirm('Supprimer cette annonce ?');"
            >
              <i class="bi bi-trash me-1"></i>Supprimer
            </a>
          <?php else: ?>
            <?php if (is_logged_in()): ?>
              <a href="#contact-vendeur" class="btn btn-primary-orange">
                <i class="bi bi-chat-dots me-1"></i>Contacter le vendeur
              </a>

              <form action="toggle-favori.php" method="POST">
                <input type="hidden" name="annonce_id" value="<?= (int) $annonce['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <button class="btn <?= $isFavori ? 'btn-danger' : 'btn-outline-secondary' ?> w-100" type="submit">
                  <i class="bi <?= $isFavori ? 'bi-heart-fill' : 'bi-heart' ?> me-1"></i>
                  <?= $isFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>
                </button>
              </form>
            <?php else: ?>
              <a href="auth.php?mode=connexion" class="btn btn-primary-orange">
                <i class="bi bi-person me-1"></i>Connectez-vous pour contacter le vendeur
              </a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="bg-white rounded-4 shadow-sm p-4">
        <h2 class="section-title mb-3">Vendeur</h2>

        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="conv-avatar" style="width:56px;height:56px;">
            <?= h(strtoupper(substr((string) $annonce['pseudo'], 0, 1))) ?>
          </div>
          <div>
            <div class="fw-bold"><?= h($annonce['pseudo']) ?></div>
            <div class="text-muted" style="font-size:.85rem;">
              Membre depuis <?= date('F Y', strtotime((string) $annonce['user_created_at'])) ?>
            </div>
          </div>
        </div>

        <div class="text-muted" style="font-size:.9rem;">
          <?= $nbAnnoncesVendeur ?> annonce(s) en ligne
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>