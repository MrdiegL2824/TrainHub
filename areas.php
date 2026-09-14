<?php
require_once __DIR__ . '/includes/areas-app.php';
exit;
require_once 'config.php';
exigirLogin();
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) { $mensagem = 'Sessão expirada. Atualize a página e tente novamente.'; }
    elseif (!temPerfil('admin')) { $mensagem = 'Somente administradores podem cadastrar áreas.'; }
    else {
        $nome = trim((string) ($_POST['nome'] ?? '')); $descricao = trim((string) ($_POST['descricao'] ?? '')); $cor = trim((string) ($_POST['cor'] ?? '#3B4E7A'));
        if ($nome === '') { $mensagem = 'Informe o nome da área.'; }
        else { $stmt = $pdo->prepare('INSERT INTO areas (nome, descricao, cor) VALUES (?, ?, ?)'); $stmt->execute([$nome, $descricao, $cor]); redirecionar('areas.php?salvo=1'); }
    }
}
$areas = $pdo->query('SELECT id, nome, descricao, cor FROM areas ORDER BY nome')->fetchAll();
if (isset($_GET['salvo'])) { $mensagem = 'Área cadastrada com sucesso.'; }
?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TrainHub - Áreas</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-slate-100 min-h-screen text-slate-800"><main class="max-w-5xl mx-auto p-6 md:p-10"><div class="flex flex-wrap justify-between items-center gap-4 mb-8"><div><a href="inicio.php" class="text-blue-700 font-semibold">← Painel</a><h1 class="text-3xl font-bold mt-3">Áreas de conhecimento</h1><p class="text-slate-500">Organize os treinamentos por tema.</p></div><a href="logout.php" class="rounded-xl bg-slate-800 text-white px-4 py-2">Sair</a></div><?php if ($mensagem !== ''): ?><div class="mb-6 rounded-xl bg-blue-50 text-blue-800 px-4 py-3"><?php echo esc($mensagem); ?></div><?php endif; ?><div class="grid md:grid-cols-3 gap-6"><section class="md:col-span-2 grid sm:grid-cols-2 gap-4"><?php foreach ($areas as $area): ?><article class="bg-white rounded-2xl p-5 shadow-sm border-l-8" style="border-color:<?php echo esc($area['cor']); ?>"><h2 class="font-bold text-xl"><?php echo esc($area['nome']); ?></h2><p class="text-slate-500 mt-2"><?php echo esc($area['descricao'] ?: 'Sem descrição cadastrada.'); ?></p></article><?php endforeach; ?></section><?php if (temPerfil('admin')): ?><form method="post" class="bg-white rounded-2xl p-5 shadow-sm space-y-4"><h2 class="font-bold text-xl">Nova área</h2><?php echo csrfInput(); ?><input name="nome" required placeholder="Nome da área" class="w-full rounded-xl border p-3"><textarea name="descricao" placeholder="Descrição" class="w-full rounded-xl border p-3"></textarea><label class="block text-sm">Cor<input type="color" name="cor" value="#3B4E7A" class="block mt-1 h-10 w-full"></label><button class="w-full rounded-xl bg-blue-700 text-white py-3 font-semibold">Cadastrar área</button></form><?php endif; ?></div></main></body></html>
