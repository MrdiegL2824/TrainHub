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

if ($usuarioId < 1 || $mensagem === '') {
    redirecionar('index.php?erro=suporte_campos');
}

$stmt = $pdo->prepare("INSERT INTO pedidos_suporte (usuario_id, mensagem) VALUES (?, ?)");
$stmt->execute([$usuarioId, $mensagem]);
$stmt = $pdo->prepare("INSERT INTO atividades (usuario_id, acao, descricao) VALUES (?, 'enviou um pedido de suporte', 'pedido de suporte')");
$stmt->execute([$usuarioId]);
unset($_SESSION['usuario_bloqueado_id'], $_SESSION['usuario_bloqueado_nome'], $_SESSION['usuario_bloqueado_email']);
redirecionar('index.php?suporte=enviado');
