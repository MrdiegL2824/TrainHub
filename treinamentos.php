<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
$pageTitle = 'Treinamentos';
$activePage = 'treinamentos';
include 'includes/header.php';
include 'includes/menu.php';
?>
<main class="main-panel">
    <header class="topbar">
        <div>
            <p class="text-sm font-bold text-blue-700">Catalogo</p>
            <h1 class="page-title">Treinamentos</h1>
        </div>
        <a href="treinamento-detalhe.php" class="primary-button">Novo treinamento</a>
    </header>

    <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <?php
        $treinamentos = [
            ['titulo' => 'Boas praticas de seguranca', 'area' => 'Seguranca do Trabalho', 'horas' => '12h', 'status' => 'Publicado'],
            ['titulo' => 'Introducao a automacao', 'area' => 'Automacao Industrial', 'horas' => '20h', 'status' => 'Rascunho'],
            ['titulo' => 'Fundamentos de redes', 'area' => 'Tecnologia da Informacao', 'horas' => '16h', 'status' => 'Publicado'],
            ['titulo' => 'Lideranca para instrutores', 'area' => 'Gestao e Negocios', 'horas' => '8h', 'status' => 'Publicado'],
        ];
        foreach ($treinamentos as $treinamento):
        ?>
            <article class="course-card">
                <div class="flex items-start justify-between gap-3">
                    <span class="tag"><?php echo esc($treinamento['area']); ?></span>
                    <span class="text-xs font-bold text-slate-500"><?php echo esc($treinamento['status']); ?></span>
                </div>
                <h2 class="mt-4 text-xl font-extrabold"><?php echo esc($treinamento['titulo']); ?></h2>
                <p class="mt-2 text-sm text-slate-600">Carga horaria: <?php echo esc($treinamento['horas']); ?></p>
                <a href="treinamento-detalhe.php" class="primary-button mt-5 w-full">Ver detalhes</a>
            </article>
        <?php endforeach; ?>
    </section>
<?php include 'includes/footer.php'; ?>
