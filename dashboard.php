<?php
require_once 'config.php';
exigirLogin();

if (!temPerfil('admin')) {
    http_response_code(403);
    exit('Acesso restrito aos administradores.');
}

$tiposMetricas = [
    'usuarios' => 'Usuários',
    'treinamentos' => 'Treinamentos',
    'aulas' => 'Aulas',
    'pendentes' => 'Pendentes',
    'materiais' => 'Materiais',
];

try {
    $metricas = [
        'usuarios' => (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE ativo = TRUE')->fetchColumn(),
        'treinamentos' => (int) $pdo->query('SELECT COUNT(*) FROM treinamentos')->fetchColumn(),
        'aulas' => (int) $pdo->query('SELECT COUNT(*) FROM aulas')->fetchColumn(),
        'pendentes' => (int) $pdo->query("SELECT COUNT(*) FROM treinamentos WHERE status = 'rascunho'")->fetchColumn(),
        'materiais' => (int) $pdo->query('SELECT COUNT(*) FROM materiais')->fetchColumn(),
    ];

    $atividades = $pdo->query(
        "SELECT u.nome, 'criou o treinamento' AS acao, t.titulo AS item, t.criado_em
         FROM treinamentos t
         JOIN usuarios u ON u.id = t.criado_por
         UNION ALL
         SELECT u.nome, 'enviou uma solicitação' AS acao, 'pedido de suporte' AS item, s.criado_em
         FROM pedidos_suporte s
         JOIN usuarios u ON u.id = s.usuario_id
         ORDER BY criado_em DESC
         LIMIT 8"
    )->fetchAll();

    $suportes = $pdo->query(
        "SELECT s.id, u.nome, u.email, s.mensagem, s.status, s.criado_em
         FROM pedidos_suporte s
         JOIN usuarios u ON u.id = s.usuario_id
         WHERE s.status = 'pendente'
         ORDER BY s.criado_em DESC
         LIMIT 5"
    )->fetchAll();
} catch (Throwable $e) {
    error_log('TrainHub admin dashboard query failed: ' . $e->getMessage());
    $metricas = array_fill_keys(array_keys($tiposMetricas), 0);
    $atividades = [];
    $suportes = [];
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
            opacity: .82;
        }

        .admin-logo img {
            display: block;
            width: 30px;
            height: 30px;
            object-fit: contain;
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
            font-size: 10px;
            line-height: 12px;
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

        .suporte-box,
        .admin-grid > .espaco-livre {
            align-self: start;
            height: 274px;
            margin-top: 11px;
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
            width: 156px;
            height: 57px;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--escuro);
            color: #f1f2f5;
            font-size: 18px;
            font-weight: 700;
            text-decoration: none;
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
        <a class="admin-logo" href="inicio.php" aria-label="Voltar para o início">
            <img src="assets/logo.png" alt="TrainHub">
        </a>
        <a class="admin-avatar" href="logout.php" aria-label="Sair"></a>
    </header>

    <main class="admin-pagina">
        <a class="admin-voltar" href="inicio.php" aria-label="Voltar para o início">←</a>

        <nav class="metricas" aria-label="Gerenciamento administrativo">
            <?php foreach ($tiposMetricas as $tipo => $rotulo): ?>
                <a class="metrica" href="admin-gerenciar.php?tipo=<?php echo esc($tipo); ?>">
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
                <?php if (!$suportes): ?>
                    <p class="suporte-vazio">Nenhum pedido de suporte pendente.</p>
                <?php else: ?>
                    <div class="suporte-lista">
                        <?php foreach ($suportes as $suporte): ?>
                            <article class="suporte-item">
                                <strong><?php echo esc($suporte['nome']); ?></strong>
                                <span class="suporte-mensagem"><?php echo esc($suporte['mensagem']); ?></span>
                                <span class="suporte-email"><?php echo esc($suporte['email']); ?></span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <div class="admin-box espaco-livre" aria-hidden="true"></div>
            <div class="admin-box espaco-livre" aria-hidden="true"></div>

            <section class="admin-box rodape-box" aria-label="Log de erros">
                <div class="erros-link">Log de erros</div>
                <a class="rodape-vazio cadastro-admin-link" href="admin-cadastrar-usuario.php" aria-label="Cadastrar usuário">Cadastrar cliente</a>
                <div class="rodape-vazio" aria-hidden="true"></div>
            </section>
        </section>
    </main>
</body>
</html>
