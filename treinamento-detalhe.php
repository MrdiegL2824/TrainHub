<?php
require_once __DIR__ . '/includes/treinamento-detalhe-app.php';
exit;
require_once 'config.php';
exigirLogin();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT t.*, a.nome AS area_nome, u.nome AS criador_nome FROM treinamentos t JOIN areas a ON a.id=t.area_id LEFT JOIN usuarios u ON u.id=t.criado_por WHERE t.id=?');
$stmt->execute([$id]);
$treinamento = $stmt->fetch();
if (!$treinamento) { http_response_code(404); exit('Treinamento não encontrado.'); }
$aulasStmt = $pdo->prepare('SELECT id, titulo, objetivo, ordem, duracao_min FROM aulas WHERE treinamento_id=? ORDER BY ordem, id');
$aulasStmt->execute([$id]);
$aulas = $aulasStmt->fetchAll();
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc($treinamento['titulo']); ?> - TrainHub</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-slate-100 min-h-screen text-slate-800"><main class="max-w-4xl mx-auto p-6 md:p-10"><a href="treinamentos.php" class="text-blue-700 font-semibold">← Treinamentos</a><article class="bg-white rounded-3xl p-7 md:p-10 shadow-sm mt-5"><span class="text-blue-700 font-semibold"><?php echo esc($treinamento['area_nome']); ?></span><h1 class="text-4xl font-bold mt-3"><?php echo esc($treinamento['titulo']); ?></h1><p class="text-slate-600 text-lg mt-5"><?php echo esc($treinamento['descricao'] ?: 'Este treinamento ainda não possui descrição.'); ?></p><div class="flex flex-wrap gap-3 mt-6 text-sm"><span class="bg-slate-100 rounded-full px-4 py-2"><?php echo esc($treinamento['carga_horaria']); ?> horas</span><span class="bg-slate-100 rounded-full px-4 py-2"><?php echo esc($treinamento['status']); ?></span><span class="bg-slate-100 rounded-full px-4 py-2">Criado por <?php echo esc($treinamento['criador_nome'] ?: 'equipe'); ?></span></div><hr class="my-8"><div class="flex justify-between items-center gap-4"><h2 class="text-2xl font-bold">Aulas</h2><a href="aulas.php?treinamento_id=<?php echo (int) $treinamento['id']; ?>" class="text-blue-700 font-semibold">+ Cadastrar aula</a></div><?php if (!$aulas): ?><p class="text-slate-500 mt-3">Nenhuma aula cadastrada neste treinamento.</p><?php else: ?><ol class="mt-4 space-y-3"><?php foreach ($aulas as $aula): ?><li class="rounded-xl bg-slate-50 p-4 flex justify-between gap-4"><div><strong><?php echo esc($aula['ordem']); ?>. <?php echo esc($aula['titulo']); ?></strong><p class="text-slate-500 text-sm mt-1"><?php echo esc($aula['objetivo'] ?: 'Conteúdo disponível para estudo.'); ?></p></div><a href="aula-template.php?id=<?php echo (int) $aula['id']; ?>" class="text-blue-700 font-semibold whitespace-nowrap">Estudar →</a></li><?php endforeach; ?></ol><?php endif; ?></article><a href="logout.php" class="inline-block mt-6 text-slate-600">Sair</a></main></body></html>
