<?php
declare(strict_types=1);

// Vercel executes PHP functions from /api. This small allow-list keeps the
// existing XAMPP pages usable without allowing arbitrary file inclusion.
$requestedPath = (string) ($_GET['path'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$requestedPath = '/' . ltrim(str_replace('\\', '/', $requestedPath), '/');

$pages = [
    '/' => 'index.php',
    '/index.php' => 'index.php',
    '/cadastro.php' => 'cadastro.php',
    '/admin-cadastrar-usuario.php' => 'admin-cadastrar-usuario.php',
    '/recuperar-senha.php' => 'recuperar-senha.php',
    '/redefinir-senha.php' => 'redefinir-senha.php',
    '/inicio.php' => 'inicio.php',
    '/dashboard.php' => 'dashboard.php',
    '/admin-dashboard.php' => 'admin-dashboard.php',
    '/admin-gerenciar.php' => 'admin-gerenciar.php',
    '/areas.php' => 'areas.php',
    '/treinamentos.php' => 'treinamentos.php',
    '/treinamento-detalhe.php' => 'treinamento-detalhe.php',
    '/aulas.php' => 'aulas.php',
    '/aula-template.php' => 'aula-template.php',
    '/materiais.php' => 'materiais.php',
    '/avaliacoes.php' => 'avaliacoes.php',
    '/suportes.php' => 'suportes.php',
    '/logs-erros.php' => 'logs-erros.php',
    '/perfil.php' => 'perfil.php',
    '/competencias.php' => 'competencias.php',
    '/usuarios.php' => 'usuarios.php',
    '/logout.php' => 'logout.php',
    '/api/index.php' => 'index.php',
    '/api/auth.php' => 'api/auth.php',
    '/api/suporte.php' => 'api/suporte.php',
    '/api/progresso.php' => 'api/progresso.php',
    '/api/favorito.php' => 'api/favorito.php',
];

$page = $pages[$requestedPath] ?? null;
if ($page === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Página não encontrada.';
    exit;
}

$projectRoot = dirname(__DIR__);
chdir($projectRoot);
require $projectRoot . '/' . $page;
