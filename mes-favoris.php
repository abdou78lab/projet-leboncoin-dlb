<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = "Vous devez être connecté pour accéder à cette page.";
    header('Location: auth.php?mode=connexion');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$search = $search ?? '';
$nbUnread = $nbUnread ?? 0;

/*
 * Version compatible avec un schéma classique :
 * favoris(user_id, annonce_id)
 * annonces(id, user_id, titre, prix, etat, description, image, created_at)
 * users(id, pseudo)
 */
$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.titre,
        a.prix,
        a.etat,
        a.description,
        a.image,
        a.created_at,
        u.pseudo
    FROM favoris f
    INNER JOIN annonces a ON a.id = f.annonce_id
    INNER JOIN users u ON u.id = a.user_id
    WHERE f.user_id = ?
    ORDER BY a.id DESC
");
$stmt->execute([$userId]);
$favoris = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes favoris - DansLeBueno</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="container py-5" style="margin-top:100px;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h2 mb-0">Mes favoris</h1>
        <a href="index.php" class="btn btn-outline-dark">Retour aux annonces</a>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!$favoris): ?>
        <div class="alert alert-info">Vous n’avez encore aucun favori.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($favoris as $annonce): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">
                        <?php if (!empty($annonce['image'])): ?>
                            <img
                                src="uploads/<?= htmlspecialchars($annonce['image']) ?>"
                                class="card-img-top"
                                alt="Image annonce"
                                style="height:220px; object-fit:cover;"
                            >
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height:220px;">
                                <span class="text-muted">Aucune image</span>
                            </div>
                        <?php endif; ?>

                        <div class="card-body">
                            <h2 class="h5"><?= htmlspecialchars($annonce['titre']) ?></h2>
                            <p class="fw-bold text-warning fs-4 mb-2">
                                <?= number_format((float)$annonce['prix'], 2, ',', ' ') ?> €
                            </p>
                            <p class="mb-2"><strong>État :</strong> <?= htmlspecialchars($annonce['etat']) ?></p>
                            <p class="small text-muted mb-2">
                                Vendeur : <?= htmlspecialchars($annonce['pseudo'] ?? 'Utilisateur') ?>
                            </p>
                            <p class="text-muted small">
                                <?= htmlspecialchars(substr((string)($annonce['description'] ?? ''), 0, 110)) ?>
                                <?= strlen((string)($annonce['description'] ?? '')) > 110 ? '...' : '' ?>
                            </p>
                        </div>

                        <div class="card-footer bg-white border-0 d-flex gap-2 flex-wrap">
                            <a href="detail-annonce.php?id=<?= (int)$annonce['id'] ?>" class="btn btn-outline-dark btn-sm">
                                Voir
                            </a>
                            <a href="toggle-favori.php?id=<?= (int)$annonce['id'] ?>&redirect=mes-favoris.php"
                               class="btn btn-outline-danger btn-sm">
                                Retirer des favoris
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>