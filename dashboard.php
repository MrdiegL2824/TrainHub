<?php
require_once 'config.php';
exigirLogin();

if (!temPerfil('admin')) {
    http_response_code(403);
    exit('Acesso restrito aos administradores.');
}

$mensagemSuporte = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validarCsrf($_POST['csrf_token'] ?? null)) {
    $suporteId = (int) ($_POST['suporte_id'] ?? 0);
    if (($_POST['acao'] ?? '') === 'resolver_suporte' && $suporteId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE pedidos_suporte SET status = 'resolvido', atualizado_em = CURRENT_TIMESTAMP WHERE id = ? AND status = 'pendente'");
            $stmt->execute([$suporteId]);
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("INSERT INTO atividades (usuario_id, acao, descricao) VALUES (?, 'resolveu o pedido de suporte', 'pedido de suporte')");
                $stmt->execute([(int) $_SESSION['usuario_id']]);
                $mensagemSuporte = 'Pedido de suporte marcado como resolvido.';
            }
        } catch (Throwable $e) {
            error_log('TrainHub support resolution failed: ' . $e->getMessage());
            $mensagemSuporte = 'Não foi possível atualizar o pedido de suporte.';
        }
    }
}

$tiposMetricas = [
    'usuarios' => 'Usuários',
    'treinamentos' => 'Treinamentos',
    'aulas' => 'Aulas',
    'pendentes' => 'Pendentes',
    'materiais' => 'Materiais',
];
$rotasMetricas = [
    'usuarios' => 'usuarios.php',
    'treinamentos' => 'treinamentos.php',
    'aulas' => 'aulas.php',
    'pendentes' => 'suportes.php?status=pendente',
    'materiais' => 'materiais.php',
];

try {
    $resumo = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM usuarios WHERE ativo = TRUE) AS usuarios,
            (SELECT COUNT(*) FROM treinamentos) AS treinamentos,
            (SELECT COUNT(*) FROM aulas) AS aulas,
            (SELECT COUNT(*) FROM pedidos_suporte WHERE status <> 'resolvido') AS pendentes,
            (SELECT COUNT(*) FROM materiais) AS materiais,
            (SELECT COALESCE(AVG(nota), 0) FROM tentativas_avaliacao WHERE finalizado_em IS NOT NULL) AS avaliacao_media,
            (SELECT COUNT(*) FROM logs_erros) AS total_erros"
    )->fetch() ?: [];
    $metricas = [
        'usuarios' => (int) ($resumo['usuarios'] ?? 0),
        'treinamentos' => (int) ($resumo['treinamentos'] ?? 0),
        'aulas' => (int) ($resumo['aulas'] ?? 0),
        'pendentes' => (int) ($resumo['pendentes'] ?? 0),
        'materiais' => (int) ($resumo['materiais'] ?? 0),
    ];

    $eventos = $pdo->query(
        "(SELECT 'atividade' AS tipo, u.nome, a.acao, a.descricao AS item, a.criado_em,
                 NULL AS email, NULL AS mensagem, NULL AS status
          FROM atividades a
          JOIN usuarios u ON u.id = a.usuario_id
          ORDER BY a.criado_em DESC
          LIMIT 8)
         UNION ALL
         (SELECT 'treinamento' AS tipo, u.nome, 'criou o treinamento' AS acao, t.titulo AS item, t.criado_em,
                 NULL AS email, NULL AS mensagem, NULL AS status
          FROM treinamentos t
          JOIN usuarios u ON u.id = t.criado_por
          ORDER BY t.criado_em DESC
          LIMIT 8)
         UNION ALL
         (SELECT 'suporte' AS tipo, u.nome, 'enviou um pedido de suporte' AS acao, s.mensagem AS item, s.criado_em,
                 u.email, s.mensagem, s.status
          FROM pedidos_suporte s
          JOIN usuarios u ON u.id = s.usuario_id
          ORDER BY s.criado_em DESC
          LIMIT 5)
         ORDER BY criado_em DESC"
    )->fetchAll();
    $atividades = array_values(array_filter($eventos, static fn (array $evento): bool => $evento['tipo'] !== 'suporte'));
    $suportes = array_values(array_filter($eventos, static fn (array $evento): bool => $evento['tipo'] === 'suporte' && $evento['status'] === 'pendente'));
    $avaliacaoMedia = (float) ($resumo['avaliacao_media'] ?? 0);
    $avaliacoesRecentes = $pdo->query(
        "SELECT u.nome, av.titulo, ta.nota, ta.aprovado
         FROM tentativas_avaliacao ta
         JOIN usuarios u ON u.id = ta.usuario_id
         JOIN avaliacoes av ON av.id = ta.avaliacao_id
         WHERE ta.finalizado_em IS NOT NULL
         ORDER BY ta.finalizado_em DESC LIMIT 3"
    )->fetchAll();
    $totalErros = (int) ($resumo['total_erros'] ?? 0);
} catch (Throwable $e) {
    registrarErro($pdo, 'dashboard administrativo', $e);
    $metricas = array_fill_keys(array_keys($tiposMetricas), 0);
    $atividades = [];
    $suportes = [];
    $avaliacaoMedia = 0;
    $avaliacoesRecentes = [];
    $totalErros = 0;
}

