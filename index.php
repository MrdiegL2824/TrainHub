<?php
require_once 'config.php';

if (usuarioLogado()) {
    redirecionar('dashboard.php');
}

$loginErro = $_SESSION['login_erro'] ?? '';
unset($_SESSION['login_erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainHub | Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="login-page">
    <section class="login-card">
        <div class="flex items-center gap-4">
            <img src="assets/logo.png" alt="Logo TrainHub" class="login-logo">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900">TrainHub</h1>
                <p class="text-sm font-semibold text-blue-700">Sistema de Gerenciamento de Treinamentos</p>
            </div>
        </div>

        <?php if ($loginErro): ?>
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-700">
                <?php echo esc($loginErro); ?>
            </div>
        <?php endif; ?>

        <form action="api/auth.php" method="POST" class="mt-8 grid gap-4">
            <?php echo csrfInput(); ?>
            <label class="grid gap-2 text-sm font-bold text-slate-700">
                Email
                <input name="email" type="email" value="admin@senai-cic.br" class="search-input w-full" required>
            </label>

            <label class="grid gap-2 text-sm font-bold text-slate-700">
                Senha
                <input name="senha" type="password" value="admin123" class="search-input w-full" required>
            </label>

            <button class="primary-button w-full mt-2" type="submit">Entrar</button>
        </form>

        <?php if (GOOGLE_CLIENT_ID !== ''): ?>
            <div class="mt-5 border-t border-slate-200 pt-5">
                <div id="g_id_onload"
                     data-client_id="<?php echo esc(GOOGLE_CLIENT_ID); ?>"
                     data-callback="loginGoogle"
                     data-auto_prompt="false"></div>
                <div class="g_id_signin"
                     data-type="standard"
                     data-size="large"
                     data-theme="outline"
                     data-text="signin_with"
                     data-shape="rectangular"
                     data-logo_alignment="left"></div>
            </div>
            <script src="https://accounts.google.com/gsi/client" async defer></script>
            <script>
                async function loginGoogle(response) {
                    const retorno = await fetch('api/google-auth.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ credential: response.credential })
                    });
                    const dados = await retorno.json();
                    if (dados.sucesso && dados.dados.redirect) {
                        window.location.href = dados.dados.redirect;
                    }
                }
            </script>
        <?php endif; ?>
    </section>
</body>
</html>
