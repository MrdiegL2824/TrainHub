<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
$pageTitle = 'Template de Aula';
$activePage = 'aulas';
include 'includes/header.php';
include 'includes/menu.php';
?>
<main class="main-panel">
    <header class="topbar">
        <div>
            <p class="text-sm font-bold text-blue-700">Template padronizado</p>
            <h1 class="page-title">Editor de aula</h1>
        </div>
        <a href="aulas.php" class="secondary-button">Voltar</a>
    </header>

    <section class="section-card p-6" x-data="{ aba: 'plano' }">
        <div class="flex flex-wrap gap-2">
            <button class="secondary-button" type="button" @click="aba = 'plano'">Plano</button>
            <button class="secondary-button" type="button" @click="aba = 'conteudo'">Conteudo</button>
            <button class="secondary-button" type="button" @click="aba = 'materiais'">Materiais</button>
        </div>

        <div class="mt-6 grid gap-4" x-show="aba === 'plano'">
            <input class="search-input w-full" type="text" placeholder="Titulo da aula">
            <textarea class="search-input w-full min-h-32" placeholder="Objetivo da aula"></textarea>
        </div>

        <div class="mt-6 grid gap-4" x-show="aba === 'conteudo'">
            <textarea class="search-input w-full min-h-56" placeholder="Conteudo principal da aula"></textarea>
        </div>

        <div class="mt-6 grid gap-4" x-show="aba === 'materiais'">
            <input class="search-input w-full" type="file">
            <p class="text-sm text-slate-600">Nesta primeira versao, o envio e apenas visual. A conexao com upload em PHP entra na proxima etapa.</p>
        </div>
    </section>
<?php include 'includes/footer.php'; ?>
