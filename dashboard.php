<?php
require_once 'config.php';
exigirLogin();

try {
    $total_treinamentos = $pdo->query("SELECT COUNT(*) FROM treinamentos")->fetchColumn();
    $total_aulas = $pdo->query("SELECT COUNT(*) FROM aulas")->fetchColumn();
    $total_materiais = $pdo->query("SELECT COUNT(*) FROM materiais")->fetchColumn();

    $metricas = [
        'treinamentos' => $total_treinamentos,
        'aulas'        => $total_aulas,
        'materiais'    => $total_materiais
    ];

    $sql = "SELECT 
                t.id, 
                a.nome AS categoria, 
                t.titulo, 
                t.descricao,
                50 AS progresso
            FROM treinamentos t
            INNER JOIN areas a ON t.area_id = a.id
            ORDER BY t.criado_em DESC 
            LIMIT 3";
            
    $stmt = $pdo->query($sql);
    $cursos_recentes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $metricas = ['treinamentos' => 0, 'aulas' => 0, 'materiais' => 0];
    $cursos_recentes = [];
    error_log('TrainHub dashboard query failed: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainHub - Painel</title>
    <link rel="stylesheet" href="css/estilos.css?v=1.4">
</head>
<body class="painel-corpo">

    <aside class="barra-lateral">
        <div>
            <div class="barra-logo">
                <img src="assets/logo.png" alt="Logo">
                <span>TrainHub</span>
            </div>
            <nav class="navegacao-lateral">
                <a href="dashboard.php" class="link-navegacao ativo">Inicio</a>
                <a href="treinamentos.php" class="link-navegacao">Treinamentos</a>
                <a href="areas.php" class="link-navegacao">Áreas</a>
                <a href="competencias.php" class="link-navegacao">Competências</a>
                <a href="aulas.php" class="link-navegacao">Aulas</a>
                <a href="usuarios.php" class="link-navegacao">Usuarios</a>
            </nav>
        </div>
        <div class="menu-inferior">
            <div class="info-usuario">
                <p class="tipo-usuario"><?php echo esc(usuarioPerfil() ?: 'Usuário'); ?></p>
                <p class="nome-usuario"><?php echo esc($_SESSION['usuario_nome'] ?? ''); ?></p>
            </div>
            <a href="logout.php" class="botao-sair">Sair</a>
        </div>
    </aside>
    <main class="conteudo-principal">
        <header class="cabecalho">
            <div class="titulo">
                <p>Bem-vindo ao TrainHub</p>
                <h1>Painel de treinamentos</h1>
            </div>
            <div class="container-pesquisa">
                <input type="text" placeholder="Pesquisar treinamentos" class="campo-pesquisa">
            </div>
        </header>

        <div class="corpo-painel">
            <div class="grade-metricas">
                <div class="cartao-metrica">
                    <p class="rotulo-metrica">Treinamentos</p>
                    <p class="valor-metrica"><?php echo $metricas['treinamentos']; ?></p>
                </div>
                <div class="cartao-metrica">
                    <p class="rotulo-metrica">Aulas</p>
                    <p class="valor-metrica"><?php echo $metricas['aulas']; ?></p>
                </div>
                <div class="cartao-metrica">
                    <p class="rotulo-metrica">Materiais</p>
                    <p class="valor-metrica"><?php echo $metricas['materiais']; ?></p>
                </div>
            </div>

            <div class="cabecalho-secao">
                <h2>Cursos Recentes</h2>
                <a href="treinamentos.php" class="link-ver-tudo">Ver tudo</a>
            </div>
            <div class="grade-cursos">
                <?php foreach ($cursos_recentes as $curso): ?>
                    <div class="cartao-curso">
                        <div>
                            <span class="categoria-curso"><?php echo esc($curso['categoria']); ?></span>
                            <h3 class="titulo-curso"><?php echo esc($curso['titulo']); ?></h3>
                            <p class="descricao-curso"><?php echo esc($curso['descricao']); ?></p>
                        </div>
                        
                        <div class="rodape-curso">
                            <div class="info-progresso">
                                <span>Progresso</span>
                                <span><?php echo $curso['progresso']; ?>%</span>
                            </div>
                            <div class="fundo-barra-progresso">
                                <div class="preenchimento-barra-progresso" style="width: <?php echo $curso['progresso']; ?>%;"></div>
                            </div>
                            <div>
                                <div>
                                    <a href="treinamento-detalhe.php?id=<?php echo $curso['id']; ?>" class="botao-abrir-curso">Abrir Curso</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </main>

</body>
</html>
