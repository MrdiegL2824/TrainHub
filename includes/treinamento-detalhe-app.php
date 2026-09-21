<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
exigirLogin();

$id = max(0, (int) ($_GET['id'] ?? 0));
$usuarioId = (int) $_SESSION['usuario_id'];
$stmt = $pdo->prepare('SELECT t.*, a.nome AS area_nome, u.nome AS criador_nome FROM treinamentos t JOIN areas a ON a.id=t.area_id LEFT JOIN usuarios u ON u.id=t.criado_por WHERE t.id=?');
$stmt->execute([$id]);
$treinamento = $stmt->fetch();
if (!$treinamento || (!temPerfil('admin') && $treinamento['status'] !== 'publicado')) {
    http_response_code(404);
    exit('Treinamento não encontrado.');
}
if (!temPerfil('admin')) {
    garantirMatriculasPublicadas($pdo, $usuarioId);
}

$sqlAulas = 'SELECT a.id, a.titulo, a.objetivo, a.ordem, a.duracao_min, a.status, COUNT(DISTINCT m.id) AS total_materiais, MAX(CASE WHEN p.concluida=TRUE THEN 1 ELSE 0 END) AS concluida FROM aulas a LEFT JOIN progresso_aulas p ON p.aula_id=a.id AND p.usuario_id=? LEFT JOIN materiais m ON m.aula_id=a.id WHERE a.treinamento_id=?';
if (!temPerfil('admin')) {
    $sqlAulas .= " AND a.status='publicado'";
}
$sqlAulas .= ' GROUP BY a.id, a.titulo, a.objetivo, a.ordem, a.duracao_min, a.status ORDER BY a.ordem, a.id';
$stmt = $pdo->prepare($sqlAulas);
$stmt->execute([$usuarioId, $id]);
$aulas = $stmt->fetchAll();

