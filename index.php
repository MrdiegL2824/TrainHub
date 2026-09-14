<?php
require_once 'config.php';
if (usuarioLogado()) { redirecionar('inicio.php'); }
$mensagens = [
    'credenciais_invalidas' => 'E-mail, nome de usuário ou senha inválidos.',
    'campos_vazios' => 'Informe e-mail ou nome de usuário e senha.',
    'solicitacao' => 'A solicitação expirou. Tente novamente.',
    'erro_interno' => 'Não foi possível concluir o login agora.',
    'suporte_campos' => 'Escreva uma mensagem para solicitar suporte.',
    'muitas_tentativas' => 'Muitas tentativas de acesso. Aguarde 15 minutos e tente novamente.',
];
$mensagensSucesso = [
    'enviado' => 'Pedido de suporte enviado ao administrador.',
    'redefinida' => 'Senha redefinida. Você já pode entrar com a nova senha.',
];
$erro = $mensagens[$_GET['erro'] ?? ''] ?? '';
$sucesso = $mensagensSucesso[$_GET['suporte'] ?? $_GET['senha'] ?? ''] ?? '';
$bloqueado = !empty($_SESSION['usuario_bloqueado_id']);
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
        .marca { position: absolute; top: 20px; left: 25px; width: 96px; height: 62px; object-fit: contain; object-position: left center; }
        .cartao-login { position: absolute; top: 27%; left: 25px; width: min(390px, calc(100vw - 50px)); padding: 32px 28px 35px; border: 1px solid #124071; border-radius: 17px; background: rgba(2, 10, 20, .66); box-shadow: 0 8px 25px rgba(0, 0, 0, .28); }
        .campo { display: block; width: 100%; height: 44px; margin: 0 0 19px; padding: 6px 15px; border: 0; border-radius: 14px; outline: none; background: #fff; color: #203d82; font-size: 16px; }
        .campo::placeholder { color: #25468c; opacity: 1; }
        .campo:focus { box-shadow: 0 0 0 2px #5577dc; }
        .esqueci { display: block; margin: -10px 4px 15px; color: #ff1717; font-size: 11px; font-weight: 700; text-decoration: none; }
        .esqueci:hover { text-decoration: underline; }
        .botao-entrar { width: 100%; height: 44px; border: 0; border-radius: 10px; background: #263e9e; color: #fff; font-size: 18px; font-weight: 700; line-height: 1; cursor: pointer; }
        .botao-entrar:hover { background: #3958c6; }
        .cadastro-restrito { display: block; margin-top: 15px; color: #7184b2; font-size: 12px; }
        .aviso-erro { margin: 0 0 12px; padding: 7px 9px; border-radius: 8px; background: rgba(180, 20, 20, .82); color: #fff; font-size: 11px; font-weight: 700; }
        .aviso-sucesso { margin: 0 0 12px; padding: 7px 9px; border-radius: 8px; background: rgba(24, 126, 79, .88); color: #fff; font-size: 11px; font-weight: 700; }
        .modal-fundo { position: fixed; inset: 0; z-index: 5; display: grid; place-items: center; padding: 20px; background: rgba(0, 0, 0, .62); }
        .modal { width: min(430px, 100%); padding: 26px; border: 1px solid #31558b; border-radius: 16px; background: #101a2b; box-shadow: 0 12px 40px rgba(0, 0, 0, .45); }
        .modal h2 { margin: 0 0 10px; font-size: 22px; }
        .modal p { margin: 0 0 18px; color: #c7d2e7; line-height: 1.5; }
        .modal textarea { display: block; width: 100%; min-height: 110px; margin-bottom: 14px; padding: 11px; border: 0; border-radius: 10px; resize: vertical; }
        .modal button { width: 100%; padding: 11px; border: 0; border-radius: 9px; background: #3159b2; color: #fff; font-weight: 700; cursor: pointer; }
        .modal-suporte { display: none; }
        .modal-suporte.ativo { display: block; }
        .modal-fechar { display: block; margin-top: 13px; color: #9db2d8; text-align: center; font-size: 13px; text-decoration: none; }
        @media (max-width: 600px) {
            body { background-position: 63% center; overflow: auto; }
            .marca { top: 18px; left: 18px; }
            .cartao-login { top: 30%; left: 50%; transform: translateX(-50%); width: min(390px, calc(100vw - 36px)); }
        }
    </style>
</head>
<body>
    <main class="tela-login">
        <img class="marca" src="assets/logo.png" alt="TrainHub">
        <section class="cartao-login" aria-label="Formulário de login">
            <?php if ($erro !== ''): ?><p class="aviso-erro"><?php echo esc($erro); ?></p><?php endif; ?>
            <?php if ($sucesso !== ''): ?><p class="aviso-sucesso"><?php echo esc($sucesso); ?></p><?php endif; ?>
            <form action="api/auth.php" method="POST">
                <?php echo csrfInput(); ?>
                <input class="campo" type="text" id="email" name="email" placeholder="email ou nome de usuário" aria-label="E-mail ou nome de usuário" required>
                <input class="campo" type="password" id="senha" name="senha" placeholder="senha" aria-label="Senha" required>
                <a class="esqueci" href="recuperar-senha.php">esqueci minha senha</a>
                <button class="botao-entrar" type="submit">ENTRAR</button>
            </form>
            <span class="cadastro-restrito">O cadastro é realizado somente pelo administrador.</span>
        </section>
        <?php if ($bloqueado): ?><div class="modal-fundo"><section class="modal" role="dialog" aria-modal="true" aria-labelledby="titulo-bloqueio"><div id="aviso-bloqueio"><h2 id="titulo-bloqueio">Acesso bloqueado</h2><p>Seu acesso ao TrainHub está bloqueado no momento. Se acredita que isso aconteceu por engano, solicite suporte à equipe.</p><button id="abrir-suporte" type="button">Solicitar suporte</button></div><div class="modal-suporte" id="formulario-suporte"><h2>Pedido de suporte</h2><p>Explique o que aconteceu para que o administrador possa analisar seu acesso.</p><form action="api/suporte.php" method="POST"><?php echo csrfInput(); ?><textarea name="mensagem" required maxlength="2000" placeholder="Escreva sua mensagem para o administrador..."></textarea><button type="submit">Enviar solicitação</button></form></div><a class="modal-fechar" href="logout.php">Voltar para o login</a></section></div><script>document.getElementById('abrir-suporte').addEventListener('click',function(){document.getElementById('aviso-bloqueio').hidden=true;document.getElementById('formulario-suporte').classList.add('ativo');document.querySelector('#formulario-suporte textarea').focus();});</script><?php endif; ?>
    </main>
</body>
</html>
