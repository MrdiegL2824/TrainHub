<?php
require_once 'config.php';

// Compatibilidade com links antigos: não existe mais cadastro público.
if (usuarioLogado() && temPerfil('admin')) {
    redirecionar('admin-cadastrar-usuario.php');
}

redirecionar('index.php');
