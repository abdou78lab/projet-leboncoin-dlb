<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = "Vous devez être connecté pour accéder à cette page.";
    header('Location: auth.php?mode=connexion');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$annonceId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM annonces WHERE id = ? AND user_id = ?");
$stmt->execute([$annonceId, $userId]);
$annonce = $stmt->fetch();

if (!$annonce) {
    $_SESSION['flash_error'] = "Annonce introuvable ou accès interdit.";
    header('Location: mes-annonces.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $prix = trim($_POST['prix'] ?? '');
    $etat = trim($_POST['etat'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $imageName = $annonce['image'];

    if ($titre === '') {
        $errors[] = "Le titre est obligatoire.";
    }

    if ($prix === '' || !is_numeric($prix) || (float)$prix < 0) {
        $errors[] = "Le prix est invalide.";
    }

    $etatsAutorises = ['Neuf', 'Bon état', 'État correct'];
    if (!in_array($etat, $etatsAutorises, true)) {
        $errors[] = "L’état est invalide.";
    }

    if ($description === '') {
        $errors[] = "La description est obligatoire.";
    }

    if (!empty($_FILES['image']['name'])) {
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $maxSize = 5 * 1024 * 1024;

        $tmp = $_FILES['image']['tmp_name'];
        $original = $_FILES['image']['name'];
        $size = (int) $_FILES['image']['size'];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            $errors[] = "Format image non autorisé.";
        }

        if ($size > $maxSize) {
            $errors[] = "Image trop lourde (max 5 Mo).";
        }

        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmp);
            $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($mime, $allowedMime, true)) {
                $errors[] = "Le fichier envoyé n'est pas une image valide.";
            }
        }

        if (!$errors) {
            $imageName = uniqid('annonce_', true) . '.' . $ext;
            $destination = __DIR__ . '/uploads/' . $imageName;

            if (!move_uploaded_file($tmp, $destination)) {
                $errors[] = "Impossible d'enregistrer l'image.";
            } else {
                if (!empty($annonce['image']) && file_exists(__DIR__ . '/uploads/' . $annonce['image'])) {
                    @unlink(__DIR__ . '/uploads/' . $annonce['image']);
                }
            }
        }
    }

    if (!$errors) {
        $update = $pdo->prepare("
            UPDATE annonces
            SET titre = ?, prix = ?, etat = ?, description = ?, image = ?
            WHERE id = ? AND user_id = ?
        ");
        $update->execute([
            $titre,
            $prix,
            $etat,
            $description,
            $imageName,
            $annonceId,
            $userId
        ]);

        $_SESSION['flash_success'] = "Annonce modifiée avec succès.";
        header('Location: mes-annonces.php');
        exit;
    }

    $annonce['titre'] = $titre;
    $annonce['prix'] = $prix;
    $annonce['etat'] = $etat;
    $annonce['description'] = $description;
    $annonce['image'] = $imageName;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier une annonce</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="h2 mb-4">Modifier mon annonce</h1>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <label class="form-label">Titre</label>
                    <input type="text" name="titre" class="form-control mb-3" value="<?= htmlspecialchars($annonce['titre']) ?>" required>

                    <label class="form-label">Prix</label>
                    <input type="number" step="0.01" min="0" name="prix" class="form-control mb-3" value="<?= htmlspecialchars($annonce['prix']) ?>" required>

                    <label class="form-label">État</label>
                    <select name="etat" class="form-select mb-3" required>
                        <?php foreach (['Neuf', 'Bon état', 'État correct'] as $etat): ?>
                            <option value="<?= htmlspecialchars($etat) ?>" <?= $annonce['etat'] === $etat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($etat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="7" required><?= htmlspecialchars($annonce['description']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <label class="form-label">Nouvelle image</label>
                    <input type="file" name="image" class="form-control mb-3" accept=".jpg,.jpeg,.png,.webp,.gif">

                    <?php if (!empty($annonce['image'])): ?>
                        <p class="small text-muted mb-2">Image actuelle :</p>
                        <img src="uploads/<?= htmlspecialchars($annonce['image']) ?>" alt="Image actuelle" class="img-fluid rounded">
                    <?php endif; ?>

                    <div class="d-grid gap-2 mt-4">
                        <button class="btn btn-warning fw-bold">Enregistrer les modifications</button>
                        <a href="mes-annonces.php" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</body>
</html>