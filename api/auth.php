<?php
session_start();

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    if (empty($email) || empty($senha)) {
        header('Location: ../index.php?erro=campos_vazios');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash, perfil, ativo FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && $usuario['ativo'] == 1 && password_verify($senha, $usuario['senha_hash'])) {
            
            session_regenerate_id(true);

            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['logado']       = true;

            header('Location: ../dashboard.php');
            exit;

        } else {
            header('Location: ../index.php?erro=credenciais_invalidas');
            exit;
        }

    } catch (PDOException $e) {
        error_log("Erro de Autenticação SGT: " . $e->getMessage());
        header('Location: ../index.php?erro=erro_interno');
        exit;
    }

} else {
    header('Location: ../index.php');
    exit;
}