<?php
declare(strict_types=1);

require_once 'config.php';
exigirLogin();

if (!temPerfil('admin')) {
    http_response_code(403);
    exit('Acesso restrito aos administradores.');
}

$erro = '';
$sucesso = '';
$nome = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
    } elseif (mb_strlen($nome) < 2 || mb_strlen($nome) > 120) {
        $erro = 'Informe um nome de usuário entre 2 e 120 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($senha !== $confirmarSenha) {
        $erro = 'As senhas não conferem.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE LOWER(nome) = LOWER(?) LIMIT 1');
            $stmt->execute([$nome]);
            if ($stmt->fetchColumn() !== false) {
                $erro = 'Já existe um usuário com este nome.';
            } else {
                $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1');
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() !== false) {
                    $erro = 'Já existe um usuário com este e-mail.';
                } else {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare(
                        "INSERT INTO usuarios (nome, email, senha_hash, perfil, ativo, auth_provider)
                         VALUES (?, ?, ?, 'instrutor', TRUE, 'local')"
                    );
                    $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);

                    $stmt = $pdo->prepare(
                        "INSERT INTO atividades (usuario_id, acao, descricao) VALUES (?, 'cadastrou o usuário', ?)"
                    );
                    $stmt->execute([(int) $_SESSION['usuario_id'], 'Criou o usuário "' . $nome . '"']);
                    $pdo->commit();

                    $sucesso = 'Cliente cadastrado com sucesso.';
                    $nome = '';
                    $email = '';
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('TrainHub admin user creation failed: ' . $e->getMessage());
            $erro = 'Não foi possível cadastrar o cliente agora.';
        }
    }
}

$usuariosRecentes = $pdo->query(
    "SELECT nome, email, ativo FROM usuarios WHERE perfil = 'instrutor' ORDER BY criado_em DESC LIMIT 8"
)->fetchAll();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TrainHub - Cadastrar cliente</title>
    <style>
        :root { color-scheme: dark; --fundo:#293346; --barra:#343a43; --painel:#3c4961; --escuro:#202838; --texto:#f4f5f8; --secundario:#b5bdcb; --azul:#3159b2; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; background:var(--fundo); color:var(--texto); font-family:Arial,Helvetica,sans-serif; }
        .topo { height:47px; display:flex; align-items:center; justify-content:space-between; padding:0 14px; background:var(--barra); border-bottom:1px solid rgba(12,18,29,.45); }
        .logo { width:30px; height:30px; object-fit:contain; opacity:.82; }
        .avatar { width:36px; height:36px; border:1px solid #080b10; border-radius:50%; background:#05070b; display:grid; place-items:center; color:#adb4c0; font-weight:700; text-decoration:none; }
        main { width:min(980px, calc(100% - 72px)); margin:38px auto 60px; }
        .voltar { display:inline-block; margin-bottom:18px; color:var(--texto); text-decoration:none; font-size:22px; }
        .painel { padding:28px; border-radius:16px; background:var(--painel); }
        h1 { margin:0 0 8px; font-size:26px; }
        .subtitulo { margin:0 0 25px; color:var(--secundario); }
        .formulario { max-width:680px; display:grid; gap:15px; }
        label { display:grid; gap:7px; color:var(--secundario); font-size:14px; }
        input { width:100%; min-height:42px; padding:9px 12px; border:1px solid transparent; border-radius:9px; background:var(--escuro); color:var(--texto); font:inherit; }
        input:focus { outline:2px solid #6887df; }
        button { min-height:42px; border:0; border-radius:9px; padding:0 18px; background:var(--azul); color:#fff; font-weight:700; cursor:pointer; }
        button:hover { background:#436bc7; }
        .aviso { margin:0 0 18px; padding:11px 13px; border-radius:9px; font-weight:700; }
        .erro { background:#7e2228; color:#fff; }
        .sucesso { background:#1d714b; color:#fff; }
        .recentes { margin-top:30px; }
        .recentes h2 { margin:0 0 12px; font-size:18px; }
        .lista { display:grid; gap:8px; }
        .item { display:grid; grid-template-columns:1fr 1.2fr 90px; gap:12px; align-items:center; padding:10px 12px; border-radius:8px; background:var(--escuro); color:var(--secundario); font-size:13px; }
        .item strong { color:var(--texto); }
        @media (max-width:760px) { main { width:calc(100% - 28px); margin-top:22px; } .painel { padding:20px; } .item { grid-template-columns:1fr; gap:4px; } }
    </style>
</head>
<body>
    <header class="topo">
        <a href="dashboard.php" aria-label="Voltar ao dashboard"><img class="logo" src="assets/logo.png" alt="TrainHub"></a>
        <a class="avatar" href="logout.php" aria-label="Sair">A</a>
    </header>
    <main>
        <a class="voltar" href="dashboard.php" aria-label="Voltar">←</a>
        <section class="painel">
            <h1>Cadastrar cliente</h1>
            <p class="subtitulo">O administrador cria o acesso informando nome de usuário, e-mail e senha.</p>
            <?php if ($erro !== ''): ?><p class="aviso erro"><?php echo esc($erro); ?></p><?php endif; ?>
            <?php if ($sucesso !== ''): ?><p class="aviso sucesso"><?php echo esc($sucesso); ?></p><?php endif; ?>
            <form class="formulario" method="post">
                <?php echo csrfInput(); ?>
                <label for="nome">Nome de usuário
                    <input id="nome" name="nome" type="text" maxlength="120" value="<?php echo esc($nome); ?>" required>
                </label>
                <label for="email">E-mail
                    <input id="email" name="email" type="email" maxlength="180" value="<?php echo esc($email); ?>" required>
                </label>
                <label for="senha">Senha
                    <input id="senha" name="senha" type="password" minlength="8" required>
                </label>
                <label for="confirmar_senha">Confirmar senha
                    <input id="confirmar_senha" name="confirmar_senha" type="password" minlength="8" required>
                </label>
                <button type="submit">Cadastrar cliente</button>
            </form>
            <?php if ($usuariosRecentes): ?>
                <div class="recentes">
                    <h2>Clientes cadastrados</h2>
                    <div class="lista">
                        <?php foreach ($usuariosRecentes as $usuario): ?>
                            <div class="item">
                                <strong><?php echo esc($usuario['nome']); ?></strong>
                                <span><?php echo esc($usuario['email']); ?></span>
                                <span><?php echo $usuario['ativo'] ? 'Ativo' : 'Bloqueado'; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
