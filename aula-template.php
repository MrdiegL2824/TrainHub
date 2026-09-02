<?php
require_once 'config.php';
exigirLogin();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT a.*, t.id AS treinamento_id, t.titulo AS treinamento_titulo FROM aulas a JOIN treinamentos t ON t.id = a.treinamento_id WHERE a.id = ?');
$stmt->execute([$id]);
$aula = $stmt->fetch();
if (!$aula) { http_response_code(404); exit('Aula não encontrada.'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc($aula['titulo']); ?> - TrainHub</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-100 min-h-screen text-slate-800"><main class="max-w-4xl mx-auto p-6 md:p-10">
    <a href="treinamento-detalhe.php?id=<?php echo (int) $aula['treinamento_id']; ?>" class="text-blue-700 font-semibold">← <?php echo esc($aula['treinamento_titulo']); ?></a>
    <article class="bg-white rounded-3xl p-7 md:p-10 shadow-sm mt-5">
        <p class="text-sm text-blue-700 font-semibold">Aula <?php echo (int) $aula['ordem']; ?> · <?php echo (int) $aula['duracao_min']; ?> min</p>
        <h1 class="text-4xl font-bold mt-3"><?php echo esc($aula['titulo']); ?></h1>
        <?php if ($aula['objetivo']): ?><div class="mt-6 rounded-2xl bg-blue-50 p-5"><h2 class="font-bold">Objetivo</h2><p class="mt-2 whitespace-pre-line"><?php echo esc($aula['objetivo']); ?></p></div><?php endif; ?>
        <div class="mt-8 border-t pt-6"><h2 class="text-2xl font-bold">Conteúdo</h2><div class="mt-4 whitespace-pre-line leading-7"><?php echo esc($aula['conteudo']); ?></div></div>
    </article>
</main></body></html>
