<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
$pageTitle = 'Aulas';
$activePage = 'aulas';
include 'includes/header.php';
include 'includes/menu.php';
?>
<main class="main-panel">
    <header class="topbar">
        <div>
            <p class="text-sm font-bold text-blue-700">Conteudo</p>
            <h1 class="page-title">Aulas</h1>
        </div>
        <a href="aula-template.php" class="primary-button">Criar aula</a>
    </header>

    <section class="grid gap-5 md:grid-cols-2">
        <?php
        $aulas = [
            ['titulo' => 'Introducao a seguranca', 'duracao' => '2h', 'treinamento' => 'Boas praticas de seguranca'],
            ['titulo' => 'EPIs e procedimentos', 'duracao' => '3h', 'treinamento' => 'Boas praticas de seguranca'],
            ['titulo' => 'Primeiros passos com CLP', 'duracao' => '4h', 'treinamento' => 'Introducao a automacao'],
            ['titulo' => 'Enderecamento IP', 'duracao' => '2h', 'treinamento' => 'Fundamentos de redes'],
        ];
        foreach ($aulas as $aula):
        ?>
            <article class="course-card">
                <span class="tag"><?php echo esc($aula['treinamento']); ?></span>
                <h2 class="mt-4 text-xl font-extrabold"><?php echo esc($aula['titulo']); ?></h2>
                <p class="mt-2 text-sm text-slate-600">Duracao: <?php echo esc($aula['duracao']); ?></p>
            </article>
        <?php endforeach; ?>
    </section>
<?php include 'includes/footer.php'; ?>
