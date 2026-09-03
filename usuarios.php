<?php
declare(strict_types=1);

require_once 'config.php';
exigirLogin();

if (!temPerfil('admin')) {
    http_response_code(403);
    exit('Acesso restrito aos administradores.');
}

function usuarioEstaAtivo(mixed $valor): bool
{
    return $valor === true || $valor === 1 || $valor === '1' || $valor === 't' || $valor === 'true';
}

$erro = '';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = (string) ($_POST['acao'] ?? '');
    $usuarioId = (int) ($_POST['usuario_id'] ?? 0);

    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
    } elseif ($usuarioId < 1) {
        $erro = 'Usuário inválido.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, nome, email, perfil, ativo FROM usuarios WHERE id = ? LIMIT 1');
            $stmt->execute([$usuarioId]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                $erro = 'Usuário não encontrado.';
            } elseif ($acao === 'alternar_status') {
                if ($usuarioId === (int) $_SESSION['usuario_id']) {
                    $erro = 'O administrador atual não pode bloquear a própria conta.';
                } else {
                    $novoStatus = !usuarioEstaAtivo($usuario['ativo']);
                    $stmt = $pdo->prepare('UPDATE usuarios SET ativo = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?');
                    $stmt->execute([$novoStatus, $usuarioId]);
                    $acaoLog = $novoStatus ? 'desbloqueou o usuário' : 'bloqueou o usuário';
                    $stmt = $pdo->prepare('INSERT INTO atividades (usuario_id, acao, descricao) VALUES (?, ?, ?)');
                    $stmt->execute([(int) $_SESSION['usuario_id'], $acaoLog, $usuario['nome']]);
                    $mensagem = $novoStatus ? 'Usuário desbloqueado.' : 'Usuário bloqueado.';
                }
            } elseif ($acao === 'editar') {
                $nome = trim((string) ($_POST['nome'] ?? ''));
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                $senha = (string) ($_POST['senha'] ?? '');

                if (mb_strlen($nome) < 2 || mb_strlen($nome) > 120) {
                    $erro = 'Informe um nome entre 2 e 120 caracteres.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $erro = 'Informe um e-mail válido.';
                } elseif ($senha !== '' && strlen($senha) < 8) {
                    $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
                } else {
                    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE (LOWER(nome) = LOWER(?) OR LOWER(email) = LOWER(?)) AND id <> ? LIMIT 1');
                    $stmt->execute([$nome, $email, $usuarioId]);
                    if ($stmt->fetchColumn() !== false) {
                        $erro = 'Já existe outro usuário com este nome ou e-mail.';
                    } else {
                        if ($senha === '') {
                            $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?');
                            $stmt->execute([$nome, $email, $usuarioId]);
                        } else {
                            $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ?, senha_hash = ?, auth_provider = \'local\', atualizado_em = CURRENT_TIMESTAMP WHERE id = ?');
                            $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $usuarioId]);
                        }
                        $stmt = $pdo->prepare("INSERT INTO atividades (usuario_id, acao, descricao) VALUES (?, 'editou o usuário', ?)");
                        $stmt->execute([(int) $_SESSION['usuario_id'], $nome]);
                        $mensagem = 'Dados do usuário atualizados.';
                    }
                }
            } else {
                $erro = 'Ação inválida.';
            }
        } catch (Throwable $e) {
            error_log('TrainHub user management failed: ' . $e->getMessage());
            $erro = 'Não foi possível atualizar o usuário agora.';
        }
    }
}

