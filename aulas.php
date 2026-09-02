<?php
require_once 'config.php';
exigirLogin();

$mensagem = '';
$erro = '';
$treinamentoSelecionado = (int) ($_GET['treinamento_id'] ?? $_POST['treinamento_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } else {
        $treinamentoId = (int) ($_POST['treinamento_id'] ?? 0);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $objetivo = trim((string) ($_POST['objetivo'] ?? ''));
        $conteudo = trim((string) ($_POST['conteudo'] ?? ''));
        $duracao = max(0, (int) ($_POST['duracao_min'] ?? 0));
        $ordem = max(1, (int) ($_POST['ordem'] ?? 1));

        if ($treinamentoId < 1 || $titulo === '' || $conteudo === '') {
            $erro = 'Selecione um treinamento e informe título e conteúdo.';
        } else {
            $verifica = $pdo->prepare('SELECT id FROM treinamentos WHERE id = ?');
            $verifica->execute([$treinamentoId]);
            if (!$verifica->fetchColumn()) {
                $erro = 'O treinamento selecionado não existe.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO aulas (treinamento_id, titulo, objetivo, conteudo, duracao_min, ordem) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$treinamentoId, $titulo, $objetivo, $conteudo, $duracao, $ordem]);
                redirecionar('aulas.php?treinamento_id=' . $treinamentoId . '&salvo=1');
            }
        }
        $treinamentoSelecionado = $treinamentoId;
    }
}

if (isset($_GET['salvo'])) {
    $mensagem = 'Aula cadastrada com sucesso.';
}

$treinamentos = $pdo->query('SELECT id, titulo FROM treinamentos ORDER BY titulo')->fetchAll();
$sql = 'SELECT a.id, a.titulo, a.objetivo, a.duracao_min, a.ordem, t.id AS treinamento_id, t.titulo AS treinamento_titulo FROM aulas a JOIN treinamentos t ON t.id = a.treinamento_id';
$parametros = [];
if ($treinamentoSelecionado > 0) {
    $sql .= ' WHERE a.treinamento_id = ?';
    $parametros[] = $treinamentoSelecionado;
}
$sql .= ' ORDER BY t.titulo, a.ordem, a.id';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$aulas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainHub - Aulas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">
<main class="max-w-6xl mx-auto p-6 md:p-10">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
        <div><a href="dashboard.php" class="text-blue-700 font-semibold">← Painel</a><h1 class="text-3xl font-bold mt-3">Aulas</h1><p class="text-slate-500">Cadastre e organize o conteúdo dos treinamentos.</p></div>
        <a href="logout.php" class="rounded-xl bg-slate-800 text-white px-4 py-2">Sair</a>
    </div>
    <?php if ($mensagem !== ''): ?><div class="mb-6 rounded-xl bg-green-50 text-green-800 px-4 py-3"><?php echo esc($mensagem); ?></div><?php endif; ?>
    <?php if ($erro !== ''): ?><div class="mb-6 rounded-xl bg-red-50 text-red-800 px-4 py-3"><?php echo esc($erro); ?></div><?php endif; ?>
    <div class="grid lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2 space-y-4">
            <form method="get" class="bg-white rounded-2xl p-4 shadow-sm flex flex-wrap gap-3 items-center"><label for="filtro-treinamento" class="font-semibold">Filtrar por treinamento</label><select id="filtro-treinamento" name="treinamento_id" class="flex-1 min-w-48 rounded-xl border p-3" onchange="this.form.submit()"><option value="0">Todos os treinamentos</option><?php foreach ($treinamentos as $treinamento): ?><option value="<?php echo (int) $treinamento['id']; ?>" <?php echo $treinamentoSelecionado === (int) $treinamento['id'] ? 'selected' : ''; ?>><?php echo esc($treinamento['titulo']); ?></option><?php endforeach; ?></select></form>
            <?php if (!$aulas): ?><div class="bg-white rounded-2xl p-6 shadow-sm text-slate-500">Nenhuma aula cadastrada para este filtro.</div><?php endif; ?>
            <?php foreach ($aulas as $aula): ?><article class="bg-white rounded-2xl p-5 shadow-sm"><p class="text-sm text-blue-700 font-semibold"><?php echo esc($aula['treinamento_titulo']); ?></p><h2 class="font-bold text-xl mt-1"><?php echo (int) $aula['ordem']; ?>. <?php echo esc($aula['titulo']); ?></h2><p class="text-slate-500 mt-2"><?php echo esc($aula['objetivo'] ?: 'Sem objetivo informado.'); ?></p><div class="flex justify-between items-center mt-4 text-sm"><span><?php echo (int) $aula['duracao_min']; ?> min</span><a href="aula-template.php?id=<?php echo (int) $aula['id']; ?>" class="text-blue-700 font-semibold">Abrir aula →</a></div></article><?php endforeach; ?>
        </section>
        <form method="post" class="bg-white rounded-2xl p-5 shadow-sm space-y-4 h-fit"><h2 class="font-bold text-xl">Nova aula</h2><?php echo csrfInput(); ?><select name="treinamento_id" required class="w-full rounded-xl border p-3"><option value="">Selecione o treinamento</option><?php foreach ($treinamentos as $treinamento): ?><option value="<?php echo (int) $treinamento['id']; ?>" <?php echo $treinamentoSelecionado === (int) $treinamento['id'] ? 'selected' : ''; ?>><?php echo esc($treinamento['titulo']); ?></option><?php endforeach; ?></select><input name="titulo" required placeholder="Título da aula" class="w-full rounded-xl border p-3"><input name="ordem" type="number" min="1" value="1" required placeholder="Ordem" class="w-full rounded-xl border p-3"><input name="duracao_min" type="number" min="0" value="30" placeholder="Duração em minutos" class="w-full rounded-xl border p-3"><textarea name="objetivo" placeholder="Objetivo da aula" class="w-full rounded-xl border p-3"></textarea><textarea name="conteudo" required rows="7" placeholder="Conteúdo da aula" class="w-full rounded-xl border p-3"></textarea><button class="w-full rounded-xl bg-blue-700 text-white py-3 font-semibold">Salvar aula</button></form>
    </div>
</main>
</body>
</html>
