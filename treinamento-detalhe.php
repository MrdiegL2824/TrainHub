<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
$pageTitle = 'Detalhe do Treinamento';
$activePage = 'treinamentos';
include 'includes/header.php';
include 'includes/menu.php';
?>
<main class="main-panel">
    <header class="topbar">
        <div>
            <p class="text-sm font-bold text-blue-700">Treinamento</p>
            <h1 class="page-title">Boas praticas de seguranca</h1>
        </div>
        <a href="treinamentos.php" class="secondary-button">Voltar</a>
    </header>

    <section class="section-card p-6">
        <div class="grid gap-5 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <span class="tag">Seguranca do Trabalho</span>
                <p class="mt-4 text-slate-600">Treinamento inicial para padronizar procedimentos, reduzir riscos e organizar materiais de apoio para instrutores.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                <div class="stat-card">
                    <p class="text-sm font-bold text-slate-500">Carga horaria</p>
                    <strong class="text-2xl">12h</strong>
                </div>
                <div class="stat-card">
                    <p class="text-sm font-bold text-slate-500">Aulas</p>
                    <strong class="text-2xl">4</strong>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-5 lg:grid-cols-2">
        <article class="section-card p-6">
            <h2 class="text-xl font-extrabold">Aulas</h2>
            <ul class="mt-4 grid gap-3">
                <li class="rounded-lg border border-slate-200 p-4">Aula 1 - Introducao a seguranca</li>
                <li class="rounded-lg border border-slate-200 p-4">Aula 2 - EPIs e procedimentos</li>
                <li class="rounded-lg border border-slate-200 p-4">Aula 3 - Analise de risco</li>
            </ul>
        </article>
        <article class="section-card p-6">
            <h2 class="text-xl font-extrabold">Materiais e avaliacoes</h2>
            <div class="mt-4 grid gap-3">
                <a href="aula-template.php" class="secondary-button">Abrir template de aula</a>
                <button class="primary-button" type="button">Enviar material</button>
                <button class="primary-button" type="button">Enviar avaliacao</button>
            </div>
        </article>
    </section>
<?php include 'includes/footer.php'; ?>
