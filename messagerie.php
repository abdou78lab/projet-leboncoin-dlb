<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = "Vous devez être connecté pour accéder à la messagerie.";
    header('Location: auth.php?mode=connexion');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$search = $search ?? '';
$nbUnread = $nbUnread ?? 0;

/*
 * 1 fil = 1 annonce + 2 utilisateurs
 * Adapté à ta table messages avec sender_id / receiver_id
 */
$sql = "
    SELECT
        m.annonce_id,
        a.titre AS annonce_titre,
        CASE
            WHEN m.sender_id = :uid1 THEN m.receiver_id
            ELSE m.sender_id
        END AS other_user_id,
        MAX(m.created_at) AS last_message_at
    FROM messages m
    INNER JOIN annonces a ON a.id = m.annonce_id
    WHERE m.sender_id = :uid2 OR m.receiver_id = :uid3
    GROUP BY
        m.annonce_id,
        a.titre,
        CASE
            WHEN m.sender_id = :uid4 THEN m.receiver_id
            ELSE m.sender_id
        END
    ORDER BY last_message_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'uid1' => $userId,
    'uid2' => $userId,
    'uid3' => $userId,
    'uid4' => $userId,
]);

$threadsRaw = $stmt->fetchAll();

$threads = [];
$getUserStmt = $pdo->prepare("SELECT id, pseudo FROM users WHERE id = ?");

foreach ($threadsRaw as $thread) {
    $getUserStmt->execute([(int)$thread['other_user_id']]);
    $otherUser = $getUserStmt->fetch();

    $threads[] = [
        'annonce_id' => (int)$thread['annonce_id'],
        'annonce_titre' => $thread['annonce_titre'],
        'other_user_id' => (int)$thread['other_user_id'],
        'other_pseudo' => $otherUser['pseudo'] ?? 'Utilisateur',
        'last_message_at' => $thread['last_message_at'],
    ];
}

$selectedAnnonce = isset($_GET['annonce_id']) ? (int)$_GET['annonce_id'] : 0;
$selectedOtherUser = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$messages = [];

if ($selectedAnnonce > 0 && $selectedOtherUser > 0) {
    $msgStmt = $pdo->prepare("
        SELECT
            m.*,
            fu.pseudo AS from_pseudo,
            tu.pseudo AS to_pseudo
        FROM messages m
        INNER JOIN users fu ON fu.id = m.sender_id
        INNER JOIN users tu ON tu.id = m.receiver_id
        WHERE m.annonce_id = ?
          AND (
            (m.sender_id = ? AND m.receiver_id = ?)
            OR
            (m.sender_id = ? AND m.receiver_id = ?)
          )
        ORDER BY m.created_at ASC, m.id ASC
    ");

    $msgStmt->execute([
        $selectedAnnonce,
        $userId, $selectedOtherUser,
        $selectedOtherUser, $userId
    ]);

    $messages = $msgStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Messagerie - DansLeBueno</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="container py-5" style="margin-top:100px;">
    <h1 class="h2 mb-4">Messagerie</h1>

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

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h5 mb-3">Mes discussions</h2>

                    <?php if (!$threads): ?>
                        <div class="alert alert-info mb-0">Aucune discussion pour le moment.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($threads as $thread): ?>
                                <?php
                                $isActive = (
                                    $selectedAnnonce === (int)$thread['annonce_id']
                                    && $selectedOtherUser === (int)$thread['other_user_id']
                                );
                                ?>
                                <a
                                    href="messagerie.php?annonce_id=<?= (int)$thread['annonce_id'] ?>&user_id=<?= (int)$thread['other_user_id'] ?>"
                                    class="list-group-item list-group-item-action <?= $isActive ? 'active' : '' ?>"
                                >
                                    <div class="fw-bold"><?= htmlspecialchars($thread['other_pseudo']) ?></div>
                                    <div class="small">Annonce : <?= htmlspecialchars($thread['annonce_titre']) ?></div>
                                    <div class="small text-muted">
                                        Dernier message :
                                        <?= !empty($thread['last_message_at']) ? date('d/m/Y H:i', strtotime($thread['last_message_at'])) : '-' ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <?php if ($selectedAnnonce <= 0 || $selectedOtherUser <= 0): ?>
                        <p class="text-muted mb-0">Sélectionnez une discussion à gauche.</p>
                    <?php else: ?>
                        <div class="mb-3">
                            <h2 class="h5 mb-1">Conversation</h2>
                            <p class="text-muted mb-0">Fil unique lié à une annonce et à deux utilisateurs.</p>
                        </div>

                        <div class="border rounded p-3 mb-3" style="max-height:420px; overflow-y:auto;">
                            <?php if (!$messages): ?>
                                <p class="text-muted mb-0">Aucun message dans cette discussion.</p>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="mb-3 <?= ((int)$message['sender_id'] === $userId) ? 'text-end' : '' ?>">
                                        <div
                                            class="d-inline-block px-3 py-2 rounded <?= ((int)$message['sender_id'] === $userId) ? 'bg-warning-subtle' : 'bg-light' ?>"
                                            style="max-width:80%;"
                                        >
                                            <div class="small fw-bold mb-1">
                                                <?= htmlspecialchars($message['from_pseudo']) ?>
                                            </div>
                                            <div><?= nl2br(htmlspecialchars($message['contenu'])) ?></div>
                                            <div class="small text-muted mt-1">
                                                <?= !empty($message['created_at']) ? date('d/m/Y H:i', strtotime($message['created_at'])) : '-' ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <form action="envoyer-message.php" method="POST">
                            <input type="hidden" name="annonce_id" value="<?= (int)$selectedAnnonce ?>">
                            <input type="hidden" name="to_user_id" value="<?= (int)$selectedOtherUser ?>">

                            <div class="mb-3">
                                <textarea
                                    name="contenu"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Votre message..."
                                    required
                                ></textarea>
                            </div>

                            <button type="submit" class="btn btn-warning fw-bold">Envoyer</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>