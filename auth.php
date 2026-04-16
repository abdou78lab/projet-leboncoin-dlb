<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin.php' : 'index.php');
}

$allowedModes = ['connexion', 'inscription'];
$activeTab = $_GET['mode'] ?? 'connexion';

if (!in_array($activeTab, $allowedModes, true)) {
    $activeTab = 'connexion';
}

$forcedTab = flash('auth_active_tab');
if ($forcedTab && in_array($forcedTab, $allowedModes, true)) {
    $activeTab = $forcedTab;
}

$globalError = flash('flash_error');
$success = flash('flash_success');
$loginError = flash('auth_error_login');
$registerError = flash('auth_error_register');
$oldLogin = pull_array('auth_old_login');
$oldRegister = pull_array('auth_old_register');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Connexion / Inscription – DansLeBueno</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <style>
    body {
      background: linear-gradient(135deg, var(--color-sky-light) 0%, var(--color-orange-light) 100%);
    }
    .auth-wrapper {
      min-height: calc(100vh - 72px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }
    .auth-card {
      width: 100%;
      max-width: 480px;
    }
    .auth-tabs {
      display: grid;
      grid-template-columns: 1fr 1fr;
      background: var(--color-neutral-100);
      border-radius: 999px;
      padding: 4px;
      margin-bottom: 1.5rem;
      gap: 4px;
    }
    .auth-tab-link {
      display: block;
      text-align: center;
      text-decoration: none;
      border: none;
      background: none;
      border-radius: 999px;
      padding: .6rem;
      font-weight: 800;
      color: #333;
      transition: all .2s ease;
    }
    .auth-tab-link.active {
      background: #fff;
      color: var(--color-orange);
      box-shadow: 0 2px 8px rgba(0,0,0,.08);
    }
    .form-card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 8px 30px rgba(0,0,0,.08);
      padding: 1.5rem;
    }
    .divider-or {
      text-align: center;
      color: var(--color-neutral-500);
      margin: 1rem 0;
      position: relative;
    }
    .divider-or::before,
    .divider-or::after {
      content: "";
      position: absolute;
      top: 50%;
      width: 42%;
      height: 1px;
      background: #e9ecef;
    }
    .divider-or::before { left: 0; }
    .divider-or::after { right: 0; }
  </style>
</head>
<body>
<nav class="navbar navbar-danslebueno fixed-top">
  <div class="container-xl">
    <a href="index.php" class="navbar-brand">
      <span class="logo-text">
        <span class="logo-dans">dans</span><span class="logo-le">le</span><span class="logo-bueno">bueno</span>
      </span>
    </a>
    <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill ms-auto">
      <i class="bi bi-arrow-left me-1"></i>Retour aux annonces
    </a>
  </div>
</nav>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="text-center mb-4">
      <span class="logo-text" style="font-size:2rem;">
        <span class="logo-dans">dans</span><span class="logo-le">le</span><span class="logo-bueno">bueno</span>
      </span>
      <p class="text-muted mt-1 mb-0">Achetez, vendez, échangez près de chez vous.</p>
    </div>

    <div class="form-card">
      <?php if ($globalError): ?>
        <div class="alert alert-danger py-2 mb-3"><?= h($globalError) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success py-2 mb-3"><?= h($success) ?></div>
      <?php endif; ?>

      <div class="auth-tabs">
        <a
          href="auth.php?mode=connexion"
          class="auth-tab-link <?= $activeTab === 'connexion' ? 'active' : '' ?>">
          Connexion
        </a>

        <a
          href="auth.php?mode=inscription"
          class="auth-tab-link <?= $activeTab === 'inscription' ? 'active' : '' ?>">
          Inscription
        </a>
      </div>

      <?php if ($activeTab === 'connexion'): ?>

        <?php if ($loginError): ?>
          <div class="alert alert-danger py-2 mb-3"><?= h($loginError) ?></div>
        <?php endif; ?>

        <form action="connexion.php" method="POST">
          <div class="mb-3">
            <label class="form-label">Adresse e-mail</label>
            <input
              type="email"
              class="form-control"
              name="email"
              required
              value="<?= h(old($oldLogin, 'email')) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Mot de passe</label>
            <input
              type="password"
              class="form-control"
              name="password"
              required>
          </div>

          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

          <button type="submit" class="btn btn-primary-orange w-100 py-2">
            Se connecter
          </button>
        </form>

        <div class="divider-or">ou</div>

        <p class="text-center mb-0">
          Pas encore de compte ?
          <a href="auth.php?mode=inscription" style="color:var(--color-orange);font-weight:700;">
            Créer un compte
          </a>
        </p>

      <?php else: ?>

        <?php if ($registerError): ?>
          <div class="alert alert-danger py-2 mb-3"><?= h($registerError) ?></div>
        <?php endif; ?>

        <form action="inscription.php" method="POST">
          <div class="mb-3">
            <label class="form-label">Pseudo</label>
            <input
              type="text"
              class="form-control"
              name="pseudo"
              maxlength="60"
              required
              value="<?= h(old($oldRegister, 'pseudo')) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Nom <span class="text-muted">(optionnel)</span></label>
            <input
              type="text"
              class="form-control"
              name="nom"
              maxlength="80"
              value="<?= h(old($oldRegister, 'nom')) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Adresse e-mail</label>
            <input
              type="email"
              class="form-control"
              name="email"
              required
              value="<?= h(old($oldRegister, 'email')) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Mot de passe</label>
            <input
              type="password"
              class="form-control"
              name="password"
              minlength="8"
              required>
          </div>

          <div class="mb-3">
            <label class="form-label">Confirmer le mot de passe</label>
            <input
              type="password"
              class="form-control"
              name="password_confirm"
              minlength="8"
              required>
          </div>

          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="cgu" id="cgu" required>
            <label class="form-check-label" for="cgu">
              J'accepte les conditions d'utilisation.
            </label>
          </div>

          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

          <button type="submit" class="btn btn-primary-orange w-100 py-2">
            Créer mon compte
          </button>
        </form>

        <div class="divider-or">ou</div>

        <p class="text-center mb-0">
          Vous avez déjà un compte ?
          <a href="auth.php?mode=connexion" style="color:var(--color-orange);font-weight:700;">
            Se connecter
          </a>
        </p>

      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>