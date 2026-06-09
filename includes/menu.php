<?php
$menuItems = [
    ['label' => 'Pagina Inicial', 'href' => 'dashboard.php', 'key' => 'dashboard', 'icon' => 'home'],
    ['label' => 'Treinamentos', 'href' => 'treinamentos.php', 'key' => 'treinamentos', 'icon' => 'courses'],
    ['label' => 'Areas', 'href' => 'areas.php', 'key' => 'areas', 'icon' => 'areas'],
    ['label' => 'Competencias', 'href' => 'competencias.php', 'key' => 'competencias', 'icon' => 'skills'],
    ['label' => 'Aulas', 'href' => 'aulas.php', 'key' => 'aulas', 'icon' => 'classes'],
    ['label' => 'Usuarios', 'href' => 'usuarios.php', 'key' => 'usuarios', 'icon' => 'users'],
];
$usuarioAtual = usuarioLogado();
?>
<aside class="sidebar">
    <a href="dashboard.php" class="brand">
        <img src="assets/logo.png" alt="Logo TrainHub" class="brand-logo">
        <span>TrainHub</span>
    </a>

    <nav class="sidebar-nav" aria-label="Menu principal">
        <?php foreach ($menuItems as $item): ?>
            <a href="<?php echo esc($item['href']); ?>" class="nav-link <?php echo $activePage === $item['key'] ? 'active' : ''; ?>">
                <span class="nav-icon nav-icon-<?php echo esc($item['icon']); ?>"></span>
                <?php echo esc($item['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($usuarioAtual): ?>
        <div class="mt-8 rounded-lg bg-white/10 p-3">
            <p class="text-sm font-bold text-white"><?php echo esc($usuarioAtual['nome']); ?></p>
            <p class="text-xs font-semibold text-blue-100"><?php echo esc($usuarioAtual['perfil']); ?></p>
        </div>
    <?php endif; ?>

    <a href="logout.php" class="logout-link">Sair</a>
</aside>
