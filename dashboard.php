<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
$pageTitle = 'Pagina Inicial';
$activePage = 'dashboard';
include 'includes/header.php';
include 'includes/menu.php';
?>
<main class="main-panel">
    <header class="topbar">
        <div>
            <p class="text-sm font-bold text-blue-700">Bem-vindo ao TrainHub</p>
            <h1 class="page-title">Painel de treinamentos</h1>
        </div>
        <input class="search-input" type="search" placeholder="Pesquisar treinamentos">
    </header>

    <section class="grid gap-4 md:grid-cols-3">
        <article class="stat-card">
            <p class="text-sm font-bold text-slate-500">Treinamentos</p>
            <strong class="text-3xl">12</strong>
        </article>
        <article class="stat-card">
            <p class="text-sm font-bold text-slate-500">Aulas cadastradas</p>
            <strong class="text-3xl">38</strong>
        </article>
        <article class="stat-card">
            <p class="text-sm font-bold text-slate-500">Materiais</p>
            <strong class="text-3xl">24</strong>
        </article>
    </section>

    <section class="mt-8">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h2 class="text-2xl font-extrabold">Cursos recentes</h2>
            <a href="treinamentos.php" class="secondary-button">Ver todos</a>
        </div>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <?php
            $cursos = [
                ['titulo' => 'Boas praticas de seguranca', 'area' => 'Seguranca do Trabalho', 'progresso' => 75],
                ['titulo' => 'Introducao a automacao', 'area' => 'Automacao Industrial', 'progresso' => 45],
                ['titulo' => 'Fundamentos de redes', 'area' => 'Tecnologia da Informacao', 'progresso' => 20],
            ];
            foreach ($cursos as $curso):
            ?>
                <article class="course-card">
                    <span class="tag"><?php echo esc($curso['area']); ?></span>
                    <h3 class="mt-4 text-xl font-extrabold"><?php echo esc($curso['titulo']); ?></h3>
                    <p class="mt-2 text-sm text-slate-600">Acompanhe aulas, materiais e avaliacoes deste treinamento.</p>
                    <div class="mt-5">
                        <div class="flex justify-between text-sm font-bold">
                            <span>Progresso</span>
                            <span><?php echo esc((string) $curso['progresso']); ?>%</span>
                        </div>
                        <div class="progress-track mt-2">
                            <div class="progress-fill" style="width: <?php echo esc((string) $curso['progresso']); ?>%"></div>
                        </div>
                    </div>
                    <a href="treinamento-detalhe.php" class="primary-button mt-5 w-full">Abrir treinamento</a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php include 'includes/footer.php'; ?>
