<?php
require_once __DIR__ . '/includes/bootstrap.php';
session_unset();
session_destroy();
session_start();
set_flash('flash_success', 'Vous êtes maintenant déconnecté.');
redirect('index.php');
