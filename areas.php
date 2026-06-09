<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
$pageTitle = 'Areas';
$activePage = 'areas';
include 'includes/header.php';
include 'includes/menu.php';
?>
<main class="main-panel">
    <header class="topbar">
        <div>
            <p class="text-sm font-bold text-blue-700">Organizacao</p>
            <h1 class="page-title">Areas de conhecimento</h1>
        </div>
        <button class="primary-button" type="button">Nova area</button>
    </header>

    <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <?php
        $areas = [
            ['nome' => 'Automacao Industrial', 'descricao' => 'CLP, robotica e instrumentacao.'],
            ['nome' => 'Tecnologia da Informacao', 'descricao' => 'Redes, sistemas e seguranca.'],
            ['nome' => 'Seguranca do Trabalho', 'descricao' => 'NRs, EPIs e prevencao.'],
            ['nome' => 'Gestao e Negocios', 'descricao' => 'Projetos, lideranca e processos.'],
        ];
        foreach ($areas as $area):
        ?>
            <article class="course-card">
                <h2 class="text-lg font-extrabold"><?php echo esc($area['nome']); ?></h2>
                <p class="mt-2 text-sm text-slate-600"><?php echo esc($area['descricao']); ?></p>
            </article>
        <?php endforeach; ?>
    </section>
<?php include 'includes/footer.php'; ?>
