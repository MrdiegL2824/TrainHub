<?php
declare(strict_types=1);

require_once 'config.php';
exigirAdmin();

$busca = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT l.id, l.contexto, l.mensagem, l.criado_em, u.nome FROM logs_erros l LEFT JOIN usuarios u ON u.id = l.usuario_id';
$params = [];
if ($busca !== '') { $sql .= ' WHERE LOWER(l.contexto) LIKE LOWER(?) OR LOWER(l.mensagem) LIKE LOWER(?)'; $params = ['%' . $busca . '%', '%' . $busca . '%']; }
$sql .= ' ORDER BY l.criado_em DESC LIMIT 100';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $logs = $stmt->fetchAll();
?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TrainHub - Log de erros</title><link rel="stylesheet" href="css/app.css?v=1.0"></head><body><header class="th-topbar"><a class="th-logo" href="dashboard.php"><img src="assets/logo.png" alt="TrainHub"></a><a class="th-avatar" href="perfil.php">●</a></header><main class="th-shell"><a class="th-back" href="dashboard.php">←</a><div class="th-heading"><div><h1>Log de erros</h1><p>Últimos erros registrados pela aplicação, sem informações sensíveis.</p></div></div><form class="th-toolbar"><input class="th-input" name="q" value="<?php echo esc($busca); ?>" placeholder="Pesquisar contexto ou mensagem"><button class="th-button">Pesquisar</button></form><section class="th-panel"><?php if(!$logs): ?><p class="th-empty">Nenhum erro registrado.</p><?php else: ?><div class="th-table-wrap"><table class="th-table"><thead><tr><th>Data</th><th>Contexto</th><th>Mensagem</th><th>Usuário</th></tr></thead><tbody><?php foreach($logs as $log): ?><tr><td><?php echo esc($log['criado_em']); ?></td><td><strong><?php echo esc($log['contexto']); ?></strong></td><td><?php echo esc($log['mensagem']); ?></td><td><?php echo esc($log['nome'] ?: 'Sistema'); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section></main></body></html>
