<?php
declare(strict_types=1);

require_once 'config.php';
exigirLogin();

$ehAdmin = temPerfil('admin');
$usuarioId = (int) $_SESSION['usuario_id'];
$erro = '';
$sucesso = '';
$resultado = null;
$avaliacaoId = max(0, (int) ($_GET['id'] ?? $_POST['avaliacao_id'] ?? 0));
$treinamentoId = max(0, (int) ($_GET['treinamento_id'] ?? $_POST['treinamento_id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = (string) ($_POST['acao'] ?? '');
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
    } elseif (in_array($acao, ['salvar_avaliacao', 'adicionar_pergunta', 'excluir_avaliacao', 'excluir_pergunta'], true) && !$ehAdmin) {
        http_response_code(403);
        exit('Acesso restrito aos administradores.');
    } else {
        try {
            if ($acao === 'salvar_avaliacao') {
                $titulo = trim((string) ($_POST['titulo'] ?? ''));
                $descricao = trim((string) ($_POST['descricao'] ?? ''));
                $notaMinima = (float) ($_POST['nota_minima'] ?? 6);
                $maxTentativas = max(1, min(10, (int) ($_POST['max_tentativas'] ?? 3)));
                $ativa = isset($_POST['ativa']);
                if ($treinamentoId < 1 || mb_strlen($titulo) < 3 || $notaMinima < 0 || $notaMinima > 10) {
                    $erro = 'Informe treinamento, título e nota mínima entre 0 e 10.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO avaliacoes (treinamento_id, titulo, descricao, nota_minima, ativa, max_tentativas) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$treinamentoId, $titulo, $descricao, $notaMinima, $ativa ? 1 : 0, $maxTentativas]);
                    registrarAtividade($pdo, $usuarioId, 'criou a avaliação', $titulo);
                    $sucesso = 'Avaliação cadastrada.';
                }
            } elseif ($acao === 'adicionar_pergunta') {
                $enunciado = trim((string) ($_POST['enunciado'] ?? ''));
                $alternativas = array_map(static fn ($item) => trim((string) $item), (array) ($_POST['alternativas'] ?? []));
                $correta = (int) ($_POST['correta'] ?? -1);
                if ($avaliacaoId < 1 || mb_strlen($enunciado) < 5 || count($alternativas) !== 4 || in_array('', $alternativas, true) || $correta < 0 || $correta > 3) {
                    $erro = 'Informe o enunciado, quatro alternativas e marque a resposta correta.';
                } else {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM perguntas_avaliacao WHERE avaliacao_id = ?');
                    $stmt->execute([$avaliacaoId]);
                    $ordem = (int) $stmt->fetchColumn();
                    $perguntaId = inserirComId($pdo, 'INSERT INTO perguntas_avaliacao (avaliacao_id, enunciado, ordem) VALUES (?, ?, ?)', [$avaliacaoId, $enunciado, $ordem]);
                    $stmt = $pdo->prepare('INSERT INTO alternativas_avaliacao (pergunta_id, texto, correta, ordem) VALUES (?, ?, ?, ?)');
                    foreach ($alternativas as $indice => $texto) {
                        $stmt->execute([$perguntaId, $texto, $indice === $correta ? 1 : 0, $indice + 1]);
                    }
                    registrarAtividade($pdo, $usuarioId, 'adicionou uma pergunta', $enunciado);
                    $pdo->commit();
                    $sucesso = 'Pergunta adicionada.';
                }
            } elseif ($acao === 'excluir_pergunta') {
                $perguntaId = (int) ($_POST['pergunta_id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM perguntas_avaliacao WHERE id = ?');
                $stmt->execute([$perguntaId]);
                $sucesso = 'Pergunta removida.';
            } elseif ($acao === 'excluir_avaliacao') {
                $stmt = $pdo->prepare('SELECT titulo FROM avaliacoes WHERE id = ?');
                $stmt->execute([$avaliacaoId]);
                $titulo = $stmt->fetchColumn();
                if ($titulo !== false) {
                    $stmt = $pdo->prepare('DELETE FROM avaliacoes WHERE id = ?');
                    $stmt->execute([$avaliacaoId]);
                    registrarAtividade($pdo, $usuarioId, 'removeu a avaliação', (string) $titulo);
                }
                $avaliacaoId = 0;
                $sucesso = 'Avaliação removida.';
            } elseif ($acao === 'responder') {
                $stmt = $pdo->prepare("SELECT av.*, t.titulo AS treinamento_titulo FROM avaliacoes av JOIN treinamentos t ON t.id = av.treinamento_id WHERE av.id = ? AND av.ativa = TRUE AND t.status = 'publicado' LIMIT 1");
                $stmt->execute([$avaliacaoId]);
                $avaliacaoResponder = $stmt->fetch();
                if (!$avaliacaoResponder) {
                    $erro = 'Avaliação indisponível.';
                } else {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM tentativas_avaliacao WHERE avaliacao_id = ? AND usuario_id = ? AND finalizado_em IS NOT NULL');
                    $stmt->execute([$avaliacaoId, $usuarioId]);
                    $tentativasUsadas = (int) $stmt->fetchColumn();
                    if ($tentativasUsadas >= (int) $avaliacaoResponder['max_tentativas']) {
                        $erro = 'Você atingiu o limite de tentativas desta avaliação.';
                    } else {
                        $stmt = $pdo->prepare('SELECT p.id AS pergunta_id, a.id AS alternativa_id, a.correta FROM perguntas_avaliacao p JOIN alternativas_avaliacao a ON a.pergunta_id = p.id WHERE p.avaliacao_id = ? ORDER BY p.ordem, a.ordem');
                        $stmt->execute([$avaliacaoId]);
                        $opcoes = $stmt->fetchAll();
                        $porPergunta = [];
                        foreach ($opcoes as $opcao) {
                            $porPergunta[(int) $opcao['pergunta_id']][(int) $opcao['alternativa_id']] = valorBooleano($opcao['correta']);
                        }
                        if (!$porPergunta) {
                            $erro = 'A avaliação ainda não possui perguntas.';
                        } else {
                            $respostas = (array) ($_POST['respostas'] ?? []);
                            $acertos = 0;
                            $pdo->beginTransaction();
                            $tentativaId = inserirComId($pdo, 'INSERT INTO tentativas_avaliacao (avaliacao_id, usuario_id) VALUES (?, ?)', [$avaliacaoId, $usuarioId]);
                            $stmtResposta = $pdo->prepare('INSERT INTO respostas_avaliacao (tentativa_id, pergunta_id, alternativa_id, correta) VALUES (?, ?, ?, ?)');
                            foreach ($porPergunta as $perguntaId => $alternativasPermitidas) {
                                $alternativaId = (int) ($respostas[$perguntaId] ?? 0);
                                $alternativaValida = array_key_exists($alternativaId, $alternativasPermitidas) ? $alternativaId : null;
                                $correta = $alternativaValida !== null && $alternativasPermitidas[$alternativaValida];
                                $acertos += $correta ? 1 : 0;
                                $stmtResposta->execute([$tentativaId, $perguntaId, $alternativaValida, $correta ? 1 : 0]);
                            }
                            $nota = round(($acertos / count($porPergunta)) * 10, 2);
                            $aprovado = $nota >= (float) $avaliacaoResponder['nota_minima'];
                            $stmt = $pdo->prepare('UPDATE tentativas_avaliacao SET nota = ?, aprovado = ?, finalizado_em = CURRENT_TIMESTAMP WHERE id = ?');
                            $stmt->execute([$nota, $aprovado ? 1 : 0, $tentativaId]);
                            registrarAtividade($pdo, $usuarioId, 'concluiu a avaliação', (string) $avaliacaoResponder['titulo']);
                            $pdo->commit();
                            $resultado = ['nota' => $nota, 'aprovado' => $aprovado, 'acertos' => $acertos, 'total' => count($porPergunta)];
                            $sucesso = 'Avaliação concluída.';
                        }
                    }
                }
            } else {
                $erro = 'Ação inválida.';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            registrarErro($pdo, 'gerenciamento de avaliacoes', $e);
            $erro = 'Não foi possível concluir a operação agora.';
        }
    }
}

$treinamentos = $pdo->query('SELECT id, titulo FROM treinamentos ORDER BY titulo')->fetchAll();
$sqlAvaliacoes = "SELECT av.*, t.titulo AS treinamento_titulo, COUNT(p.id) AS total_perguntas FROM avaliacoes av JOIN treinamentos t ON t.id = av.treinamento_id LEFT JOIN perguntas_avaliacao p ON p.avaliacao_id = av.id";
$filtros = [];
$params = [];
if (!$ehAdmin) { $filtros[] = "av.ativa = TRUE AND t.status = 'publicado'"; }
if ($treinamentoId > 0) { $filtros[] = 'av.treinamento_id = ?'; $params[] = $treinamentoId; }
if ($filtros) { $sqlAvaliacoes .= ' WHERE ' . implode(' AND ', $filtros); }
$sqlAvaliacoes .= ' GROUP BY av.id, t.titulo ORDER BY av.criado_em DESC';
$stmt = $pdo->prepare($sqlAvaliacoes); $stmt->execute($params); $avaliacoes = $stmt->fetchAll();

$avaliacaoSelecionada = null; $perguntas = [];
if ($avaliacaoId > 0) {
    $stmt = $pdo->prepare('SELECT av.*, t.titulo AS treinamento_titulo FROM avaliacoes av JOIN treinamentos t ON t.id = av.treinamento_id WHERE av.id = ? LIMIT 1');
    $stmt->execute([$avaliacaoId]); $avaliacaoSelecionada = $stmt->fetch();
    if ($avaliacaoSelecionada) {
        $stmt = $pdo->prepare('SELECT p.id AS pergunta_id, p.enunciado, p.ordem, a.id AS alternativa_id, a.texto, a.correta FROM perguntas_avaliacao p LEFT JOIN alternativas_avaliacao a ON a.pergunta_id = p.id WHERE p.avaliacao_id = ? ORDER BY p.ordem, a.ordem');
        $stmt->execute([$avaliacaoId]);
        foreach ($stmt->fetchAll() as $linha) {
            $pid = (int) $linha['pergunta_id'];
            $perguntas[$pid]['enunciado'] = $linha['enunciado'];
            $perguntas[$pid]['ordem'] = $linha['ordem'];
            if ($linha['alternativa_id'] !== null) { $perguntas[$pid]['alternativas'][] = $linha; }
        }
    }
}
$stmt = $pdo->prepare('SELECT ta.nota, ta.aprovado, ta.finalizado_em, av.titulo, t.titulo AS treinamento_titulo FROM tentativas_avaliacao ta JOIN avaliacoes av ON av.id = ta.avaliacao_id JOIN treinamentos t ON t.id = av.treinamento_id WHERE ta.usuario_id = ? AND ta.finalizado_em IS NOT NULL ORDER BY ta.finalizado_em DESC LIMIT 10');
$stmt->execute([$usuarioId]); $historico = $stmt->fetchAll();
?><!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TrainHub - Avaliações</title><link rel="stylesheet" href="css/app.css?v=1.0"><style>.avaliacao-card{display:grid;gap:10px}.pergunta{padding:16px;border-radius:11px;background:var(--th-card)}.alternativa{display:flex;gap:9px;align-items:flex-start;margin-top:9px;color:var(--th-muted)}.alternativa input{margin-top:2px}.resultado{padding:22px;text-align:center}.resultado strong{display:block;font-size:34px}.questoes-admin{display:grid;gap:10px;margin-top:15px}.correta{color:var(--th-green)}</style></head>
<body><header class="th-topbar"><a class="th-logo" href="inicio.php"><img src="assets/logo.png" alt="TrainHub"></a><a class="th-avatar" href="perfil.php">●</a></header><main class="th-shell"><a class="th-back" href="<?php echo $ehAdmin ? 'dashboard.php' : 'inicio.php'; ?>">←</a><div class="th-heading"><div><h1>Avaliações</h1><p><?php echo $ehAdmin ? 'Crie avaliações e acompanhe os resultados.' : 'Responda às avaliações dos seus treinamentos.'; ?></p></div></div>
<?php if ($erro !== ''): ?><p class="th-alert th-alert-error"><?php echo esc($erro); ?></p><?php endif; ?><?php if ($sucesso !== ''): ?><p class="th-alert th-alert-success"><?php echo esc($sucesso); ?></p><?php endif; ?>
<?php if ($resultado): ?><section class="th-panel resultado"><strong><?php echo number_format((float) $resultado['nota'], 1, ',', ''); ?></strong><p><?php echo $resultado['acertos']; ?> de <?php echo $resultado['total']; ?> respostas corretas</p><span class="th-badge <?php echo $resultado['aprovado'] ? 'th-badge-success' : 'th-badge-danger'; ?>"><?php echo $resultado['aprovado'] ? 'Aprovado' : 'Reprovado'; ?></span></section><?php endif; ?>
<div class="th-grid" style="margin-top:20px"><section class="th-panel"><h2>Avaliações disponíveis</h2><div class="th-list"><?php if (!$avaliacoes): ?><p class="th-empty">Nenhuma avaliação disponível.</p><?php endif; ?><?php foreach ($avaliacoes as $avaliacao): ?><article class="th-card avaliacao-card"><div class="th-card-row"><div><span class="th-meta"><?php echo esc($avaliacao['treinamento_titulo']); ?></span><h3><?php echo esc($avaliacao['titulo']); ?></h3></div><span class="th-badge"><?php echo (int) $avaliacao['total_perguntas']; ?> perguntas</span></div><p><?php echo esc($avaliacao['descricao'] ?: 'Sem descrição.'); ?></p><div class="th-card-row"><span class="th-meta">Nota mínima <?php echo esc($avaliacao['nota_minima']); ?> · até <?php echo (int) $avaliacao['max_tentativas']; ?> tentativas</span><div class="th-actions"><a class="th-button" href="avaliacoes.php?id=<?php echo (int) $avaliacao['id']; ?>">Abrir</a><?php if ($ehAdmin): ?><form method="post" onsubmit="return confirm('Remover esta avaliação e suas tentativas?')"><?php echo csrfInput(); ?><input type="hidden" name="acao" value="excluir_avaliacao"><input type="hidden" name="avaliacao_id" value="<?php echo (int) $avaliacao['id']; ?>"><button class="th-button th-button-danger">Remover</button></form><?php endif; ?></div></div></article><?php endforeach; ?></div></section>
<aside class="th-panel"><?php if ($ehAdmin): ?><h2>Nova avaliação</h2><form class="th-form" method="post"><?php echo csrfInput(); ?><input type="hidden" name="acao" value="salvar_avaliacao"><label class="th-field">Treinamento<select name="treinamento_id" required><option value="">Selecione</option><?php foreach ($treinamentos as $treinamento): ?><option value="<?php echo (int) $treinamento['id']; ?>"><?php echo esc($treinamento['titulo']); ?></option><?php endforeach; ?></select></label><label class="th-field">Título<input name="titulo" maxlength="200" required></label><label class="th-field">Descrição<textarea name="descricao"></textarea></label><div class="th-form-row"><label class="th-field">Nota mínima<input name="nota_minima" type="number" min="0" max="10" step="0.1" value="6" required></label><label class="th-field">Tentativas<input name="max_tentativas" type="number" min="1" max="10" value="3" required></label></div><label><input name="ativa" type="checkbox" checked> Disponível para os alunos</label><button class="th-button">Cadastrar avaliação</button></form><?php else: ?><h2>Seu histórico</h2><div class="th-list"><?php if (!$historico): ?><p class="th-empty">Você ainda não concluiu avaliações.</p><?php endif; ?><?php foreach ($historico as $tentativa): ?><div class="th-card"><strong><?php echo esc($tentativa['titulo']); ?></strong><p class="th-meta"><?php echo esc($tentativa['treinamento_titulo']); ?></p><span class="th-badge <?php echo valorBooleano($tentativa['aprovado']) ? 'th-badge-success' : 'th-badge-danger'; ?>"><?php echo number_format((float) $tentativa['nota'], 1, ',', ''); ?> · <?php echo valorBooleano($tentativa['aprovado']) ? 'Aprovado' : 'Reprovado'; ?></span></div><?php endforeach; ?></div><?php endif; ?></aside></div>
<?php if ($avaliacaoSelecionada): ?><section class="th-panel" style="margin-top:22px"><h2><?php echo esc($avaliacaoSelecionada['titulo']); ?></h2><p class="th-meta"><?php echo esc($avaliacaoSelecionada['treinamento_titulo']); ?></p><?php if ($ehAdmin): ?><form class="th-form" method="post" style="margin-top:16px"><?php echo csrfInput(); ?><input type="hidden" name="acao" value="adicionar_pergunta"><input type="hidden" name="avaliacao_id" value="<?php echo $avaliacaoId; ?>"><label class="th-field">Enunciado<textarea name="enunciado" required></textarea></label><?php for ($i=0;$i<4;$i++): ?><div class="th-card-row"><label class="th-field" style="flex:1">Alternativa <?php echo $i+1; ?><input name="alternativas[]" required></label><label><input type="radio" name="correta" value="<?php echo $i; ?>" required> correta</label></div><?php endfor; ?><button class="th-button">Adicionar pergunta</button></form><div class="questoes-admin"><?php foreach ($perguntas as $pid=>$pergunta): ?><div class="pergunta"><div class="th-card-row"><strong><?php echo (int) $pergunta['ordem']; ?>. <?php echo esc($pergunta['enunciado']); ?></strong><form method="post" onsubmit="return confirm('Remover esta pergunta?')"><?php echo csrfInput(); ?><input type="hidden" name="acao" value="excluir_pergunta"><input type="hidden" name="avaliacao_id" value="<?php echo $avaliacaoId; ?>"><input type="hidden" name="pergunta_id" value="<?php echo $pid; ?>"><button class="th-button th-button-danger">Remover</button></form></div><?php foreach ($pergunta['alternativas'] ?? [] as $alternativa): ?><div class="<?php echo valorBooleano($alternativa['correta']) ? 'correta' : 'th-meta'; ?>">• <?php echo esc($alternativa['texto']); ?></div><?php endforeach; ?></div><?php endforeach; ?></div><?php else: ?><?php if (!$perguntas): ?><p class="th-empty">Esta avaliação ainda não possui perguntas.</p><?php else: ?><form class="th-form" method="post"><?php echo csrfInput(); ?><input type="hidden" name="acao" value="responder"><input type="hidden" name="avaliacao_id" value="<?php echo $avaliacaoId; ?>"><?php foreach ($perguntas as $pid=>$pergunta): ?><fieldset class="pergunta"><legend><strong><?php echo (int) $pergunta['ordem']; ?>. <?php echo esc($pergunta['enunciado']); ?></strong></legend><?php foreach ($pergunta['alternativas'] ?? [] as $alternativa): ?><label class="alternativa"><input type="radio" name="respostas[<?php echo $pid; ?>]" value="<?php echo (int) $alternativa['alternativa_id']; ?>" required><span><?php echo esc($alternativa['texto']); ?></span></label><?php endforeach; ?></fieldset><?php endforeach; ?><button class="th-button">Finalizar avaliação</button></form><?php endif; ?><?php endif; ?></section><?php endif; ?>
</main></body></html>
