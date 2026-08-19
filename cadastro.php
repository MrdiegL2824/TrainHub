<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainHub-Cadastro</title>
    <link rel="stylesheet" href="css/estilos.css?v=1.2">
</head>
<body class="corpo-login">

    <div class="container-login">
        <h1 class="titulo-login">Faça seu Cadastro</h1>
        
        <form action="cadastro.php" method="POST">
            
            <div class="grupo-campo-login">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required>
            </div>

            <div class="grupo-campo-login">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="grupo-campo-login">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit" class="btn-cadastrar-login" style="margin-top: 15px;">Cadastrar</button>
        </form>
        
        <div class="divisor-cadastro" style="margin-top: 25px;">    
            <a href="index.php" class="link-esqueceu" style="color: #718096; text-align: center; margin: 0; display: inline-block;">Já tenho uma conta</a>
        </div>
    </div>

</body>
</html>
