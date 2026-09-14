<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrf($_POST['csrf_token'] ?? null)) {
    redirecionar('inicio.php?erro=solicitacao');
}

$aulaId = (int) ($_POST['aula_id'] ?? 0);
$concluir = ($_POST['acao'] ?? '') === 'concluir';
$usuarioId = (int) $_SESSION['usuario_id'];

try {
    $stmt = $pdo->prepare('SELECT a.id, a.titulo, a.treinamento_id, a.status, t.status AS treinamento_status FROM aulas a JOIN treinamentos t ON t.id = a.treinamento_id WHERE a.id = ? LIMIT 1');
    $stmt->execute([$aulaId]);
    $aula = $stmt->fetch();
    if (!$aula || (!temPerfil('admin') && ($aula['status'] !== 'publicado' || $aula['treinamento_status'] !== 'publicado'))) {
        http_response_code(404);
        exit('Aula não encontrada.');
    }

    garantirMatriculasPublicadas($pdo, $usuarioId);
    $pdo->beginTransaction();
    if (bancoPostgres($pdo)) {
        $stmt = $pdo->prepare('INSERT INTO progresso_aulas (usuario_id, aula_id, concluida, atualizado_em) VALUES (?, ?, ?, CURRENT_TIMESTAMP) ON CONFLICT (usuario_id, aula_id) DO UPDATE SET concluida = EXCLUDED.concluida, atualizado_em = CURRENT_TIMESTAMP');
        $stmt->execute([$usuarioId, $aulaId, $concluir ? 1 : 0]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO progresso_aulas (usuario_id, aula_id, concluida, atualizado_em) VALUES (?, ?, ?, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE concluida = VALUES(concluida), atualizado_em = CURRENT_TIMESTAMP');
        $stmt->execute([$usuarioId, $aulaId, $concluir ? 1 : 0]);
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM aulas WHERE treinamento_id = ? AND status = 'publicado'");
    $stmt->execute([(int) $aula['treinamento_id']]);
    $total = (int) $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM progresso_aulas p JOIN aulas a ON a.id = p.aula_id WHERE p.usuario_id = ? AND a.treinamento_id = ? AND a.status = 'publicado' AND p.concluida = TRUE");
    $stmt->execute([$usuarioId, (int) $aula['treinamento_id']]);
    $concluidas = (int) $stmt->fetchColumn();
    $cursoConcluido = $total > 0 && $concluidas >= $total;
    $stmt = $pdo->prepare("UPDATE matriculas SET status = ?, ultima_aula_id = ?, concluido_em = " . ($cursoConcluido ? 'CURRENT_TIMESTAMP' : 'NULL') . ' WHERE usuario_id = ? AND treinamento_id = ?');
    $stmt->execute([$cursoConcluido ? 'concluido' : 'em_andamento', $aulaId, $usuarioId, (int) $aula['treinamento_id']]);
    registrarAtividade($pdo, $usuarioId, $concluir ? 'concluiu a aula' : 'reabriu a aula', (string) $aula['titulo']);
    $pdo->commit();
    redirecionar('aula-template.php?id=' . $aulaId . '&progresso=salvo');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    registrarErro($pdo, 'atualizacao de progresso', $e);
    redirecionar('aula-template.php?id=' . $aulaId . '&erro=progresso');
}
