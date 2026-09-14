<?php
require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrf($_POST['csrf_token'] ?? null)) {
    redirecionar('index.php?erro=solicitacao');
}

$usuarioId = (int) ($_SESSION['usuario_bloqueado_id'] ?? 0);
if ($usuarioId < 1 && usuarioLogado()) {
    $usuarioId = (int) $_SESSION['usuario_id'];
}
$mensagem = trim((string) ($_POST['mensagem'] ?? ''));

if ($usuarioId < 1 || $mensagem === '' || mb_strlen($mensagem) > 2000) {
    redirecionar('index.php?erro=suporte_campos');
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos_suporte WHERE usuario_id = ? AND status <> 'resolvido'");
    $stmt->execute([$usuarioId]);
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO pedidos_suporte (usuario_id, mensagem) VALUES (?, ?)');
        $stmt->execute([$usuarioId, $mensagem]);
        registrarAtividade($pdo, $usuarioId, 'enviou um pedido de suporte', 'acesso bloqueado');
        $pdo->commit();
    }
    unset($_SESSION['usuario_bloqueado_id'], $_SESSION['usuario_bloqueado_nome'], $_SESSION['usuario_bloqueado_email']);
    redirecionar('index.php?suporte=enviado');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    registrarErro($pdo, 'pedido de suporte', $e);
    redirecionar('index.php?erro=erro_interno');
}
