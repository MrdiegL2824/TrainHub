<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
$pageTitle = 'Competencias';
$activePage = 'competencias';
include 'includes/header.php';
include 'includes/menu.php';
?>
<main class="main-panel">
    <header class="topbar">
        <div>
            <p class="text-sm font-bold text-blue-700">Objetivos</p>
            <h1 class="page-title">Competencias</h1>
        </div>
        <button class="primary-button" type="button">Nova competencia</button>
    </header>

    <section class="section-card overflow-hidden">
        <div class="grid gap-0">
            <?php
            $competencias = [
                'Identificar riscos e aplicar medidas preventivas no ambiente de trabalho.',
                'Operar sistemas automatizados seguindo procedimentos tecnicos.',
                'Criar materiais de aula padronizados para treinamentos internos.',
                'Aplicar conceitos basicos de redes em ambientes educacionais.',
            ];
            foreach ($competencias as $competencia):
            ?>
                <div class="border-b border-slate-200 p-5 last:border-b-0">
                    <p class="font-semibold text-slate-800"><?php echo esc($competencia); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php include 'includes/footer.php'; ?>
