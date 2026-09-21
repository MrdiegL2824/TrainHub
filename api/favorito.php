<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrf($_POST['csrf_token'] ?? null)) {
    redirecionar('inicio.php?erro=solicitacao');
}

$areaId = (int) ($_POST['area_id'] ?? 0);
$usuarioId = (int) $_SESSION['usuario_id'];
$acao = (string) ($_POST['acao'] ?? 'alternar');
$responderJson = str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');

try {
    if ($areaId < 1 || !in_array($acao, ['adicionar', 'remover', 'alternar'], true)) {
        throw new InvalidArgumentException('Favorito inválido.');
    }

    if ($acao === 'alternar') {
        $stmt = $pdo->prepare('SELECT 1 FROM favoritos_areas WHERE usuario_id = ? AND area_id = ?');
        $stmt->execute([$usuarioId, $areaId]);
        $acao = $stmt->fetchColumn() ? 'remover' : 'adicionar';
    }

    if ($acao === 'remover') {
        $stmt = $pdo->prepare('DELETE FROM favoritos_areas WHERE usuario_id = ? AND area_id = ?');
        $stmt->execute([$usuarioId, $areaId]);
    } else {
        if (bancoPostgres($pdo)) {
            $stmt = $pdo->prepare('INSERT INTO favoritos_areas (usuario_id, area_id) VALUES (?, ?) ON CONFLICT (usuario_id, area_id) DO NOTHING');
        } else {
            $stmt = $pdo->prepare('INSERT IGNORE INTO favoritos_areas (usuario_id, area_id) VALUES (?, ?)');
        }
        $stmt->execute([$usuarioId, $areaId]);
    }

    if ($responderJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['sucesso' => true, 'favorito' => $acao === 'adicionar'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Throwable $e) {
    registrarErro($pdo, 'favorito de area', $e);
    if ($responderJson) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['sucesso' => false], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

redirecionar('inicio.php#areas');
