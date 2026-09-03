<?php
declare(strict_types=1);

require_once 'config.php';
exigirLogin();

$ehAdmin = temPerfil('admin');
$erro = '';
$mensagem = '';
$aulaSelecionada = (int) ($_GET['aula_id'] ?? $_POST['aula_id'] ?? 0);

function rotuloMaterial(string $tipo): string
{
    return match ($tipo) {
        'pdf' => 'PDF',
        'video' => 'Vídeo',
        'link' => 'Link',
        default => 'Outro',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$ehAdmin) {
        http_response_code(403);
        exit('Acesso restrito aos administradores.');
    }

    $acao = (string) ($_POST['acao'] ?? '');
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
    } elseif ($acao === 'adicionar') {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $arquivo = trim((string) ($_POST['arquivo'] ?? ''));
        $tipo = (string) ($_POST['tipo'] ?? 'link');
        $aulaSelecionada = (int) ($_POST['aula_id'] ?? 0);
        $url = filter_var($arquivo, FILTER_VALIDATE_URL);
        $partesUrl = $url ? parse_url($arquivo) : false;

        if (mb_strlen($nome) < 2 || mb_strlen($nome) > 200) {
            $erro = 'Informe um nome de material entre 2 e 200 caracteres.';
        } elseif (!$url || !is_array($partesUrl) || !in_array(strtolower((string) ($partesUrl['scheme'] ?? '')), ['http', 'https'], true) || empty($partesUrl['host']) || strlen($arquivo) > 300) {
            $erro = 'Informe um link http ou https válido para o material.';
        } elseif (!in_array($tipo, ['pdf', 'video', 'link', 'outro'], true)) {
            $erro = 'Tipo de material inválido.';
        } elseif ($aulaSelecionada < 1) {
            $erro = 'Selecione uma aula.';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT id, titulo FROM aulas WHERE id = ? LIMIT 1');
                $stmt->execute([$aulaSelecionada]);
                $aula = $stmt->fetch();
                if (!$aula) {
                    $erro = 'A aula selecionada não existe.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO materiais (aula_id, nome, arquivo, tipo, tamanho_kb) VALUES (?, ?, ?, ?, 0)');
                    $stmt->execute([$aulaSelecionada, $nome, $arquivo, $tipo]);
                    $stmt = $pdo->prepare("INSERT INTO atividades (usuario_id, acao, descricao) VALUES (?, 'cadastrou o material', ?)");
                    $stmt->execute([(int) $_SESSION['usuario_id'], $nome]);
                    redirecionar('materiais.php?aula_id=' . $aulaSelecionada . '&salvo=1');
                }
            } catch (Throwable $e) {
                error_log('TrainHub material creation failed: ' . $e->getMessage());
                $erro = 'Não foi possível cadastrar o material agora.';
            }
        }
    } elseif ($acao === 'excluir') {
        $materialId = (int) ($_POST['material_id'] ?? 0);
        if ($materialId < 1) {
            $erro = 'Material inválido.';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT nome FROM materiais WHERE id = ? LIMIT 1');
                $stmt->execute([$materialId]);
                $material = $stmt->fetch();
                if (!$material) {
                    $erro = 'Material não encontrado.';
                } else {
                    $stmt = $pdo->prepare('DELETE FROM materiais WHERE id = ?');
                    $stmt->execute([$materialId]);
                    $stmt = $pdo->prepare("INSERT INTO atividades (usuario_id, acao, descricao) VALUES (?, 'removeu o material', ?)");
                    $stmt->execute([(int) $_SESSION['usuario_id'], $material['nome']]);
                    $mensagem = 'Material removido.';
                }
            } catch (Throwable $e) {
                error_log('TrainHub material deletion failed: ' . $e->getMessage());
                $erro = 'Não foi possível remover o material agora.';
            }
        }
    } else {
        $erro = 'Ação inválida.';
    }
}

if (isset($_GET['salvo'])) {
    $mensagem = 'Material cadastrado com sucesso.';
}

