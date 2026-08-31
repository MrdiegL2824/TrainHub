<?php
require_once 'config.php';
if (usuarioLogado()) { redirecionar('dashboard.php'); }
$mensagens = [
    'credenciais_invalidas' => 'E-mail ou senha inválidos.',
    'campos_vazios' => 'Informe e-mail e senha.',
    'solicitacao' => 'A solicitação expirou. Tente novamente.',
    'erro_interno' => 'Não foi possível concluir o login agora.',
];
$erro = $mensagens[$_GET['erro'] ?? ''] ?? '';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainHub - Login</title>
    <style>
        :root { font-family: Arial, Helvetica, sans-serif; color: #fff; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #02060e url('assets/background-TrainHub.png') center center / cover no-repeat; overflow: hidden; }
        .tela-login { position: relative; min-height: 100vh; background: linear-gradient(90deg, rgba(0, 5, 15, .22), transparent 58%); }
        .marca { position: absolute; top: 22px; left: 25px; margin: 0; color: #123b91; font-size: clamp(28px, 4vw, 42px); font-weight: 800; letter-spacing: -2px; text-shadow: 0 2px 2px #02050c, 0 0 12px rgba(21, 88, 205, .3); }
        .link-cadastrar { position: absolute; top: 12px; right: 14px; padding: 2px 7px; border-radius: 11px; background: #263d83; color: #fff; font-size: clamp(13px, 1.7vw, 19px); text-decoration: none; line-height: 1.1; }
        .link-cadastrar:hover { background: #3958b2; }
        .cartao-login { position: absolute; top: 30%; left: 25px; width: min(310px, calc(100vw - 50px)); padding: 22px 11px 25px; border: 1px solid #124071; border-radius: 13px; background: rgba(2, 10, 20, .66); box-shadow: 0 8px 25px rgba(0, 0, 0, .28); }
        .campo { display: block; width: 100%; height: 21px; margin: 0 0 17px; padding: 2px 12px; border: 0; border-radius: 14px; outline: none; background: #fff; color: #203d82; font-size: 12px; }
        .campo::placeholder { color: #25468c; opacity: 1; }
        .campo:focus { box-shadow: 0 0 0 2px #5577dc; }
        .esqueci { display: block; margin: -12px 4px 10px; color: #ff1717; font-size: 8px; font-weight: 700; text-decoration: none; }
        .esqueci:hover { text-decoration: underline; }
        .botao-entrar { width: 100%; height: 28px; border: 0; border-radius: 8px; background: #263e9e; color: #fff; font-size: 23px; line-height: 1; cursor: pointer; }
        .botao-entrar:hover { background: #3958c6; }
        .novo-usuario { display: block; margin-top: 10px; color: #4162b6; font-size: 13px; text-decoration: none; }
        .novo-usuario:hover { color: #89a6ff; }
        .aviso-erro { margin: 0 0 12px; padding: 7px 9px; border-radius: 8px; background: rgba(180, 20, 20, .82); color: #fff; font-size: 11px; font-weight: 700; }
        @media (max-width: 600px) {
            body { background-position: 63% center; overflow: auto; }
            .marca { top: 18px; left: 18px; }
            .link-cadastrar { top: 14px; right: 12px; }
            .cartao-login { top: 33%; left: 50%; transform: translateX(-50%); width: min(320px, calc(100vw - 36px)); }
        }
    </style>
</head>
<body>
    <main class="tela-login">
        <h1 class="marca">TrainHub</h1>
        <a class="link-cadastrar" href="cadastro.php">CADASTRAR</a>
        <section class="cartao-login" aria-label="Formulário de login">
            <?php if ($erro !== ''): ?><p class="aviso-erro"><?php echo esc($erro); ?></p><?php endif; ?>
            <form action="api/auth.php" method="POST">
                <?php echo csrfInput(); ?>
                <input class="campo" type="email" id="email" name="email" placeholder="email" aria-label="E-mail" required>
                <input class="campo" type="password" id="senha" name="senha" placeholder="senha" aria-label="Senha" required>
                <a class="esqueci" href="recuperar-senha.php">esqueci minha senha</a>
                <button class="botao-entrar" type="submit">ENTRAR</button>
            </form>
            <a class="novo-usuario" href="cadastro.php">Não tem uma conta?</a>
        </section>
    </main>
</body>
</html>
