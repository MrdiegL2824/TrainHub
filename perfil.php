<?php
declare(strict_types=1);

require_once 'config.php';
exigirLogin();

$erro = '';
$sucesso = '';
$usuarioId = (int) $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
    } else {
        try {
            $acao = (string) ($_POST['acao'] ?? '');
            if ($acao === 'perfil') {
                $nome = trim((string) ($_POST['nome'] ?? ''));
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                if (mb_strlen($nome) < 2 || mb_strlen($nome) > 120) {
                    $erro = 'Informe um nome entre 2 e 120 caracteres.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $erro = 'Informe um e-mail válido.';
                } else {
                    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE (LOWER(nome) = LOWER(?) OR LOWER(email) = LOWER(?)) AND id <> ? LIMIT 1');
                    $stmt->execute([$nome, $email, $usuarioId]);
                    if ($stmt->fetchColumn() !== false) {
                        $erro = 'Este nome ou e-mail já está sendo usado.';
                    } else {
                        $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?');
                        $stmt->execute([$nome, $email, $usuarioId]);
                        $_SESSION['usuario_nome'] = $nome;
                        $_SESSION['usuario_email'] = $email;
                        registrarAtividade($pdo, $usuarioId, 'alterou o perfil', 'dados da própria conta');
                        $sucesso = 'Perfil atualizado.';
                    }
                }
            } elseif ($acao === 'senha') {
                $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
                $novaSenha = (string) ($_POST['nova_senha'] ?? '');
                $confirmacao = (string) ($_POST['confirmar_senha'] ?? '');
                $stmt = $pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = ? LIMIT 1');
                $stmt->execute([$usuarioId]);
                $hashAtual = (string) $stmt->fetchColumn();
                if (!password_verify($senhaAtual, $hashAtual)) {
                    $erro = 'A senha atual está incorreta.';
                } elseif (strlen($novaSenha) < 8) {
                    $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
                } elseif ($novaSenha !== $confirmacao) {
                    $erro = 'A confirmação da nova senha não confere.';
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = ?, auth_provider = 'local', atualizado_em = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([password_hash($novaSenha, PASSWORD_DEFAULT), $usuarioId]);
                    registrarAtividade($pdo, $usuarioId, 'alterou a senha', 'própria conta');
                    $sucesso = 'Senha atualizada.';
                }
            } else {
                $erro = 'Ação inválida.';
            }
        } catch (Throwable $e) {
            registrarErro($pdo, 'atualizacao de perfil', $e);
            $erro = 'Não foi possível atualizar o perfil agora.';
        }
    }
}

$stmt = $pdo->prepare('SELECT nome, email, perfil, criado_em FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$usuarioId]);
$usuario = $stmt->fetch();
$stmt = $pdo->prepare('SELECT mensagem, resposta, status, atualizado_em FROM pedidos_suporte WHERE usuario_id = ? ORDER BY criado_em DESC LIMIT 1');
$stmt->execute([$usuarioId]);
$ultimoSuporte = $stmt->fetch();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TrainHub - Perfil</title>
    <link rel="stylesheet" href="css/app.css?v=1.0">
    <style>
        .perfil-grid { display:grid; grid-template-columns:minmax(0,1.5fr) minmax(240px,.5fr); gap:24px; }
        .perfil-identidade { display:flex; align-items:center; gap:16px; margin-bottom:22px; padding-bottom:20px; border-bottom:1px solid var(--th-border); }
        .perfil-foto { width:64px; height:64px; display:grid; place-items:center; border:2px solid #080b10; border-radius:50%; background:#05070b; color:#aeb7c7; font-size:24px; }
        .perfil-identidade h1 { margin:0 0 5px; font-size:22px; }
        .perfil-identidade p { margin:0; color:var(--th-muted); font-size:12px; }
        .perfil-secao { margin-top:22px; padding-top:20px; border-top:1px solid var(--th-border); }
        .perfil-secao h2 { margin:0 0 13px; }
        .perfil-lateral { min-height:430px; }
        @media(max-width:760px){.perfil-grid{grid-template-columns:1fr}.perfil-lateral{display:none}}
    </style>
</head>
<body>
<header class="th-topbar"><a class="th-logo" href="inicio.php"><img src="assets/logo.png" alt="TrainHub"></a><a class="th-avatar" href="logout.php" aria-label="Sair">↪</a></header>
<main class="th-shell">
    <a class="th-back" href="inicio.php" aria-label="Voltar">←</a>
    <?php if ($erro !== ''): ?><p class="th-alert th-alert-error"><?php echo esc($erro); ?></p><?php endif; ?>
    <?php if ($sucesso !== ''): ?><p class="th-alert th-alert-success"><?php echo esc($sucesso); ?></p><?php endif; ?>
    <div class="perfil-grid">
        <section class="th-panel">
            <div class="perfil-identidade"><div class="perfil-foto">●</div><div><h1><?php echo esc($usuario['nome']); ?></h1><p><?php echo esc($usuario['email']); ?> · <?php echo esc($usuario['perfil']); ?></p></div></div>
            <?php if ($ultimoSuporte): ?><section class="th-card" style="margin-bottom:20px"><div class="th-card-row"><strong>Último pedido de suporte</strong><span class="th-badge <?php echo $ultimoSuporte['status'] === 'resolvido' ? 'th-badge-success' : 'th-badge-warning'; ?>"><?php echo esc(str_replace('_', ' ', $ultimoSuporte['status'])); ?></span></div><p><?php echo esc($ultimoSuporte['mensagem']); ?></p><?php if ($ultimoSuporte['resposta']): ?><p style="color:var(--th-text)"><strong>Resposta da equipe:</strong><br><?php echo nl2br(esc($ultimoSuporte['resposta'])); ?></p><?php endif; ?></section><?php endif; ?>
            <form class="th-form" method="post">
                <?php echo csrfInput(); ?><input type="hidden" name="acao" value="perfil">
                <div class="th-form-row"><label class="th-field">Nome de usuário<input name="nome" maxlength="120" value="<?php echo esc($usuario['nome']); ?>" required></label><label class="th-field">E-mail<input name="email" type="email" maxlength="180" value="<?php echo esc($usuario['email']); ?>" required></label></div>
                <button class="th-button" type="submit">Salvar perfil</button>
            </form>
            <section class="perfil-secao"><h2>Alterar senha</h2><form class="th-form" method="post"><?php echo csrfInput(); ?><input type="hidden" name="acao" value="senha"><label class="th-field">Senha atual<input name="senha_atual" type="password" autocomplete="current-password" required></label><div class="th-form-row"><label class="th-field">Nova senha<input name="nova_senha" type="password" minlength="8" autocomplete="new-password" required></label><label class="th-field">Confirmar nova senha<input name="confirmar_senha" type="password" minlength="8" autocomplete="new-password" required></label></div><button class="th-button" type="submit">Atualizar senha</button></form></section>
            <div class="perfil-secao"><a class="th-button th-button-danger" href="logout.php">Sair da conta</a></div>
        </section>
        <aside class="th-panel perfil-lateral" aria-label="Espaço do perfil"></aside>
    </div>
</main>
</body>
</html>