$total = 0;
$concluidas = 0;
foreach ($aulas as $aula) {
    if ($aula['status'] === 'publicado') {
        $total++;
        $concluidas += valorBooleano($aula['concluida']) ? 1 : 0;
    }
}
$percentual = $total ? min(100, (int) round($concluidas / $total * 100)) : 0;
$continuarId = 0;
$stmt = $pdo->prepare('SELECT ultima_aula_id FROM matriculas WHERE usuario_id=? AND treinamento_id=? LIMIT 1');
$stmt->execute([$usuarioId, $id]);
$continuarId = (int) ($stmt->fetchColumn() ?: 0);
if ($continuarId < 1) {
    foreach ($aulas as $aula) {
        if ($aula['status'] === 'publicado' && !valorBooleano($aula['concluida'])) {
            $continuarId = (int) $aula['id'];
            break;
        }
    }
}
if ($continuarId < 1 && $aulas) {
    $continuarId = (int) $aulas[0]['id'];
}
$formatarData = static function (?string $valor): string {
    return $valor ? date('d/m/Y', strtotime($valor)) : 'A definir';
};
$stmt = $pdo->prepare('SELECT id, titulo, nota_minima FROM avaliacoes WHERE treinamento_id=? AND ativa=TRUE ORDER BY criado_em');
$stmt->execute([$id]);
$avaliacoes = $stmt->fetchAll();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo esc($treinamento['titulo']); ?> - TrainHub</title><link rel="stylesheet" href="css/app.css?v=1.0">
    <style>
        .curso-hero{display:grid;grid-template-columns:minmax(0,1fr) 270px;gap:22px}.curso-intro{padding:28px}.curso-intro h1{margin:10px 0 12px;font-size:clamp(28px,4vw,43px)}.curso-descricao{max-width:860px;color:var(--th-muted);font-size:17px;line-height:1.65}.curso-info{display:flex;flex-wrap:wrap;gap:9px;margin:21px 0}.curso-datas{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px;margin:20px 0;padding:17px;border-radius:10px;background:var(--th-card)}.curso-datas strong{display:block;margin-bottom:5px}.curso-progresso{display:grid;place-items:center;align-content:center;gap:10px;padding:24px;text-align:center;background:var(--th-card)}.curso-progresso h2{margin:0;font-size:20px}.curso-progresso p{margin:0;color:var(--th-muted)}.progresso-circular{--percent:0%;width:155px;height:155px;display:grid;place-items:center;border-radius:50%;background:conic-gradient(#315fe6 var(--percent),#647087 0);position:relative}.progresso-circular:after{content:'';position:absolute;inset:13px;border-radius:50%;background:var(--th-card)}.progresso-circular strong{position:relative;z-index:1;font-size:34px}.curso-unidades{margin-top:22px}.aula-linha{display:grid;grid-template-columns:38px minmax(0,1fr) auto;gap:13px;align-items:center}.aula-numero{width:36px;height:36px;display:grid;place-items:center;border-radius:50%;background:var(--th-card-2);font-weight:700}.aula-linha.concluida .aula-numero{background:#1d704d}.aula-linha h3{margin:0 0 5px}.avaliacoes-lista{margin-top:22px}.th-button-continue{margin-top:4px}@media(max-width:800px){.curso-hero{grid-template-columns:1fr}.curso-progresso{min-height:260px}.aula-linha{grid-template-columns:36px minmax(0,1fr)}}@media(max-width:560px){.curso-intro{padding:21px}.curso-datas{grid-template-columns:1fr}.aula-linha{align-items:start}.aula-linha .th-button{grid-column:2;justify-self:start}}
    </style>
</head>
<body>
<header class="th-topbar"><a class="th-logo" href="inicio.php"><img src="assets/logo.png" alt="TrainHub"></a><a class="th-avatar" href="perfil.php" aria-label="Perfil">●</a></header>
<main class="th-shell">
    <a class="th-back" href="<?php echo temPerfil('admin') ? 'treinamentos.php' : 'inicio.php'; ?>" onclick="if(history.length>1){history.back();return false;}" aria-label="Voltar">←</a>
    <section class="curso-hero">
        <article class="th-panel curso-intro">
            <span class="th-badge"><?php echo esc($treinamento['area_nome']); ?></span>
            <h1><?php echo esc($treinamento['titulo']); ?></h1>
            <p class="curso-descricao"><?php echo esc($treinamento['descricao'] ?: 'Este treinamento ainda não possui descrição.'); ?></p>
            <div class="curso-info"><span class="th-badge"><?php echo esc($treinamento['carga_horaria']); ?> horas</span><span class="th-badge <?php echo $treinamento['status'] === 'publicado' ? 'th-badge-success' : 'th-badge-warning'; ?>"><?php echo esc($treinamento['status']); ?></span><span class="th-badge"><?php echo $concluidas; ?> de <?php echo $total; ?> aulas</span></div>
            <div class="curso-datas"><div><strong>Começa em</strong><span><?php echo esc($formatarData($treinamento['data_inicio'] ?? null)); ?></span></div><div><strong>Termina em</strong><span><?php echo esc($formatarData($treinamento['data_fim'] ?? null)); ?></span></div></div>
            <?php if ($continuarId > 0): ?><a class="th-button th-button-continue" href="aula-template.php?id=<?php echo $continuarId; ?>">Continuar de onde parou →</a><?php endif; ?>
        </article>
        <aside class="th-panel curso-progresso">
            <h2>Meu progresso no curso</h2><p><?php echo $concluidas; ?> de <?php echo $total; ?> aulas completas</p>
            <div class="progresso-circular" style="--percent:<?php echo $percentual; ?>%"><strong><?php echo $percentual; ?>%</strong></div>
        </aside>
    </section>
    <section class="th-panel curso-unidades">
        <div class="th-card-row"><h2>Unidades curriculares</h2><?php if (temPerfil('admin')): ?><a class="th-button" href="aulas.php?treinamento_id=<?php echo $id; ?>">+ Cadastrar aula</a><?php endif; ?></div>
        <div class="th-list" style="margin-top:14px">
            <?php if (!$aulas): ?><p class="th-empty">Nenhuma aula disponível neste treinamento.</p><?php endif; ?>
            <?php foreach ($aulas as $aula): $feita = valorBooleano($aula['concluida']); ?>
                <article class="th-card aula-linha <?php echo $feita ? 'concluida' : ''; ?>"><span class="aula-numero"><?php echo $feita ? '✓' : (int) $aula['ordem']; ?></span><div><h3><?php echo esc($aula['titulo']); ?></h3><p class="th-meta"><?php echo esc($aula['objetivo'] ?: 'Conteúdo disponível para estudo.'); ?> · <?php echo (int) $aula['duracao_min']; ?> min · <?php echo (int) $aula['total_materiais']; ?> material(is)</p></div><a class="th-button" href="aula-template.php?id=<?php echo (int) $aula['id']; ?>"><?php echo $feita ? 'Revisar' : 'Estudar'; ?> →</a></article>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="th-panel avaliacoes-lista">
        <div class="th-card-row"><h2>Avaliações</h2><a class="th-button th-button-secondary" href="avaliacoes.php?treinamento_id=<?php echo $id; ?>">Ver todas</a></div>
        <?php if (!$avaliacoes): ?><p class="th-meta">Nenhuma avaliação disponível.</p><?php else: ?><div class="th-list" style="margin-top:12px"><?php foreach ($avaliacoes as $avaliacao): ?><div class="th-card th-card-row"><span><strong><?php echo esc($avaliacao['titulo']); ?></strong><small class="th-meta">Nota mínima <?php echo esc($avaliacao['nota_minima']); ?></small></span><a class="th-button" href="avaliacoes.php?id=<?php echo (int) $avaliacao['id']; ?>">Responder</a></div><?php endforeach; ?></div><?php endif; ?>
    </section>
</main>
</body>
</html>
