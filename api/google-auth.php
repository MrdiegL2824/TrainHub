<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonErro('Metodo nao permitido.', 405);
}

if (GOOGLE_CLIENT_ID === '') {
    jsonErro('Login Google ainda nao configurado.', 503);
}

$payload = json_decode(file_get_contents('php://input'), true);
$idToken = is_array($payload) ? (string) ($payload['credential'] ?? '') : '';

if ($idToken === '') {
    jsonErro('Credencial Google ausente.', 400);
}

$response = @file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken));

if ($response === false) {
    jsonErro('Nao foi possivel validar a credencial Google.', 502);
}

$googleUser = json_decode($response, true);

if (!is_array($googleUser)) {
    jsonErro('Resposta Google invalida.', 502);
}

$audienciaValida = hash_equals(GOOGLE_CLIENT_ID, (string) ($googleUser['aud'] ?? ''));
$emissorValido = in_array($googleUser['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true);
$emailVerificado = ($googleUser['email_verified'] ?? false) === true || ($googleUser['email_verified'] ?? '') === 'true';
$email = filter_var($googleUser['email'] ?? '', FILTER_VALIDATE_EMAIL);
$googleId = (string) ($googleUser['sub'] ?? '');
$nome = trim((string) ($googleUser['name'] ?? 'Usuario Google'));

if (!$audienciaValida || !$emissorValido || !$emailVerificado || !$email || $googleId === '') {
    jsonErro('Credencial Google rejeitada.', 401);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, nome, email, perfil, ativo FROM usuarios WHERE google_id = :google_id OR email = :email LIMIT 1');
    $stmt->execute(['google_id' => $googleId, 'email' => $email]);
    $usuario = $stmt->fetch();
} catch (RuntimeException) {
    jsonErro('Banco de dados indisponivel.', 503);
}

if ($usuario) {
    if (!$usuario['ativo']) {
        jsonErro('Usuario inativo.', 403);
    }

    $update = $pdo->prepare('UPDATE usuarios SET google_id = :google_id, auth_provider = :provider WHERE id = :id');
    $update->execute([
        'google_id' => $googleId,
        'provider' => 'google',
        'id' => $usuario['id'],
    ]);
} else {
    $insert = $pdo->prepare(
        'INSERT INTO usuarios (nome, email, senha_hash, perfil, google_id, auth_provider)
         VALUES (:nome, :email, NULL, :perfil, :google_id, :provider)'
    );
    $insert->execute([
        'nome' => $nome,
        'email' => $email,
        'perfil' => 'instrutor',
        'google_id' => $googleId,
        'provider' => 'google',
    ]);

    $usuario = [
        'id' => (int) $pdo->lastInsertId(),
        'nome' => $nome,
        'email' => $email,
        'perfil' => 'instrutor',
        'ativo' => 1,
    ];
}

session_regenerate_id(true);
$_SESSION['usuario'] = [
    'id' => (int) $usuario['id'],
    'nome' => $usuario['nome'],
    'email' => $usuario['email'],
    'perfil' => $usuario['perfil'],
];

jsonSucesso(['redirect' => APP_URL . '/dashboard.php'], 'Login realizado.');