$aulas = $pdo->query(
    'SELECT a.id, a.titulo, t.titulo AS treinamento_titulo FROM aulas a JOIN treinamentos t ON t.id = a.treinamento_id ORDER BY t.titulo, a.ordem, a.id'
)->fetchAll();
$materiais = $pdo->query(
    'SELECT m.id, m.nome, m.arquivo, m.tipo, m.tamanho_kb, a.id AS aula_id, a.titulo AS aula_titulo, t.titulo AS treinamento_titulo
     FROM materiais m JOIN aulas a ON a.id = m.aula_id JOIN treinamentos t ON t.id = a.treinamento_id
     ORDER BY t.titulo, a.ordem, m.nome'
)->fetchAll();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TrainHub - Materiais</title>
    <style>
        :root { color-scheme:dark; --fundo:#293346; --barra:#343a43; --painel:#3c4961; --escuro:#202838; --texto:#f4f5f8; --secundario:#b5bdcb; --azul:#3159b2; --vermelho:#7e2228; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:var(--fundo); color:var(--texto); font-family:Arial,Helvetica,sans-serif; }
        a { color:inherit; }
        .topo { height:47px; display:flex; align-items:center; justify-content:space-between; padding:0 14px; background:var(--barra); border-bottom:1px solid rgba(12,18,29,.45); }
        .logo { width:30px; height:30px; object-fit:contain; opacity:.82; }
        .sair { padding:7px 12px; border-radius:8px; background:var(--escuro); text-decoration:none; font-size:13px; }
        main { width:min(1180px, calc(100% - 64px)); margin:28px auto 55px; }
        .voltar { display:inline-block; margin-bottom:16px; font-size:22px; text-decoration:none; }
        .cabecalho { display:flex; align-items:flex-end; justify-content:space-between; gap:20px; margin-bottom:22px; }
        h1 { margin:0 0 7px; font-size:28px; }
        h2 { margin:0; font-size:18px; }
        .subtitulo { margin:0; color:var(--secundario); }
        .painel { padding:22px; border-radius:16px; background:var(--painel); }
        .grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(280px,.65fr); gap:22px; align-items:start; }
        .lista { display:grid; gap:11px; margin-top:13px; }
        .material { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px 16px; border-radius:10px; background:var(--escuro); }
        .material-info { min-width:0; }
        .material-info strong, .material-info span { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .material-info span { margin-top:5px; color:var(--secundario); font-size:12px; }
        .material-acoes { display:flex; flex:0 0 auto; align-items:center; gap:8px; }
        .abrir, button { border:0; border-radius:7px; padding:8px 11px; background:var(--azul); color:#fff; font:inherit; font-size:12px; font-weight:700; text-decoration:none; cursor:pointer; }
        button:hover, .abrir:hover { background:#436bc7; }
        .remover { background:#5e2932; }
        .formulario { display:grid; gap:13px; margin-top:15px; }
        label { display:grid; gap:6px; color:var(--secundario); font-size:13px; }
        input, select { width:100%; min-height:39px; border:1px solid transparent; border-radius:8px; padding:8px 10px; background:var(--escuro); color:var(--texto); font:inherit; }
        input:focus, select:focus { outline:2px solid #6887df; }
        .aviso { margin:0 0 18px; padding:11px 13px; border-radius:9px; font-weight:700; }
        .erro { background:var(--vermelho); }
        .sucesso { background:#1d714b; }
        .vazio { margin-top:13px; padding:28px; border-radius:10px; background:var(--escuro); color:var(--secundario); text-align:center; }
        @media (max-width:850px) { main { width:calc(100% - 28px); } .grid { grid-template-columns:1fr; } }
        @media (max-width:560px) { .cabecalho { align-items:flex-start; flex-direction:column; } .material { align-items:flex-start; flex-direction:column; } .material-acoes { width:100%; } }
    </style>
</head>
<body>
    <header class="topo">
        <a href="<?php echo $ehAdmin ? 'dashboard.php' : 'inicio.php'; ?>" aria-label="Voltar"><img class="logo" src="assets/logo.png" alt="TrainHub"></a>
        <a class="sair" href="logout.php">Sair</a>
    </header>
    <main>
        <a class="voltar" href="<?php echo $ehAdmin ? 'dashboard.php' : 'inicio.php'; ?>" aria-label="Voltar">←</a>
        <div class="cabecalho">
            <div><h1>Materiais</h1><p class="subtitulo">Acesse os materiais complementares de cada aula.</p></div>
        </div>
        <?php if ($erro !== ''): ?><p class="aviso erro"><?php echo esc($erro); ?></p><?php endif; ?>
        <?php if ($mensagem !== ''): ?><p class="aviso sucesso"><?php echo esc($mensagem); ?></p><?php endif; ?>
        <div class="grid">
            <section class="painel">
                <h2>Materiais cadastrados</h2>
                <?php if (!$materiais): ?>
                    <p class="vazio">Nenhum material cadastrado.</p>
                <?php else: ?>
                    <div class="lista">
                        <?php foreach ($materiais as $material): ?>
                            <?php if ($aulaSelecionada > 0 && (int) $material['aula_id'] !== $aulaSelecionada) { continue; } ?>
                            <article class="material">
                                <div class="material-info">
                                    <strong><?php echo esc($material['nome']); ?></strong>
                                    <span><?php echo esc($material['treinamento_titulo']); ?> · <?php echo esc($material['aula_titulo']); ?> · <?php echo esc(rotuloMaterial((string) $material['tipo'])); ?></span>
                                </div>
                                <div class="material-acoes">
                                    <a class="abrir" href="<?php echo esc($material['arquivo']); ?>" target="_blank" rel="noopener noreferrer">Abrir</a>
                                    <?php if ($ehAdmin): ?>
                                        <form method="post">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="material_id" value="<?php echo (int) $material['id']; ?>">
                                            <button class="remover" type="submit">Remover</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            <?php if ($ehAdmin): ?>
                <section class="painel">
                    <h2>Novo material</h2>
                    <form class="formulario" method="post">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="acao" value="adicionar">
                        <label for="aula_id">Aula
                            <select id="aula_id" name="aula_id" required>
                                <option value="">Selecione a aula</option>
                                <?php foreach ($aulas as $aula): ?>
                                    <option value="<?php echo (int) $aula['id']; ?>" <?php echo $aulaSelecionada === (int) $aula['id'] ? 'selected' : ''; ?>><?php echo esc($aula['treinamento_titulo'] . ' - ' . $aula['titulo']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label for="nome">Nome do material
                            <input id="nome" name="nome" type="text" maxlength="200" placeholder="Ex.: Apostila da aula" required>
                        </label>
                        <label for="tipo">Tipo
                            <select id="tipo" name="tipo" required><option value="pdf">PDF</option><option value="video">Vídeo</option><option value="link" selected>Link</option><option value="outro">Outro</option></select>
                        </label>
                        <label for="arquivo">Link do material
                            <input id="arquivo" name="arquivo" type="url" maxlength="300" placeholder="https://..." required>
                        </label>
                        <button type="submit">Salvar material</button>
                    </form>
                </section>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
