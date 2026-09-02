<?php
require_once __DIR__ . '/config.php';

// Compatibilidade com links antigos: o painel administrativo agora se chama dashboard.php.
redirecionar('dashboard.php');
