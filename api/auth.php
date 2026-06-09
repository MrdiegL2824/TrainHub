<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('index.php');
}

if (!validarCsrf($_POST['csrf_token'] ?? null)) {
    $_SESSION['login_erro'] = 'Sessao expirada. Tente novamente.';
    redirecionar('index.php');
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$senha = (string) ($_POST['senha'] ?? '');

if (!$email || $senha === '') {
    $_SESSION['login_erro'] = 'Informe email e senha.';
    redirecionar('index.php');
}

try {
    $stmt = db()->prepare(
        'SELECT id, nome, email, senha_hash, perfil, ativo
           FROM usuarios
          WHERE email = :email
          LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();
} catch (RuntimeException) {
    $_SESSION['login_erro'] = 'Banco de dados indisponivel. Inicie o MySQL e importe o SQL do projeto.';
    redirecionar('index.php');
}

if (!$usuario || !$usuario['ativo'] || empty($usuario['senha_hash']) || !password_verify($senha, $usuario['senha_hash'])) {
    $_SESSION['login_erro'] = 'Email ou senha invalidos.';
    redirecionar('index.php');
}

session_regenerate_id(true);
$_SESSION['usuario'] = [
    'id' => (int) $usuario['id'],
    'nome' => $usuario['nome'],
    'email' => $usuario['email'],
    'perfil' => $usuario['perfil'],
];

redirecionar('dashboard.php');
