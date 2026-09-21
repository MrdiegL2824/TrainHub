<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
exigirLogin();

$id = max(0, (int) ($_GET['id'] ?? 0));
$usuarioId = (int) $_SESSION['usuario_id'];
$stmt = $pdo->prepare('SELECT id, nome, descricao, cor FROM areas WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$area = $stmt->fetch();
if (!$area) {
    http_response_code(404);
    exit('Área não encontrada.');
}

if (!temPerfil('admin')) {
    garantirMatriculasPublicadas($pdo, $usuarioId);
}

$sql = "SELECT t.id, t.titulo, t.descricao, t.carga_horaria, t.data_inicio, t.data_fim, t.status,
               COUNT(DISTINCT CASE WHEN a.status = 'publicado' THEN a.id END) AS total_aulas,
               COUNT(DISTINCT CASE WHEN a.status = 'publicado' AND p.concluida = TRUE THEN a.id END) AS aulas_concluidas
        FROM treinamentos t
        LEFT JOIN aulas a ON a.treinamento_id = t.id
        LEFT JOIN progresso_aulas p ON p.aula_id = a.id AND p.usuario_id = ?
        WHERE t.area_id = ?";
$parametros = [$usuarioId, $id];
if (!temPerfil('admin')) {
    $sql .= " AND t.status = 'publicado'";
}
$sql .= ' GROUP BY t.id, t.titulo, t.descricao, t.carga_horaria, t.data_inicio, t.data_fim, t.status, t.criado_em ORDER BY CASE WHEN t.data_inicio IS NULL THEN 1 ELSE 0 END, t.data_inicio, t.titulo';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$treinamentos = $stmt->fetchAll();

$totalAulas = 0;
$aulasConcluidas = 0;
$totalHoras = 0.0;
foreach ($treinamentos as $treinamento) {
    $totalAulas += (int) $treinamento['total_aulas'];
    $aulasConcluidas += (int) $treinamento['aulas_concluidas'];
    $totalHoras += (float) $treinamento['carga_horaria'];
}
$percentual = $totalAulas > 0 ? min(100, (int) round(($aulasConcluidas / $totalAulas) * 100)) : 0;
$formatarData = static function (?string $valor): string {
    return $valor ? date('d/m/Y', strtotime($valor)) : 'A definir';
};

$continuarId = 0;
$stmt = $pdo->prepare("SELECT m.ultima_aula_id
                       FROM matriculas m
                       JOIN treinamentos t ON t.id = m.treinamento_id
                       WHERE m.usuario_id = ? AND t.area_id = ?
                       ORDER BY m.iniciado_em DESC
                       LIMIT 1");
$stmt->execute([$usuarioId, $id]);
$continuarId = (int) ($stmt->fetchColumn() ?: 0);
if ($continuarId < 1) {
    $stmt = $pdo->prepare("SELECT a.id
                           FROM aulas a
                           JOIN treinamentos t ON t.id = a.treinamento_id
                           LEFT JOIN progresso_aulas p ON p.aula_id = a.id AND p.usuario_id = ?
                           WHERE t.area_id = ? AND t.status = 'publicado' AND a.status = 'publicado'
                             AND COALESCE(p.concluida, FALSE) = FALSE
                           ORDER BY t.titulo, a.ordem, a.id
                           LIMIT 1");
    $stmt->execute([$usuarioId, $id]);
    $continuarId = (int) ($stmt->fetchColumn() ?: 0);
}
if ($continuarId < 1) {
    $stmt = $pdo->prepare("SELECT a.id
                           FROM aulas a
                           JOIN treinamentos t ON t.id = a.treinamento_id
                           WHERE t.area_id = ? AND t.status = 'publicado' AND a.status = 'publicado'
                           ORDER BY t.titulo, a.ordem, a.id
                           LIMIT 1");
    $stmt->execute([$id]);
    $continuarId = (int) ($stmt->fetchColumn() ?: 0);
}
$continuarUrl = $continuarId > 0 ? 'aula-template.php?id=' . $continuarId : ($treinamentos ? 'treinamento-detalhe.php?id=' . (int) $treinamentos[0]['id'] : '#');
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc($area['nome']); ?> - TrainHub</title>
    <link rel="stylesheet" href="css/app.css?v=1.0">
    <style>
        .area-hero{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:22px;align-items:stretch}.area-intro{padding:28px}.area-intro h1{margin:10px 0 12px;font-size:clamp(28px,4vw,44px)}.area-intro>p{max-width:820px;color:var(--th-muted);font-size:17px;line-height:1.65}.area-info{display:flex;flex-wrap:wrap;gap:9px;margin:22px 0}.area-progresso{display:grid;place-items:center;align-content:center;gap:10px;padding:24px;text-align:center;background:var(--th-card)}.area-progresso h2{margin:0;font-size:20px}.area-progresso p{margin:0;color:var(--th-muted)}.progresso-circular{--percent:0%;width:160px;height:160px;display:grid;place-items:center;border-radius:50%;background:conic-gradient(#315fe6 var(--percent),#647087 0);position:relative}.progresso-circular:after{content:'';position:absolute;inset:13px;border-radius:50%;background:var(--th-card)}.progresso-circular strong{position:relative;z-index:1;font-size:34px}.unidades{margin-top:22px}.unidades-cabecalho{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px}.unidades-cabecalho h2{margin:0}.unidades-filtros{display:flex;flex-wrap:wrap;gap:9px}.unidades-filtros input,.unidades-filtros select{min-height:38px;border:1px solid var(--th-border);border-radius:8px;background:var(--th-card);color:var(--th-text);padding:8px 11px}.unidades-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.unidade-card{position:relative;min-height:225px;display:flex;flex-direction:column;padding:20px;border-radius:14px;background:linear-gradient(135deg,#2f9998,#287d7c);overflow:hidden;text-decoration:none}.unidade-card:before{content:'';position:absolute;width:180px;height:180px;right:-65px;bottom:-85px;border:28px solid rgba(6,57,75,.22);border-radius:50%}.unidade-card>*{position:relative;z-index:1}.unidade-cabecalho{display:flex;justify-content:space-between;gap:12px}.unidade-card h3{margin:0;font-size:19px;line-height:1.3}.unidade-card p{color:#e6ffff;line-height:1.45}.unidade-percentual{flex:0 0 auto;width:54px;height:54px;display:grid;place-items:center;border:5px solid #e8edf7;border-right-color:#1e64ff;border-radius:50%;font-weight:700}.unidade-rodape{display:flex;flex-wrap:wrap;gap:7px;margin-top:auto}.unidade-meta{display:inline-flex;padding:4px 8px;border-radius:999px;background:rgba(12,28,45,.42);font-size:12px}.unidade-datas{display:flex;justify-content:space-between;gap:10px;margin-top:18px;color:#f4ffff;font-size:12px}.area-vazia{padding:42px 20px;text-align:center;color:var(--th-muted);background:var(--th-card);border-radius:12px}@media(max-width:900px){.area-hero{grid-template-columns:1fr}.area-progresso{min-height:270px}.unidades-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.area-intro{padding:21px}.unidades-cabecalho{align-items:flex-start;flex-direction:column}.unidades-filtros{width:100%}.unidades-filtros input,.unidades-filtros select{flex:1;min-width:0}.unidades-grid{grid-template-columns:1fr}.unidade-card{min-height:205px}}
    </style>
</head>
<body>
<header class="th-topbar"><a class="th-logo" href="inicio.php" title="Voltar para a área inicial"><img src="assets/logo.png" alt="TrainHub"></a><a class="th-avatar" href="perfil.php" aria-label="Perfil">●</a></header>
<main class="th-shell">
    <a class="th-back" href="inicio.php" onclick="if(history.length>1){history.back();return false;}" aria-label="Voltar">←</a>
    <section class="area-hero">
        <article class="th-panel area-intro">
            <span class="th-badge"><?php echo count($treinamentos); ?> treinamento<?php echo count($treinamentos) === 1 ? '' : 's'; ?></span>
            <h1><?php echo esc($area['nome']); ?></h1>
            <p><?php echo esc($area['descricao'] ?: 'Explore os treinamentos desta área e acompanhe sua evolução no TrainHub.'); ?></p>
            <div class="area-info"><span class="th-badge"><?php echo esc(rtrim(rtrim(number_format($totalHoras, 1, '.', ''), '0'), '.')); ?>h de conteúdo</span><span class="th-badge"><?php echo $aulasConcluidas; ?> de <?php echo $totalAulas; ?> aulas</span><span class="th-badge">Área de conhecimento</span></div>
            <?php if ($continuarId > 0): ?><a class="th-button" href="<?php echo esc($continuarUrl); ?>">Continuar de onde parou →</a><?php endif; ?>
        </article>
        <aside class="th-panel area-progresso">
            <h2>Meu progresso na área</h2><p><?php echo $aulasConcluidas; ?> de <?php echo $totalAulas; ?> aulas concluídas</p>
            <div class="progresso-circular" style="--percent:<?php echo $percentual; ?>%"><strong><?php echo $percentual; ?>%</strong></div>
        </aside>
    </section>
    <section class="th-panel unidades">
        <div class="unidades-cabecalho"><h2>Treinamentos da área</h2><div class="unidades-filtros"><input id="busca-unidades" type="search" placeholder="Pesquisar" aria-label="Pesquisar treinamentos"><select id="filtro-unidades" aria-label="Filtrar treinamentos"><option value="">Todos</option><option value="concluido">Concluídos</option><option value="andamento">Em andamento</option><option value="nao-iniciado">Não iniciados</option></select></div></div>
        <?php if (!$treinamentos): ?><div class="area-vazia">Nenhum treinamento publicado nesta área.</div><?php else: ?><div class="unidades-grid" id="lista-unidades"><?php foreach ($treinamentos as $treinamento): $total = (int) $treinamento['total_aulas']; $feitas = (int) $treinamento['aulas_concluidas']; $percentualTreinamento = $total > 0 ? min(100, (int) round(($feitas / $total) * 100)) : 0; $status = $percentualTreinamento >= 100 ? 'concluido' : ($percentualTreinamento > 0 ? 'andamento' : 'nao-iniciado'); $rotulo = $status === 'concluido' ? 'Concluído' : ($status === 'andamento' ? 'Em andamento' : 'Não iniciado'); ?><a class="unidade-card unidade-item" data-status="<?php echo esc($status); ?>" data-busca="<?php echo esc(mb_strtolower($treinamento['titulo'] . ' ' . $treinamento['descricao'])); ?>" href="treinamento-detalhe.php?id=<?php echo (int) $treinamento['id']; ?>"><div class="unidade-cabecalho"><h3><?php echo esc($treinamento['titulo']); ?></h3><span class="unidade-percentual"><?php echo $percentualTreinamento; ?>%</span></div><p><?php echo esc($treinamento['descricao'] ?: 'Conteúdo de aprendizagem desta área.'); ?></p><div class="unidade-rodape"><span class="unidade-meta"><?php echo esc($rotulo); ?></span><span class="unidade-meta"><?php echo esc($treinamento['carga_horaria']); ?>h</span><span class="unidade-meta"><?php echo $total; ?> aulas</span></div><div class="unidade-datas"><span>Começa: <?php echo esc($formatarData($treinamento['data_inicio'])); ?></span><span>Termina: <?php echo esc($formatarData($treinamento['data_fim'])); ?></span></div></a><?php endforeach; ?></div><?php endif; ?>
    </section>
</main>
<script>
const buscaUnidades=document.getElementById('busca-unidades');const filtroUnidades=document.getElementById('filtro-unidades');function filtrarUnidades(){const termo=(buscaUnidades?.value||'').toLocaleLowerCase('pt-BR');const filtro=filtroUnidades?.value||'';document.querySelectorAll('.unidade-item').forEach(item=>{item.hidden=!item.dataset.busca.includes(termo)||!!filtro&&item.dataset.status!==filtro;});}buscaUnidades?.addEventListener('input',filtrarUnidades);filtroUnidades?.addEventListener('change',filtrarUnidades);
</script>
</body>
</html>
