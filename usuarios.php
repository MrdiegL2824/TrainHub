<?php
require_once 'config.php';
require_once 'includes/auth_check.php';
$pageTitle = 'Usuarios';
$activePage = 'usuarios';
include 'includes/header.php';
include 'includes/menu.php';
?>
<main class="main-panel">
    <?php if (!temPerfil('admin')): ?>
        <section class="section-card p-6">
            <h1 class="text-2xl font-extrabold">Acesso restrito</h1>
            <p class="mt-2 text-slate-600">Somente administradores podem gerenciar usuarios.</p>
        </section>
    <?php include 'includes/footer.php'; ?>
    <?php exit; endif; ?>

    <header class="topbar">
        <div>
            <p class="text-sm font-bold text-blue-700">Acesso</p>
            <h1 class="page-title">Usuarios</h1>
        </div>
        <button class="primary-button" type="button">Novo usuario</button>
    </header>

    <section class="section-card overflow-hidden">
        <div class="grid">
            <?php
            $usuarios = [
                ['nome' => 'Administrador', 'email' => 'admin@senai-cic.br', 'perfil' => 'Admin'],
                ['nome' => 'Instrutor SENAI', 'email' => 'instrutor@senai-cic.br', 'perfil' => 'Instrutor'],
            ];
            foreach ($usuarios as $usuario):
            ?>
                <div class="grid gap-2 border-b border-slate-200 p-5 last:border-b-0 md:grid-cols-3 md:items-center">
                    <strong><?php echo esc($usuario['nome']); ?></strong>
                    <span class="text-slate-600"><?php echo esc($usuario['email']); ?></span>
                    <span class="tag w-fit"><?php echo esc($usuario['perfil']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php include 'includes/footer.php'; ?>
