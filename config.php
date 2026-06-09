<?php
define('APP_NAME', 'TrainHub');
define('APP_VERSAO', '1.0.0');

if (!defined('APP_URL')) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if (basename($basePath) === 'api') {
        $basePath = rtrim(dirname($basePath), '/');
    }
    if ($basePath === '/' || $basePath === '\\') {
        $basePath = '';
    }
    define('APP_URL', $scheme . '://' . $host . ($basePath === '' ? '' : $basePath));
}

define('DB_HOST', getenv('TRAINHUB_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('TRAINHUB_DB_NAME') ?: 'TrainHub');
define('DB_USER', getenv('TRAINHUB_DB_USER') ?: 'root');
define('DB_PASS', getenv('TRAINHUB_DB_PASS') ?: '');
define('DB_CHARSET', getenv('TRAINHUB_DB_CHARSET') ?: 'utf8mb4');
define('GOOGLE_CLIENT_ID', getenv('TRAINHUB_GOOGLE_CLIENT_ID') ?: '');

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_MATERIAIS', UPLOAD_DIR . 'materiais/');
define('UPLOAD_AVALIACOES', UPLOAD_DIR . 'avaliacoes/');
define('SESSION_DIR', __DIR__ . '/storage/sessions/');
define('UPLOAD_MAX_MB', 10);
define('UPLOAD_TIPOS', ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'mp4']);
define('UPLOAD_MIMES', [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'mp4' => 'video/mp4',
]);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    if (!is_dir(SESSION_DIR)) {
        mkdir(SESSION_DIR, 0775, true);
    }
    session_save_path(SESSION_DIR);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Falha na conexao com o banco de dados.', 0, $e);
        }
    }

    return $pdo;
}

function redirecionar(string $url): void
{
    header('Location: ' . APP_URL . '/' . ltrim($url, '/'));
    exit;
}

function usuarioLogado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function exigirLogin(): void
{
    if (!usuarioLogado()) {
        redirecionar('index.php');
    }
}

function temPerfil(string ...$perfis): bool
{
    $usuario = usuarioLogado();

    if (!$usuario) {
        return false;
    }

    return in_array($usuario['perfil'], $perfis, true);
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfInput(): string
{
    return '<input type="hidden" name="csrf_token" value="' . esc(csrfToken()) . '">';
}

function validarCsrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function esc(?string $valor): string
{
    if ($valor === null) {
        return '';
    }

    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function jsonResponder(mixed $dados, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonErro(string $mensagem, int $status = 400): void
{
    jsonResponder(['sucesso' => false, 'erro' => $mensagem], $status);
}

function jsonSucesso(mixed $dados = null, string $mensagem = 'OK'): void
{
    jsonResponder(['sucesso' => true, 'mensagem' => $mensagem, 'dados' => $dados]);
}
