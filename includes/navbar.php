<?php
$user = (isset($user) && is_array($user)) ? $user : null;
$search = $search ?? '';
$nbUnread = (int)($nbUnread ?? 0);
?>

<nav class="navbar navbar-expand-lg navbar-danslebueno fixed-top">
  <div class="container-xl d-flex align-items-center gap-3">

    <a href="index.php" class="navbar-brand me-0 me-lg-3 flex-shrink-0">
      <span class="logo-text">
        <span class="logo-dans">dans</span><span class="logo-le">le</span><span class="logo-bueno">bueno</span>
      </span>
    </a>

    <form action="index.php" method="GET" class="search-bar-wrapper d-none d-md-flex flex-grow-1">
      <div class="input-group w-100">
        <input
          type="search"
          class="form-control"
          placeholder="Rechercher une annonce…"
          name="q"
          value="<?= h($search) ?>"
        />
        <button class="btn-search" type="submit">
          <i class="bi bi-search"></i>
        </button>
      </div>
    </form>

    <button class="navbar-toggler border-0 ms-auto"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
            aria-controls="navbarMenu"
            aria-expanded="false"
            aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse flex-grow-0" id="navbarMenu">

      <form action="index.php" method="GET" class="search-bar-wrapper d-md-none my-2 w-100">
        <div class="input-group w-100">
          <input
            type="search"
            class="form-control"
            placeholder="Rechercher…"
            name="q"
            value="<?= h($search) ?>"
          />
          <button class="btn-search" type="submit">
            <i class="bi bi-search"></i>
          </button>
        </div>
      </form>

      <div class="d-flex align-items-center gap-2 gap-lg-3 mt-2 mt-lg-0">

        <a href="deposer-annonce.php" class="btn btn-deposer flex-shrink-0">
          <i class="bi bi-plus-lg me-1"></i>Déposer
        </a>

        <?php if ($user !== null): ?>

          <a href="messagerie.php" class="nav-icon-link position-relative">
            <i class="bi bi-chat-dots"></i>
            <?php if ($nbUnread > 0): ?>
              <span class="badge-unread position-absolute" style="top:-6px;right:-6px;">
                <?= $nbUnread ?>
              </span>
            <?php endif; ?>
            <span>Messages</span>
          </a>

          <div class="dropdown">
            <a href="#" class="nav-icon-link" data-bs-toggle="dropdown" aria-expanded="false" id="menuCompte">
              <i class="bi bi-person-circle"></i>
              <span><?= h($user['pseudo'] ?? 'Mon compte') ?></span>
            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-1" aria-labelledby="menuCompte">
              <li>
                <a class="dropdown-item" href="profil.php">
                  <i class="bi bi-person me-2 text-muted"></i>Mon profil
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="mes-annonces.php">
                  <i class="bi bi-tag me-2 text-muted"></i>Mes annonces
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="mes-favoris.php">
                  <i class="bi bi-heart me-2 text-muted"></i>Mes favoris
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="messagerie.php">
                  <i class="bi bi-chat-dots me-2 text-muted"></i>Messagerie
                </a>
              </li>

              <?php if (($user['role'] ?? 'user') === 'admin'): ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item text-warning fw-bold" href="admin.php">
                    <i class="bi bi-shield-lock me-2"></i>Administration
                  </a>
                </li>
              <?php endif; ?>

              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="logout.php">
                  <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                </a>
              </li>
            </ul>
          </div>

        <?php else: ?>

          <a href="auth.php?mode=connexion" class="nav-icon-link">
            <i class="bi bi-person"></i>
            <span>Connexion</span>
          </a>

        <?php endif; ?>

      </div>
    </div>
  </div>
</nav>