<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrf($_POST['csrf_token'] ?? null)) {
    redirecionar('index.php?erro=solicitacao');
}

$identificador = trim((string) ($_POST['email'] ?? ''));
$senha = (string) ($_POST['senha'] ?? '');

if ($identificador === '' || $senha === '') {
    redirecionar('index.php?erro=campos_vazios');
}

$identificadorHash = hash('sha256', mb_strtolower($identificador));
$ip = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'desconhecido'))[0]);
$ipHash = hash('sha256', $ip);
$janelaSql = bancoPostgres($pdo)
    ? "CURRENT_TIMESTAMP - INTERVAL '15 minutes'"
    : 'DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 15 MINUTE)';

try {
    $limpezaSql = bancoPostgres($pdo)
        ? "DELETE FROM tentativas_login WHERE criado_em < CURRENT_TIMESTAMP - INTERVAL '1 day'"
        : 'DELETE FROM tentativas_login WHERE criado_em < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY)';
    $pdo->exec($limpezaSql);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tentativas_login WHERE identificador_hash = ? AND ip_hash = ? AND criado_em >= $janelaSql");
    $stmt->execute([$identificadorHash, $ipHash]);
    if ((int) $stmt->fetchColumn() >= 5) {
        redirecionar('index.php?erro=muitas_tentativas');
    }

    $stmt = $pdo->prepare(
        'SELECT id, nome, email, senha_hash, perfil, ativo
         FROM usuarios
         WHERE LOWER(email) = LOWER(?) OR LOWER(nome) = LOWER(?)
         LIMIT 1'
    );
    $stmt->execute([$identificador, $identificador]);
    $usuario = $stmt->fetch();
    $senhaValida = $usuario && !empty($usuario['senha_hash'])
        && password_verify($senha, (string) $usuario['senha_hash']);

    if (!$senhaValida) {
        $stmt = $pdo->prepare('INSERT INTO tentativas_login (identificador_hash, ip_hash) VALUES (?, ?)');
        $stmt->execute([$identificadorHash, $ipHash]);
        redirecionar('index.php?erro=credenciais_invalidas');
    }

    if (!valorBooleano($usuario['ativo'])) {
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['usuario_bloqueado_id'] = (int) $usuario['id'];
        $_SESSION['usuario_bloqueado_nome'] = (string) $usuario['nome'];
        $_SESSION['usuario_bloqueado_email'] = (string) ($usuario['email'] ?? '');
        redirecionar('index.php?bloqueado=1');
    }

    $stmt = $pdo->prepare('DELETE FROM tentativas_login WHERE identificador_hash = ? AND ip_hash = ?');
    $stmt->execute([$identificadorHash, $ipHash]);

    session_regenerate_id(true);
    unset($_SESSION['usuario_bloqueado_id'], $_SESSION['usuario_bloqueado_nome'], $_SESSION['usuario_bloqueado_email']);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nome'] = (string) $usuario['nome'];
    $_SESSION['usuario_email'] = (string) ($usuario['email'] ?? '');
    $_SESSION['logado'] = true;
    $_SESSION['usuario_perfil'] = (string) $usuario['perfil'];

    garantirMatriculasPublicadas($pdo, (int) $usuario['id']);
    redirecionar('inicio.php');
} catch (Throwable $e) {
    registrarErro($pdo, 'autenticacao', $e);
    redirecionar('index.php?erro=erro_interno');
}
