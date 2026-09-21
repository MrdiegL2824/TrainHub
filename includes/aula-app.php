<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
exigirLogin();

$id = max(0, (int) ($_GET['id'] ?? 0));
$usuarioId = (int) $_SESSION['usuario_id'];
if (!temPerfil('admin')) {
    garantirMatriculasPublicadas($pdo, $usuarioId);
}
$stmt = $pdo->prepare('SELECT a.*, t.id AS treinamento_id, t.titulo AS treinamento_titulo, t.status AS treinamento_status FROM aulas a JOIN treinamentos t ON t.id = a.treinamento_id WHERE a.id = ? LIMIT 1');
$stmt->execute([$id]);
$aula = $stmt->fetch();
if (!$aula || (!temPerfil('admin') && ($aula['status'] !== 'publicado' || $aula['treinamento_status'] !== 'publicado'))) {
    http_response_code(404);
    exit('Aula não encontrada.');
}

$stmt = $pdo->prepare('UPDATE matriculas SET ultima_aula_id = ? WHERE usuario_id = ? AND treinamento_id = ?');
$stmt->execute([$id, $usuarioId, (int) $aula['treinamento_id']]);
$stmt = $pdo->prepare('SELECT id, nome, arquivo, tipo FROM materiais WHERE aula_id = ? ORDER BY nome');
$stmt->execute([$id]);
$materiais = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT concluida FROM progresso_aulas WHERE usuario_id = ? AND aula_id = ?');
$stmt->execute([$usuarioId, $id]);
$concluida = valorBooleano($stmt->fetchColumn());
$statusAulas = temPerfil('admin') ? '' : " AND status='publicado'";
$stmt = $pdo->prepare('SELECT id, titulo, ordem, status FROM aulas WHERE treinamento_id = ?' . $statusAulas . ' ORDER BY ordem, id');
$stmt->execute([(int) $aula['treinamento_id']]);
$aulasCurso = $stmt->fetchAll();
$anterior = $proxima = null;
foreach ($aulasCurso as $indice => $item) {
    if ((int) $item['id'] === $id) {
        $anterior = $aulasCurso[$indice - 1] ?? null;
        $proxima = $aulasCurso[$indice + 1] ?? null;
        break;
    }
}
$stmt = $pdo->prepare("SELECT COUNT(*) FROM aulas WHERE treinamento_id=? AND status='publicado'");
$stmt->execute([(int) $aula['treinamento_id']]);
$totalAulas = (int) $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM aulas a JOIN progresso_aulas p ON p.aula_id=a.id AND p.usuario_id=? WHERE a.treinamento_id=? AND a.status='publicado' AND p.concluida=TRUE");
$stmt->execute([$usuarioId, (int) $aula['treinamento_id']]);
$aulasConcluidas = (int) $stmt->fetchColumn();
$percentual = $totalAulas > 0 ? min(100, (int) round($aulasConcluidas / $totalAulas * 100)) : 0;
$iconeMaterial = static function (string $tipo): array {
    $tipo = strtolower(trim($tipo));
    if ($tipo === 'pdf') {
        return ['▣', 'Documento PDF'];
    }
    if (in_array($tipo, ['video', 'mp4', 'youtube'], true)) {
        return ['▶', 'Vídeo'];
    }
    if (in_array($tipo, ['link', 'url', 'site'], true)) {
        return ['↗', 'Link externo'];
    }
    return ['•', 'Material'];
};
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc($aula['titulo']); ?> - TrainHub</title><link rel="stylesheet" href="css/app.css?v=1.0">
    <style>
        .aula-layout{display:grid;grid-template-columns:minmax(0,1fr) 310px;gap:20px}.aula-conteudo{min-height:520px;padding:28px}.aula-conteudo h1{margin:8px 0 16px;font-size:clamp(27px,4vw,42px)}.aula-texto{margin-top:24px;padding-top:22px;border-top:1px solid var(--th-border);white-space:pre-line;line-height:1.7}.aula-objetivo{padding:16px;border-radius:10px;background:var(--th-card)}.aula-objetivo strong{display:block;margin-bottom:6px}.aula-progresso{display:flex;align-items:center;gap:12px;margin:20px 0;padding:14px;border-radius:10px;background:var(--th-card)}.aula-progresso .th-progress{flex:1}.aula-lateral{padding:0;overflow:hidden}.aula-lateral h2{margin:0;padding:18px;background:var(--th-card)}.aula-item{display:flex;align-items:center;justify-content:space-between;padding:15px 18px;border-top:1px solid var(--th-border);text-decoration:none}.aula-item.ativa{background:#697181}.aula-item small{color:var(--th-muted)}.materiais{margin-top:24px}.material{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:8px;padding:12px;border-radius:9px;background:var(--th-card);text-decoration:none}.material:hover{background:var(--th-card-2)}.material-info{display:flex;align-items:center;gap:11px;min-width:0}.material-info strong{display:block}.material-info small{display:block;margin-top:3px}.material-icon{width:34px;height:34px;display:grid;place-items:center;flex:0 0 auto;border-radius:8px;background:#3159b2;color:#fff;font-size:17px;font-weight:700}.aula-navegacao{display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;margin-top:24px}@media(max-width:800px){.aula-layout{grid-template-columns:1fr}.aula-lateral{order:-1}.aula-conteudo{min-height:0;padding:21px}}@media(max-width:560px){.aula-progresso{align-items:flex-start;flex-direction:column}.aula-progresso .th-progress{width:100%}}
    </style>
</head>
<body>
<header class="th-topbar"><a class="th-logo" href="inicio.php"><img src="assets/logo.png" alt="TrainHub"></a><a class="th-avatar" href="perfil.php" aria-label="Perfil">●</a></header>
<main class="th-shell">
    <a class="th-back" href="treinamento-detalhe.php?id=<?php echo (int) $aula['treinamento_id']; ?>" onclick="if(history.length>1){history.back();return false;}" aria-label="Voltar">←</a>
    <?php if (isset($_GET['progresso'])): ?><p class="th-alert th-alert-success">Progresso atualizado.</p><?php endif; ?>
    <?php if (isset($_GET['erro'])): ?><p class="th-alert th-alert-error">Não foi possível atualizar o progresso.</p><?php endif; ?>
    <div class="aula-layout">
        <article class="th-panel aula-conteudo">
            <span class="th-badge">Aula <?php echo (int) $aula['ordem']; ?> · <?php echo (int) $aula['duracao_min']; ?> min</span>
            <h1><?php echo esc($aula['titulo']); ?></h1>
            <p class="th-meta">Treinamento: <?php echo esc($aula['treinamento_titulo']); ?></p>
            <div class="aula-progresso"><strong>Meu progresso: <?php echo $percentual; ?>%</strong><div class="th-progress"><span style="width:<?php echo $percentual; ?>%"></span></div></div>
            <?php if ($aula['objetivo']): ?><div class="aula-objetivo"><strong>Objetivo</strong><?php echo nl2br(esc($aula['objetivo'])); ?></div><?php endif; ?>
            <div class="aula-texto"><?php echo esc($aula['conteudo']); ?></div>
            <section class="materiais"><div class="th-card-row"><h2>Materiais da aula</h2><?php if (temPerfil('admin')): ?><a href="materiais.php?aula_id=<?php echo $id; ?>">Gerenciar</a><?php endif; ?></div><?php if (!$materiais): ?><p class="th-meta">Nenhum material disponível para esta aula.</p><?php else: ?><?php foreach ($materiais as $material): $icone = $iconeMaterial((string) $material['tipo']); ?><a class="material" href="<?php echo esc($material['arquivo']); ?>" target="_blank" rel="noopener noreferrer"><span class="material-info"><span class="material-icon" aria-hidden="true"><?php echo esc($icone[0]); ?></span><span><strong><?php echo esc($material['nome']); ?></strong><small class="th-meta"><?php echo esc($icone[1]); ?></small></span></span><span>Abrir →</span></a><?php endforeach; ?><?php endif; ?></section>
            <form method="post" action="api/progresso.php" style="margin-top:24px"><?php echo csrfInput(); ?><input type="hidden" name="aula_id" value="<?php echo $id; ?>"><input type="hidden" name="acao" value="<?php echo $concluida ? 'reabrir' : 'concluir'; ?>"><button class="th-button <?php echo $concluida ? 'th-button-secondary' : 'th-button-success'; ?>" type="submit"><?php echo $concluida ? 'Marcar como não concluída' : '✓ Concluir aula'; ?></button></form>
            <nav class="aula-navegacao"><?php if ($anterior): ?><a class="th-button th-button-secondary" href="aula-template.php?id=<?php echo (int) $anterior['id']; ?>">← <?php echo esc($anterior['titulo']); ?></a><?php else: ?><span></span><?php endif; ?><?php if ($proxima): ?><a class="th-button" href="aula-template.php?id=<?php echo (int) $proxima['id']; ?>"><?php echo esc($proxima['titulo']); ?> →</a><?php else: ?><a class="th-button" href="avaliacoes.php?treinamento_id=<?php echo (int) $aula['treinamento_id']; ?>">Ir para avaliações →</a><?php endif; ?></nav>
        </article>
        <aside class="th-panel aula-lateral"><h2>☆ <?php echo esc($aula['treinamento_titulo']); ?></h2><?php foreach ($aulasCurso as $item): ?><a class="aula-item <?php echo (int) $item['id'] === $id ? 'ativa' : ''; ?>" href="aula-template.php?id=<?php echo (int) $item['id']; ?>"><span><?php echo (int) $item['ordem']; ?>. <?php echo esc($item['titulo']); ?></span><small><?php echo (int) $item['id'] === $id ? '●' : '›'; ?></small></a><?php endforeach; ?></aside>
    </div>
</main>
</body>
</html>
