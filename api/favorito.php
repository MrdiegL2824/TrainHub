<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrf($_POST['csrf_token'] ?? null)) {
    redirecionar('inicio.php?erro=solicitacao');
}

$areaId = (int) ($_POST['area_id'] ?? 0);
$usuarioId = (int) $_SESSION['usuario_id'];

try {
    $stmt = $pdo->prepare('SELECT id FROM areas WHERE id = ? LIMIT 1');
    $stmt->execute([$areaId]);
    if (!$stmt->fetchColumn()) {
        redirecionar('inicio.php');
    }
    $stmt = $pdo->prepare('SELECT 1 FROM favoritos_areas WHERE usuario_id = ? AND area_id = ?');
    $stmt->execute([$usuarioId, $areaId]);
    if ($stmt->fetchColumn()) {
        $stmt = $pdo->prepare('DELETE FROM favoritos_areas WHERE usuario_id = ? AND area_id = ?');
        $stmt->execute([$usuarioId, $areaId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO favoritos_areas (usuario_id, area_id) VALUES (?, ?)');
        $stmt->execute([$usuarioId, $areaId]);
    }
} catch (Throwable $e) {
    registrarErro($pdo, 'favorito de area', $e);
}

redirecionar('inicio.php#areas');