$usuarios = $pdo->query('SELECT id, nome, email, perfil, ativo, criado_em FROM usuarios ORDER BY nome')->fetchAll();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TrainHub - Usuários</title>
    <style>
        :root { color-scheme: dark; --fundo:#293346; --barra:#343a43; --painel:#3c4961; --escuro:#202838; --texto:#f4f5f8; --secundario:#b5bdcb; --azul:#3159b2; --vermelho:#d91d27; --verde:#2c9b68; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:var(--fundo); color:var(--texto); font-family:Arial,Helvetica,sans-serif; }
        a { color:inherit; }
        .topo { height:47px; display:flex; align-items:center; justify-content:space-between; padding:0 14px; background:var(--barra); border-bottom:1px solid rgba(12,18,29,.45); }
        .logo { width:30px; height:30px; object-fit:contain; opacity:.82; }
        .sair { padding:7px 12px; border-radius:8px; background:var(--escuro); color:var(--texto); text-decoration:none; font-size:13px; }
        main { width:min(1180px, calc(100% - 64px)); margin:28px auto 55px; }
        .voltar { display:inline-block; margin-bottom:16px; font-size:22px; text-decoration:none; }
        .cabecalho { display:flex; align-items:flex-end; justify-content:space-between; gap:20px; margin-bottom:22px; }
        h1 { margin:0 0 7px; font-size:28px; }
        .subtitulo { margin:0; color:var(--secundario); }
        .novo { padding:11px 16px; border-radius:9px; background:var(--azul); color:#fff; font-weight:700; text-decoration:none; white-space:nowrap; }
        .novo:hover, .acao:hover { background:#436bc7; }
        .aviso { margin:0 0 18px; padding:11px 13px; border-radius:9px; font-weight:700; }
        .erro { background:#7e2228; }
        .sucesso { background:#1d714b; }
        .lista { display:grid; gap:12px; }
        .usuario { display:grid; grid-template-columns:minmax(160px,1fr) minmax(180px,1.2fr) 90px 110px minmax(330px,1.7fr); gap:14px; align-items:center; padding:15px 16px; border-radius:12px; background:var(--painel); }
        .usuario-campo { min-width:0; }
        .usuario-campo small { display:block; margin-bottom:5px; color:var(--secundario); font-size:11px; }
        .usuario-campo strong, .usuario-campo span { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .usuario-acoes { display:flex; align-items:center; justify-content:flex-end; gap:8px; }
        .usuario-acoes form { margin:0; }
        .acao { min-height:34px; border:0; border-radius:7px; padding:7px 10px; background:var(--escuro); color:var(--texto); font:inherit; font-size:12px; font-weight:700; cursor:pointer; }
        .acao-bloquear { background:#5e2932; }
        .acao-desbloquear { background:#205c49; }
        .editar { grid-column:1 / -1; display:grid; grid-template-columns:1fr 1.2fr 1fr auto; gap:8px; padding-top:12px; border-top:1px solid rgba(181,189,203,.2); }
        .editar input { width:100%; min-height:34px; border:1px solid transparent; border-radius:7px; padding:7px 9px; background:var(--escuro); color:var(--texto); font:inherit; font-size:12px; }
        .editar input:focus { outline:2px solid #6887df; }
        .vazio { padding:32px; border-radius:12px; background:var(--painel); color:var(--secundario); text-align:center; }
        @media (max-width:950px) { main { width:calc(100% - 28px); } .usuario { grid-template-columns:1fr 1fr; } .usuario-acoes { justify-content:flex-start; } .editar { grid-column:1 / -1; grid-template-columns:1fr 1fr; } }
        @media (max-width:560px) { .cabecalho { align-items:flex-start; flex-direction:column; } .usuario { grid-template-columns:1fr; } .editar { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <header class="topo">
        <a href="dashboard.php" aria-label="Voltar ao dashboard"><img class="logo" src="assets/logo.png" alt="TrainHub"></a>
        <a class="sair" href="logout.php">Sair</a>
    </header>
    <main>
        <a class="voltar" href="dashboard.php" aria-label="Voltar">←</a>
        <div class="cabecalho">
            <div>
                <h1>Usuários</h1>
                <p class="subtitulo">Gerencie nomes, e-mails, senhas e acessos dos clientes.</p>
            </div>
            <a class="novo" href="admin-cadastrar-usuario.php">+ Cadastrar cliente</a>
        </div>
        <?php if ($erro !== ''): ?><p class="aviso erro"><?php echo esc($erro); ?></p><?php endif; ?>
        <?php if ($mensagem !== ''): ?><p class="aviso sucesso"><?php echo esc($mensagem); ?></p><?php endif; ?>
        <?php if (!$usuarios): ?>
            <div class="vazio">Nenhum usuário cadastrado.</div>
        <?php else: ?>
            <section class="lista" aria-label="Lista de usuários">
                <?php foreach ($usuarios as $usuario): ?>
                    <?php $ativo = usuarioEstaAtivo($usuario['ativo']); ?>
                    <article class="usuario">
                        <div class="usuario-campo"><small>Nome de usuário</small><strong><?php echo esc($usuario['nome']); ?></strong></div>
                        <div class="usuario-campo"><small>E-mail</small><span><?php echo esc($usuario['email'] ?? ''); ?></span></div>
                        <div class="usuario-campo"><small>Perfil</small><span><?php echo esc($usuario['perfil']); ?></span></div>
                        <div class="usuario-campo"><small>Status</small><span><?php echo $ativo ? 'Ativo' : 'Bloqueado'; ?></span></div>
                        <div class="usuario-acoes">
                            <?php if ((int) $usuario['id'] !== (int) $_SESSION['usuario_id']): ?>
                                <form method="post">
                                    <?php echo csrfInput(); ?>
                                    <input type="hidden" name="acao" value="alternar_status">
                                    <input type="hidden" name="usuario_id" value="<?php echo (int) $usuario['id']; ?>">
                                    <button class="acao <?php echo $ativo ? 'acao-bloquear' : 'acao-desbloquear'; ?>" type="submit"><?php echo $ativo ? 'Bloquear' : 'Desbloquear'; ?></button>
                                </form>
                            <?php else: ?><span style="color:var(--secundario);font-size:12px;">Conta atual</span><?php endif; ?>
                        </div>
                        <form class="editar" method="post">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="acao" value="editar">
                            <input type="hidden" name="usuario_id" value="<?php echo (int) $usuario['id']; ?>">
                            <input name="nome" type="text" maxlength="120" value="<?php echo esc($usuario['nome']); ?>" aria-label="Nome de <?php echo esc($usuario['nome']); ?>" required>
                            <input name="email" type="email" maxlength="180" value="<?php echo esc($usuario['email'] ?? ''); ?>" aria-label="E-mail de <?php echo esc($usuario['nome']); ?>" required>
                            <input name="senha" type="password" minlength="8" placeholder="Nova senha (opcional)" aria-label="Nova senha de <?php echo esc($usuario['nome']); ?>">
                            <button class="acao" type="submit">Salvar alterações</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
