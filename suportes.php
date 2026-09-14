<?php
declare(strict_types=1);

require_once 'config.php';
exigirAdmin();

$erro = '';
$sucesso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedidoId = (int) ($_POST['pedido_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'pendente');
    $resposta = trim((string) ($_POST['resposta'] ?? ''));
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'A solicitação expirou. Atualize a página.';
    } elseif ($pedidoId < 1 || !in_array($status, ['pendente', 'em_atendimento', 'resolvido'], true)) {
        $erro = 'Pedido ou status inválido.';
    } elseif ($status === 'resolvido' && $resposta === '') {
        $erro = 'Escreva uma resposta antes de concluir o pedido.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT u.nome FROM pedidos_suporte s JOIN usuarios u ON u.id = s.usuario_id WHERE s.id = ? LIMIT 1');
            $stmt->execute([$pedidoId]);
            $nome = $stmt->fetchColumn();
            if ($nome === false) {
                $erro = 'Pedido não encontrado.';
            } else {
                $stmt = $pdo->prepare('UPDATE pedidos_suporte SET status = ?, resposta = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?');
                $stmt->execute([$status, $resposta !== '' ? $resposta : null, $pedidoId]);
                registrarAtividade($pdo, (int) $_SESSION['usuario_id'], 'atualizou o pedido de suporte de', (string) $nome);
                $sucesso = 'Pedido atualizado.';
            }
        } catch (Throwable $e) {
            registrarErro($pdo, 'atualizacao de suporte', $e);
            $erro = 'Não foi possível atualizar o pedido.';
        }
    }
}

$statusFiltro = (string) ($_GET['status'] ?? '');
$sql = 'SELECT s.*, u.nome, u.email FROM pedidos_suporte s JOIN usuarios u ON u.id = s.usuario_id';
$params = [];
if (in_array($statusFiltro, ['pendente', 'em_atendimento', 'resolvido'], true)) { $sql .= ' WHERE s.status = ?'; $params[] = $statusFiltro; }
$sql .= ' ORDER BY CASE s.status WHEN \'pendente\' THEN 1 WHEN \'em_atendimento\' THEN 2 ELSE 3 END, s.criado_em DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $pedidos = $stmt->fetchAll();
?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TrainHub - Pedidos de suporte</title><link rel="stylesheet" href="css/app.css?v=1.0"><style>.pedido{display:grid;gap:12px}.pedido-mensagem{margin:0;padding:13px;border-radius:9px;background:var(--th-card-2);color:var(--th-text)!important;white-space:pre-line}.pedido-form{display:grid;grid-template-columns:minmax(0,1fr) 180px auto;gap:9px;align-items:end}@media(max-width:760px){.pedido-form{grid-template-columns:1fr}}</style></head><body><header class="th-topbar"><a class="th-logo" href="dashboard.php"><img src="assets/logo.png" alt="TrainHub"></a><a class="th-avatar" href="perfil.php">●</a></header><main class="th-shell"><a class="th-back" href="dashboard.php">←</a><div class="th-heading"><div><h1>Pedidos de suporte</h1><p>Analise as solicitações e registre a resposta enviada ao cliente.</p></div></div><?php if ($erro): ?><p class="th-alert th-alert-error"><?php echo esc($erro); ?></p><?php endif; ?><?php if ($sucesso): ?><p class="th-alert th-alert-success"><?php echo esc($sucesso); ?></p><?php endif; ?><form class="th-toolbar" method="get"><select class="th-input" name="status" onchange="this.form.submit()"><option value="">Todos os status</option><option value="pendente" <?php echo $statusFiltro==='pendente'?'selected':''; ?>>Pendentes</option><option value="em_atendimento" <?php echo $statusFiltro==='em_atendimento'?'selected':''; ?>>Em atendimento</option><option value="resolvido" <?php echo $statusFiltro==='resolvido'?'selected':''; ?>>Resolvidos</option></select></form><section class="th-list"><?php if(!$pedidos): ?><p class="th-empty">Nenhum pedido de suporte encontrado.</p><?php endif; ?><?php foreach($pedidos as $pedido): ?><article class="th-panel pedido"><div class="th-card-row"><div><h2><?php echo esc($pedido['nome']); ?></h2><span class="th-meta"><?php echo esc($pedido['email']); ?> · <?php echo esc($pedido['criado_em']); ?></span></div><span class="th-badge <?php echo $pedido['status']==='resolvido'?'th-badge-success':($pedido['status']==='pendente'?'th-badge-danger':'th-badge-warning'); ?>"><?php echo esc(str_replace('_',' ',$pedido['status'])); ?></span></div><p class="pedido-mensagem"><?php echo esc($pedido['mensagem']); ?></p><form class="pedido-form" method="post"><?php echo csrfInput(); ?><input type="hidden" name="pedido_id" value="<?php echo (int)$pedido['id']; ?>"><label class="th-field">Resposta<textarea name="resposta" maxlength="4000" placeholder="Escreva a resposta ao cliente..."><?php echo esc($pedido['resposta'] ?? ''); ?></textarea></label><label class="th-field">Status<select name="status"><option value="pendente" <?php echo $pedido['status']==='pendente'?'selected':''; ?>>Pendente</option><option value="em_atendimento" <?php echo $pedido['status']==='em_atendimento'?'selected':''; ?>>Em atendimento</option><option value="resolvido" <?php echo $pedido['status']==='resolvido'?'selected':''; ?>>Resolvido</option></select></label><button class="th-button">Salvar</button></form></article><?php endforeach; ?></section></main></body></html>
