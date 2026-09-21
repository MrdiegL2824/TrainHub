<?php
declare(strict_types=1);

require_once 'config.php';

if (usuarioLogado()) {
    redirecionar('perfil.php');
}

$erro = '';
$sucesso = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um endereço de e-mail válido.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE LOWER(email) = LOWER(?) AND ativo = TRUE AND auth_provider = 'local' LIMIT 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('DELETE FROM redefinicoes_senha WHERE usuario_id = ? AND usado_em IS NULL');
                $stmt->execute([(int) $usuario['id']]);
                $expiracao = bancoPostgres($pdo)
                    ? "CURRENT_TIMESTAMP + INTERVAL '1 hour'"
                    : 'DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR)';
                $stmt = $pdo->prepare("INSERT INTO redefinicoes_senha (usuario_id, token_hash, expira_em) VALUES (?, ?, $expiracao)");
                $stmt->execute([(int) $usuario['id'], $tokenHash]);
                $pdo->commit();

                $remetente = trim((string) getenv('TRAINHUB_MAIL_FROM'));
                if (getenv('TRAINHUB_MAIL_ENABLED') === '1' && filter_var($remetente, FILTER_VALIDATE_EMAIL)) {
                    $link = APP_URL . '/redefinir-senha.php?token=' . rawurlencode($token);
                    $assunto = 'Redefinição de senha - TrainHub';
                    $mensagem = "Olá, {$usuario['nome']}.\n\nUse o link abaixo para redefinir sua senha. Ele expira em 1 hora:\n$link\n\nSe você não pediu essa alteração, ignore esta mensagem.";
                    $cabecalhos = "From: TrainHub <{$remetente}>\r\nContent-Type: text/plain; charset=UTF-8";
                    if (!mail((string) $usuario['email'], $assunto, $mensagem, $cabecalhos)) {
                        error_log('TrainHub password reset email could not be sent.');
                    }
                }
            }

            $sucesso = 'Se o e-mail estiver cadastrado, você receberá as instruções para redefinir a senha.';
            $email = '';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            registrarErro($pdo, 'recuperacao de senha', $e);
            $erro = 'Não foi possível processar a solicitação agora.';
        }
    }
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TrainHub - Recuperar senha</title>
    <link rel="stylesheet" href="css/app.css?v=1.0">
    <style>
        body { background: #02060e url('assets/background-TrainHub.png') center/cover no-repeat fixed; }
        .reset-page { min-height:100vh; display:grid; place-items:center; padding:24px; background:rgba(0,5,15,.44); }
        .reset-card { width:min(410px,100%); padding:28px; border:1px solid #124071; border-radius:17px; background:rgba(2,10,20,.88); }
        .reset-logo { width:58px; height:58px; object-fit:contain; }
        .reset-card h1 { margin:12px 0 7px; }
        .reset-card > p { color:var(--th-muted); line-height:1.5; }
        .reset-back { display:block; margin-top:15px; color:#9db2d8; text-align:center; text-decoration:none; }
    </style>
</head>
<body>
<main class="reset-page">
    <section class="reset-card">
        <img class="reset-logo" src="assets/logo.png" alt="TrainHub">
        <h1>Recuperar senha</h1>
        <p>Informe o e-mail da sua conta. O link enviado será válido por uma hora.</p>
        <?php if ($erro !== ''): ?><p class="th-alert th-alert-error"><?php echo esc($erro); ?></p><?php endif; ?>
        <?php if ($sucesso !== ''): ?><p class="th-alert th-alert-success"><?php echo esc($sucesso); ?></p><?php endif; ?>
        <form class="th-form" method="post">
            <?php echo csrfInput(); ?>
            <label class="th-field" for="email">E-mail
                <input id="email" name="email" type="email" maxlength="180" value="<?php echo esc($email); ?>" autocomplete="email" required>
            </label>
            <button class="th-button" type="submit">Enviar</button>
        </form>
        <a class="reset-back" href="index.php">Voltar para o login</a>
    </section>
</main>
</body>
</html>