$atividadesPorDia = [];
$agora = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
$hoje = $agora->setTime(0, 0, 0);
foreach (array_slice($atividades, 0, 8) as $atividade) {
    try {
        $dataAtividade = (new DateTimeImmutable((string) $atividade['criado_em']))->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        $dias = (int) $hoje->diff($dataAtividade->setTime(0, 0, 0))->format('%r%a');
        $rotulo = $dias === 0 ? 'Hoje' : ($dias === -1 ? 'Ontem' : $dataAtividade->format('d/m'));
    } catch (Throwable $e) {
        $rotulo = 'Hoje';
    }
    $atividadesPorDia[$rotulo][] = $atividade;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TrainHub - Dashboard Administrativo</title>
    <style>
        :root {
            color-scheme: dark;
            --fundo: #293346;
            --barra: #343a43;
            --painel: #3c4961;
            --escuro: #202838;
            --texto: #f4f5f8;
            --secundario: #b5bdcb;
            --vermelho: #d91d27;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-width: 0;
            min-height: 100%;
        }

        body {
            margin: 0;
            background: var(--fundo);
            color: var(--texto);
            font-family: Arial, Helvetica, sans-serif;
        }

        a {
            color: inherit;
        }

        .admin-topo {
            height: 47px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 14px;
            background: var(--barra);
            border-bottom: 1px solid rgba(12, 18, 29, .45);
        }

        .admin-logo {
            display: flex;
            align-items: center;
            width: 30px;
            height: 34px;
            position: relative;
            border: 0;
            outline: 0;
            box-shadow: none;
            opacity: .82;
        }

        .admin-logo::after {
            content: attr(data-tooltip);
            position: absolute;
            top: 36px;
            left: 0;
            z-index: 10;
            pointer-events: none;
            white-space: nowrap;
            border-radius: 5px;
            padding: 5px 8px;
            background: #111722;
            color: #eef2fb;
            font-size: 11px;
            opacity: 0;
            transform: translateY(-3px);
            transition: opacity .15s ease, transform .15s ease;
        }

        .admin-logo:hover::after,
        .admin-logo:focus-visible::after {
            opacity: 1;
            transform: translateY(0);
        }

        .admin-logo img {
            display: block;
            width: 30px;
            height: 30px;
            object-fit: contain;
            border: 0;
            outline: 0;
        }

        .admin-avatar {
            position: relative;
            display: block;
            width: 36px;
            height: 36px;
            border: 1px solid #080b10;
            border-radius: 50%;
            background: #05070b;
            text-decoration: none;
        }

        .admin-avatar::before {
            content: '';
            position: absolute;
            top: 6px;
            left: 11px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #adb4c0;
        }

        .admin-avatar::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 6px;
            width: 22px;
            height: 12px;
            border-radius: 14px 14px 5px 5px;
            background: #adb4c0;
        }

        .admin-pagina {
            position: relative;
            width: 84vw;
            margin: 0 auto;
            padding: 27px 0 45px;
        }

        .admin-voltar {
            position: absolute;
            top: 16px;
            left: -64px;
            display: inline-flex;
            align-items: center;
            height: 27px;
            color: #f1f3f7;
            font-size: 32px;
            line-height: 1;
            text-decoration: none;
        }

        .admin-voltar:hover {
            color: #fff;
        }

        .metricas {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 20px;
            margin-top: 0;
            padding: 12px 24px 13px;
            border-radius: 17px;
            background: var(--painel);
        }

        .metrica {
            display: flex;
            min-width: 0;
            min-height: 56px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--escuro);
            text-decoration: none;
            transition: background .15s ease, transform .15s ease;
        }

        .metrica:hover,
        .metrica:focus-visible {
            background: #29344a;
            outline: none;
            transform: translateY(-1px);
        }

        .metrica strong {
            font-size: 20px;
            line-height: 22px;
        }

        .metrica span {
            font-size: 16px;
            font-weight: 700;
            line-height: 17px;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            grid-template-rows: 286px 83px;
            gap: 31px 19px;
            margin-top: 19px;
        }

        .admin-box {
            min-width: 0;
            overflow: hidden;
            border-radius: 16px;
            background: var(--painel);
        }

        .atividades-box {
            grid-row: 1 / 3;
            height: 402px;
        }

        .box-titulo {
            display: flex;
            min-height: 34px;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 7px 10px;
            background: var(--escuro);
            font-size: 18px;
            font-weight: 700;
            line-height: 20px;
        }

        .atividade-conteudo {
            padding: 9px 9px 14px;
        }

        .atividade-dia {
            margin: 0 0 3px;
            font-size: 12px;
            font-weight: 700;
        }

        .atividade-item {
            margin: 0 0 3px;
            overflow: hidden;
            padding: 1px 7px;
            border-radius: 7px;
            background: var(--escuro);
            color: #e5e8ef;
            font-size: 13px;
            line-height: 16px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .suporte-cabecalho {
            position: relative;
        }

        .suporte-badge,
        .erro-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            display: grid;
            width: 17px;
            height: 17px;
            place-items: center;
            border-radius: 50%;
            background: var(--vermelho);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
        }

        .suporte-lista {
            display: grid;
            gap: 4px;
            padding: 5px 7px 8px;
        }

        .suporte-item {
            position: relative;
            min-height: 48px;
            padding: 7px 8px 5px 18px;
            border-radius: 9px;
            background: var(--escuro);
            color: #e8ebf1;
            font-size: 10px;
            line-height: 12px;
        }

        .suporte-item::before {
            content: '';
            position: absolute;
            top: 13px;
            left: 7px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--vermelho);
        }

        .suporte-item strong {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .suporte-email,
        .suporte-mensagem {
            display: block;
            overflow: hidden;
            color: var(--secundario);
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .suporte-mensagem {
            color: #e8ebf1;
        }

        .suporte-resolver {
            display: block;
            margin-top: 5px;
            border: 0;
            border-radius: 5px;
            padding: 3px 6px;
            background: #2a3c5d;
            color: #dce5f7;
            font-size: 9px;
            cursor: pointer;
        }

        .suporte-resolver:hover {
            background: #436bc7;
        }

        .suporte-vazio {
            display: flex;
            height: 224px;
            align-items: flex-end;
            justify-content: center;
            margin: 0;
            padding: 12px;
            color: #aeb7c7;
            font-size: 12px;
            text-align: center;
        }

        .espaco-livre {
            background: var(--painel);
        }

        .avaliacao-media { display:block; width:max-content; margin:8px auto; padding:4px 11px; border-radius:12px; background:var(--escuro); color:#ffdc36; font-weight:700; }
        .avaliacao-lista { display:grid; gap:5px; padding:0 7px 8px; }
        .avaliacao-item { padding:7px 8px; border-radius:8px; background:var(--escuro); font-size:10px; line-height:12px; }
        .avaliacao-item strong, .avaliacao-item span { display:block; }
        .avaliacao-item span { color:var(--secundario); }
        .avaliacao-vazio { padding:22px 10px; color:var(--secundario); font-size:11px; text-align:center; }

        .suporte-box,
        .admin-grid > .espaco-livre {
            align-self: start;
            height: 274px;
            margin-top: 0;
        }

        .rodape-box {
            grid-column: 2 / 5;
            display: flex;
            gap: 20px;
            align-items: center;
            justify-content: space-around;
            padding: 13px 84px;
        }

        .erros-link,
        .rodape-vazio {
            display: flex;
            width: 176px;
            height: 66px;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--escuro);
            color: #f1f2f5;
            font-size: 20px;
            font-weight: 700;
            text-decoration: none;
        }

        .erro-acao {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .erro-badge {
            position: static;
            flex: 0 0 auto;
            width: 23px;
            height: 23px;
            font-size: 12px;
        }

        .erros-link:hover,
        .erros-link:focus-visible {
            background: #29344a;
            outline: none;
        }

        @media (max-width: 900px) {
            .admin-pagina {
                width: calc(100% - 32px);
            }

            .admin-voltar {
                left: 0;
            }

            .metricas {
                gap: 10px;
                padding-left: 14px;
                padding-right: 14px;
            }

            .metrica strong {
                font-size: 16px;
            }

            .metrica span {
                font-size: 14px;
            }
        }

        @media (max-width: 700px) {
            .admin-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                grid-template-rows: auto;
            }

            .atividades-box {
                grid-row: auto;
                height: auto;
                min-height: 274px;
            }

            .suporte-box,
            .admin-grid > .espaco-livre {
                height: auto;
                margin-top: 0;
                min-height: 274px;
            }

            .rodape-box {
                grid-column: 1 / 3;
                padding-left: 20px;
                padding-right: 20px;
            }

            .suporte-vazio {
                height: 224px;
            }
        }

        @media (max-width: 500px) {
            .admin-topo {
                padding: 0 9px;
            }

            .admin-pagina {
                width: calc(100% - 24px);
                padding-top: 12px;
            }

            .metricas {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .metricas .metrica:last-child {
                grid-column: 1 / 3;
            }

            .admin-grid {
                grid-template-columns: 1fr;
            }

            .rodape-box {
                grid-column: auto;
                min-height: 83px;
                flex-wrap: wrap;
                padding: 13px;
            }
        }
    </style>
</head>
<body>
    <header class="admin-topo">
        <a class="admin-logo" href="inicio.php" aria-label="Voltar para o início" title="Voltar para a área inicial" data-tooltip="Voltar para a área inicial">
            <img src="assets/logo.png" alt="TrainHub">
        </a>
        <a class="admin-avatar" href="perfil.php" aria-label="Abrir perfil"></a>
    </header>

    <main class="admin-pagina">
        <a class="admin-voltar" href="inicio.php" aria-label="Voltar para o início">←</a>

        <nav class="metricas" aria-label="Gerenciamento administrativo">
            <?php foreach ($tiposMetricas as $tipo => $rotulo): ?>
                <a class="metrica" href="<?php echo esc($rotasMetricas[$tipo]); ?>">
                    <strong><?php echo esc($rotulo); ?></strong>
                    <span><?php echo (int) ($metricas[$tipo] ?? 0); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <section class="admin-grid" aria-label="Resumo administrativo">
            <section class="admin-box atividades-box">
                <h2 class="box-titulo">Atividades recentes</h2>
                <div class="atividade-conteudo">
                    <?php if (!$atividadesPorDia): ?>
                        <p class="atividade-dia">Hoje</p>
                        <p class="atividade-item">Nenhuma atividade registrada.</p>
                    <?php else: ?>
                        <?php foreach ($atividadesPorDia as $dia => $itensDoDia): ?>
                            <p class="atividade-dia"><?php echo esc($dia); ?></p>
                            <?php foreach ($itensDoDia as $atividade): ?>
                                <p class="atividade-item">
                                    -<?php echo esc($atividade['nome']); ?> <?php echo esc($atividade['acao']); ?> "<?php echo esc($atividade['item']); ?>"
                                </p>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="admin-box suporte-box">
                <h2 class="box-titulo suporte-cabecalho">
                    Pedidos de suporte
                    <?php if ($suportes): ?>
                        <span class="suporte-badge" aria-label="<?php echo count($suportes); ?> pedidos pendentes"><?php echo count($suportes); ?></span>
                    <?php endif; ?>
                </h2>
                <?php if ($mensagemSuporte !== ''): ?><p class="suporte-vazio" style="height:auto;padding:8px 10px;color:#9de0bd;"><?php echo esc($mensagemSuporte); ?></p><?php endif; ?>
                <?php if (!$suportes): ?>
                    <p class="suporte-vazio">Nenhum pedido de suporte pendente.</p>
                <?php else: ?>
                    <div class="suporte-lista">
                        <?php foreach ($suportes as $suporte): ?>
                            <article class="suporte-item">
                                <strong><?php echo esc($suporte['nome']); ?></strong>
                                <span class="suporte-mensagem"><?php echo esc($suporte['mensagem']); ?></span>
                                <span class="suporte-email"><?php echo esc($suporte['email']); ?></span>
                                <a class="suporte-resolver" href="suportes.php?status=pendente">Abrir pedido</a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <div class="admin-box espaco-livre" aria-hidden="true"></div>
            <section class="admin-box suporte-box">
                <h2 class="box-titulo">Avaliações</h2>
                <span class="avaliacao-media"><?php echo number_format($avaliacaoMedia, 1, ',', ''); ?> ★</span>
                <?php if (!$avaliacoesRecentes): ?><p class="avaliacao-vazio">Nenhuma avaliação respondida.</p><?php else: ?><div class="avaliacao-lista"><?php foreach ($avaliacoesRecentes as $avaliacao): ?><article class="avaliacao-item"><strong><?php echo esc($avaliacao['nome']); ?></strong><span><?php echo esc($avaliacao['titulo']); ?></span><span><?php echo number_format((float) $avaliacao['nota'], 1, ',', ''); ?> · <?php echo valorBooleano($avaliacao['aprovado']) ? 'Aprovado' : 'Reprovado'; ?></span></article><?php endforeach; ?></div><?php endif; ?>
            </section>

            <section class="admin-box rodape-box" aria-label="Log de erros">
                <div class="erro-acao"><a class="erros-link" href="logs-erros.php">Log de erros</a><?php if ($totalErros > 0): ?><span class="erro-badge" aria-label="<?php echo $totalErros; ?> erros registrados"><?php echo $totalErros; ?></span><?php endif; ?></div>
                <a class="rodape-vazio cadastro-admin-link" href="admin-cadastrar-usuario.php" aria-label="Cadastrar usuário">Cadastrar cliente</a>
                <a class="rodape-vazio cadastro-admin-link" href="avaliacoes.php" aria-label="Gerenciar avaliações">Avaliações</a>
            </section>
        </section>
    </main>
</body>
</html>
