<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainHub - Aulas</title>
    <link rel="stylesheet" href="css/estilos.css?v=1.6">
    
    <style>
        body {
            background-color: #b2c0d6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        .container-usuarios {
            background-color: #e5eaf5;
            width: 100%;
            max-width: 500px;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .titulo-usuarios {
            color: #3b4766;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .btn-voltar {
            display: inline-block;
            width: 100%;
            max-width: 200px;
            padding: 12px 24px;
            background-color: #3f5283;
            color: #ffffff;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 16px;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-voltar:hover {
            background-color: #314067;
            transform: translateY(-1px);
        }

        .btn-voltar:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <div class="container-usuarios">
        <h1 class="titulo-usuarios">Aulas</h1>
        
        <div style="margin-bottom: 30px; color: #718096; font-size: 0.95rem;">
            <p>Gerencie as aulas cadastradas no sistema TrainHub aqui.</p>
        </div>

        <a class="btn-voltar" href="dashboard.php">Voltar ao Painel</a>
    </div>

</body>
</html>