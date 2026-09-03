<?php
require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validarCsrf($_POST['csrf_token'] ?? null)) {
    
$email = trim((string) ($_POST['email'] ?? ''));
$senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    if (empty($email) || empty($senha)) {
        header('Location: ../index.php?erro=campos_vazios');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash, perfil, ativo, codigo_acesso FROM usuarios WHERE LOWER(email) = LOWER(?) OR LOWER(nome) = LOWER(?) LIMIT 1");
        $stmt->execute([$email, $email]);
        $usuario = $stmt->fetch();
        $senhaValida = false;
        if ($usuario) {
            if (!empty($usuario['senha_hash'])) {
                $senhaValida = password_verify($senha, (string) $usuario['senha_hash']);
            } elseif (!empty($usuario['codigo_acesso'])) {
                $senhaValida = hash_equals((string) $usuario['codigo_acesso'], $senha);
            }
        }

        if ($usuario && $usuario['ativo'] != 1 && $senhaValida) {
            session_regenerate_id(true);
            $_SESSION['usuario_bloqueado_id'] = $usuario['id'];
            $_SESSION['usuario_bloqueado_nome'] = $usuario['nome'];
            $_SESSION['usuario_bloqueado_email'] = $usuario['email'];
            header('Location: ../index.php?bloqueado=1');
            exit;
        }

        if ($usuario && $usuario['ativo'] == 1 && $senhaValida) {
            
            session_regenerate_id(true);

            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = (string) ($usuario['email'] ?? '');
            $_SESSION['logado']       = true;
            $_SESSION['usuario_perfil'] = $usuario['perfil'];

            header('Location: ../inicio.php');
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
    header('Location: ../index.php?erro=solicitacao');
    exit;
}
