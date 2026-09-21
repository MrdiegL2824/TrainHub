<?php
declare(strict_types=1);

require_once 'config.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$tokenValido = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmar = (string) ($_POST['confirmar_senha'] ?? '');
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
    } elseif (!$tokenValido) {
        $erro = 'Este link é inválido ou expirou.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($senha !== $confirmar) {
        $erro = 'As senhas não conferem.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id, usuario_id FROM redefinicoes_senha WHERE token_hash = ? AND usado_em IS NULL AND expira_em > CURRENT_TIMESTAMP LIMIT 1 FOR UPDATE');
            $stmt->execute([hash('sha256', $token)]);
            $redefinicao = $stmt->fetch();
            if (!$redefinicao) {
                $pdo->rollBack();
                $erro = 'Este link é inválido ou expirou.';
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = ?, auth_provider = 'local', atualizado_em = CURRENT_TIMESTAMP WHERE id = ? AND ativo = TRUE");
                $stmt->execute([password_hash($senha, PASSWORD_DEFAULT), (int) $redefinicao['usuario_id']]);
                if ($stmt->rowCount() !== 1) {
                    $pdo->rollBack();
                    $erro = 'Não foi possível atualizar essa conta.';
                } else {
                    $stmt = $pdo->prepare('UPDATE redefinicoes_senha SET usado_em = CURRENT_TIMESTAMP WHERE id = ?');
                    $stmt->execute([(int) $redefinicao['id']]);
                    registrarAtividade($pdo, (int) $redefinicao['usuario_id'], 'redefiniu a senha', 'própria conta');
                    $pdo->commit();
                    redirecionar('index.php?senha=redefinida');
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            registrarErro($pdo, 'redefinicao de senha', $e);
            $erro = 'Não foi possível redefinir a senha agora.';
        }
    }
}

if ($tokenValido && $erro === '') {
    $stmt = $pdo->prepare('SELECT id FROM redefinicoes_senha WHERE token_hash = ? AND usado_em IS NULL AND expira_em > CURRENT_TIMESTAMP LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $tokenValido = $stmt->fetchColumn() !== false;
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TrainHub - Redefinir senha</title>
    <link rel="stylesheet" href="css/app.css?v=1.0">
    <style>
        body { background:#02060e url('assets/background-TrainHub.png') center/cover no-repeat fixed; }
        .reset-page { min-height:100vh; display:grid; place-items:center; padding:24px; background:rgba(0,5,15,.44); }
        .reset-card { width:min(410px,100%); padding:28px; border:1px solid #124071; border-radius:17px; background:rgba(2,10,20,.88); }
        .reset-card h1 { margin:0 0 8px; }
        .reset-card > p { color:var(--th-muted); }
        .reset-back { display:block; margin-top:15px; color:#9db2d8; text-align:center; text-decoration:none; }
    </style>
</head>
<body><main class="reset-page"><section class="reset-card">
    <h1>Redefinir senha</h1>
    <?php if (!$tokenValido): ?>
        <p class="th-alert th-alert-error"><?php echo esc($erro ?: 'Este link é inválido ou expirou.'); ?></p>
        <a class="th-button" href="recuperar-senha.php">Solicitar outro link</a>
    <?php else: ?>
        <p>Escolha uma nova senha com pelo menos oito caracteres.</p>
        <?php if ($erro !== ''): ?><p class="th-alert th-alert-error"><?php echo esc($erro); ?></p><?php endif; ?>
        <form class="th-form" method="post">
            <?php echo csrfInput(); ?><input type="hidden" name="token" value="<?php echo esc($token); ?>">
            <label class="th-field" for="senha">Nova senha<input id="senha" name="senha" type="password" minlength="8" autocomplete="new-password" required></label>
            <label class="th-field" for="confirmar_senha">Confirmar senha<input id="confirmar_senha" name="confirmar_senha" type="password" minlength="8" autocomplete="new-password" required></label>
            <button class="th-button" type="submit">Salvar nova senha</button>
        </form>
    <?php endif; ?>
    <a class="reset-back" href="index.php">Voltar para o login</a>
</section></main></body>
</html>