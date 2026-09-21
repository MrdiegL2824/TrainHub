<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
exigirLogin();

$usuarioId = (int) $_SESSION['usuario_id'];
$areaSelecionada = max(0, (int) ($_GET['area_id'] ?? 0));
$imagensAreas = [
    'Sistemas' => 'sistemas.png', 'Redes' => 'redes.png', 'Segurança' => 'seguranca.png',
    'Banco de Dados' => 'banco-de-dados.png', 'IA' => 'ia.png', 'Web' => 'web.png',
    'Automação' => 'automacao.png', 'Eletrônica' => 'eletronica.png', 'Gestão' => 'gestao.png',
];

try {
    garantirMatriculasPublicadas($pdo, $usuarioId);
    $sqlCursos = "SELECT t.id, t.titulo, t.descricao, t.carga_horaria, t.status, a.nome AS area_nome,
                         m.ultima_aula_id,
                         m.iniciado_em,
                         COUNT(DISTINCT CASE WHEN au.status = 'publicado' THEN au.id END) AS total_aulas,
                         COUNT(DISTINCT CASE WHEN au.status = 'publicado' AND p.concluida = TRUE THEN au.id END) AS aulas_concluidas
                  FROM matriculas m
                  JOIN treinamentos t ON t.id = m.treinamento_id
                  JOIN areas a ON a.id = t.area_id
                  LEFT JOIN aulas au ON au.treinamento_id = t.id
                  LEFT JOIN progresso_aulas p ON p.aula_id = au.id AND p.usuario_id = m.usuario_id
                  WHERE m.usuario_id = ?";
    $parametros = [$usuarioId];
    if ($areaSelecionada > 0) {
        $sqlCursos .= ' AND t.area_id = ?';
        $parametros[] = $areaSelecionada;
    }
    $sqlCursos .= ' GROUP BY t.id, t.titulo, t.descricao, t.carga_horaria, t.status, a.nome, m.ultima_aula_id, m.iniciado_em ORDER BY m.iniciado_em DESC, t.titulo LIMIT 6';
    $stmt = $pdo->prepare($sqlCursos);
    $stmt->execute($parametros);
    $cursos = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT a.id, a.nome, a.descricao, a.cor,
                COALESCE(SUM(CASE WHEN t.status = 'publicado' THEN t.carga_horaria ELSE 0 END), 0) AS horas,
                MAX(CASE WHEN f.area_id IS NOT NULL THEN 1 ELSE 0 END) AS favorita
         FROM areas a
         LEFT JOIN treinamentos t ON t.area_id = a.id
         LEFT JOIN favoritos_areas f ON f.area_id = a.id AND f.usuario_id = ?
         GROUP BY a.id, a.nome, a.descricao, a.cor
         ORDER BY CASE a.nome WHEN 'Sistemas' THEN 1 WHEN 'Banco de Dados' THEN 2 WHEN 'Automação' THEN 3 WHEN 'Redes' THEN 4 WHEN 'IA' THEN 5 WHEN 'Eletrônica' THEN 6 WHEN 'Segurança' THEN 7 WHEN 'Web' THEN 8 WHEN 'Gestão' THEN 9 ELSE 10 END, a.nome"
    );
    $stmt->execute([$usuarioId]);
    $areas = $stmt->fetchAll();
} catch (Throwable $e) {
    registrarErro($pdo, 'carregamento da tela inicial', $e);
    $cursos = [];
    $areas = [];
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TrainHub - Início</title>
    <style>
        :root{color-scheme:dark;font-family:Arial,Helvetica,sans-serif;--fundo:#273143;--barra:#34373c;--painel:#3a4356;--escuro:#1e2430;--texto:#eef2fb;--muted:#b8c0cf}
        *{box-sizing:border-box}body{margin:0;min-width:0;min-height:100vh;background:var(--fundo);color:var(--texto)}button,input,select{font:inherit}
        .topo{position:relative;height:42px;display:flex;align-items:center;padding:0 10px;background:var(--barra);border-bottom:1px solid #2f343e}.logo{position:relative;display:block;border:0;outline:0;box-shadow:none;text-decoration:none}.logo img{display:block;width:31px;height:31px;object-fit:contain;border:0;outline:0}.logo:after{content:attr(data-tooltip);position:absolute;top:36px;left:0;z-index:10;pointer-events:none;white-space:nowrap;border-radius:5px;padding:5px 8px;background:#111722;color:#eef2fb;font-size:11px;opacity:0;transform:translateY(-3px);transition:opacity .15s ease,transform .15s ease}.logo:hover:after,.logo:focus-visible:after{opacity:1;transform:translateY(0)}.dashboard-link{position:absolute;top:6px;right:17%;padding:6px 11px;border-radius:12px;background:var(--escuro);color:#d6dcec;text-decoration:none;font-size:13px}.perfil{position:absolute;right:7px;top:4px;width:34px;height:34px;border:1px solid #07090d;border-radius:50%;background:#05070c;text-decoration:none}.perfil:before{content:'';position:absolute;top:5px;left:10px;width:11px;height:11px;border-radius:50%;background:#a6adb9}.perfil:after{content:'';position:absolute;bottom:4px;left:6px;width:20px;height:11px;border-radius:12px 12px 4px 4px;background:#a6adb9}
        .conteudo{width:min(1440px,calc(100% - clamp(34px,14vw,240px)));margin:0 auto;padding:28px 0 54px}.cartao{padding:12px 22px 24px;border-radius:14px;background:var(--painel)}.areas{margin-top:30px}.secao-topo{min-height:25px;display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:9px}.secao-topo h1{margin:0;font-size:14px;font-weight:400}.filtros{display:flex;align-items:center;gap:7px}.busca,.select{height:24px;border:0;border-radius:13px;background:#272d3c;color:#e8edf7;font-size:12px;outline:none}.busca{width:145px;padding:3px 9px 3px 24px}.select-wrap{position:relative}.select{width:92px;padding:2px 24px 2px 9px;appearance:none}.select-wrap:after{content:'';position:absolute;right:9px;top:7px;width:7px;height:7px;border-right:2px solid #dbe2ee;border-bottom:2px solid #dbe2ee;transform:rotate(45deg);pointer-events:none}.icone-lista{width:20px;color:#dbe2ee;text-align:center}
        .cursos{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:30px}.curso{min-width:0}.curso-capa{position:relative;height:145px;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:9px;background:#1e2430}.curso-capa:before{content:'';position:absolute;inset:0;background:url('assets/background-TrainHub.png') center/cover;opacity:.12}.curso-titulo{position:relative;z-index:1;color:inherit;text-decoration:none}.curso-titulo:hover h2,.curso-titulo:focus-visible h2{text-decoration:underline;text-underline-offset:3px}.curso-capa h2{position:relative;z-index:1;margin:0;padding:18px;text-align:center;font-size:18px;text-shadow:0 2px 3px #000}.progresso{position:absolute;z-index:2;right:8px;bottom:7px;width:35px;height:35px;display:grid;place-items:center;border:4px solid #1747ff;border-radius:50%;background:var(--escuro);font-size:10px;font-weight:700}.progresso.zero{border-color:#aeb1b8}.curso-rodape{display:flex;align-items:center;justify-content:space-between;margin-top:6px;font-size:12px}.curso-rodape a{color:#c7cfdf;text-decoration:none}.concluido{color:#34c759}.andamento{color:#ffcc00}.encerrado{color:#ff383c}.nao-iniciado{color:#c8ced8}
        .grade-areas{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px 28px}.area-item{min-width:0;display:grid;grid-template-columns:minmax(0,1fr) 23px;gap:7px;align-items:start}.area-link{text-decoration:none}.area-capa{position:relative;height:62px;display:flex;align-items:center;overflow:hidden;padding:0 12px;border-radius:7px;background:var(--escuro)}.area-capa img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.78}.area-capa:after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,rgba(8,14,25,.32),rgba(8,14,25,.13))}.area-capa span{position:relative;z-index:1;color:#fff;font-size:16px;text-shadow:0 1px 3px #000}.area-tags{display:flex;gap:4px;margin-top:3px}.tag{display:inline-flex;height:16px;align-items:center;padding:2px 5px;border-radius:8px;background:#727989;color:#fff;font-size:9px}.favorito-form{margin:0}.estrela{width:22px;height:22px;border:0;background:transparent;color:#fff;font-size:19px;line-height:20px;cursor:pointer;padding:0}.estrela.ativa{color:#ffcc00}.vazio{grid-column:1/-1;padding:38px;border-radius:9px;background:var(--escuro);color:var(--muted);text-align:center}
        @media(max-width:850px){.conteudo{width:calc(100% - 32px)}.cursos,.grade-areas{grid-template-columns:repeat(2,minmax(0,1fr))}.dashboard-link{right:70px}}
        @media(max-width:560px){.conteudo{padding-top:18px}.cartao{padding:12px 14px 20px}.secao-topo{align-items:flex-start;flex-direction:column}.filtros{width:100%}.busca{flex:1;width:auto}.cursos,.grade-areas{grid-template-columns:1fr}.dashboard-link{left:50%;right:auto;transform:translateX(-50%)}}
        .logo img{opacity:1;filter:brightness(1.75) saturate(1.35) contrast(1.1)}
        .icone-lista{width:20px;height:24px;padding:0;border:0;border-radius:4px;background:transparent;color:#dbe2ee;text-align:center;line-height:24px;font-size:16px;cursor:pointer}
        .icone-lista:hover,.icone-lista:focus-visible{color:#fff;background:rgba(255,255,255,.08);outline:1px solid rgba(219,226,238,.35);outline-offset:1px}
        .grade-areas.layout-lista{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 18px}
        @media(max-width:560px){.grade-areas.layout-lista{grid-template-columns:1fr}}
        .item-area[hidden],.item-curso[hidden]{display:none!important}
        .curso-capa{height:165px}
        .area-capa{height:74px}
    </style>
</head>
<body>
<header class="topo"><a class="logo" href="inicio.php" aria-label="Início" title="Voltar para a área inicial" data-tooltip="Voltar para a área inicial"><img src="assets/logo.png" alt="TrainHub"></a><?php if (temPerfil('admin')): ?><a class="dashboard-link" href="dashboard.php">Dashboard</a><?php endif; ?><a class="perfil" href="perfil.php" aria-label="Abrir perfil"></a></header>
<main class="conteudo">
    <section class="cartao">
        <div class="secao-topo"><h1>Meus Cursos</h1><div class="filtros"><input id="busca-cursos" class="busca" type="search" placeholder="⌕  Pesquisar" aria-label="Pesquisar cursos"><span class="select-wrap"><select id="status-cursos" class="select" aria-label="Filtrar cursos"><option value="">Todos</option><option value="concluido">Concluído</option><option value="andamento">Em andamento</option><option value="nao-iniciado">Não iniciado</option><option value="encerrado">Encerrado</option></select></span><span class="icone-lista">☰</span></div></div>
        <div class="cursos" id="lista-cursos">
            <?php if (!$cursos): ?><p class="vazio">Nenhum curso disponível<?php echo $areaSelecionada ? ' nesta área' : ''; ?>.</p><?php endif; ?>
            <?php foreach ($cursos as $curso): ?>
                <?php
                $totalAulas = (int) $curso['total_aulas'];
                $concluidas = (int) $curso['aulas_concluidas'];
                $percentual = $totalAulas > 0 ? (int) round(($concluidas / $totalAulas) * 100) : 0;
                if ($curso['status'] === 'arquivado') { $status = 'encerrado'; $rotulo = 'Encerrado'; }
                elseif ($percentual >= 100) { $status = 'concluido'; $rotulo = 'Concluído'; }
                elseif ($percentual > 0) { $status = 'andamento'; $rotulo = 'Em andamento'; }
                else { $status = 'nao-iniciado'; $rotulo = 'Não iniciado'; }
                ?>
                <?php $cursoUrl = $curso['ultima_aula_id'] ? 'aula-template.php?id=' . (int) $curso['ultima_aula_id'] : 'treinamento-detalhe.php?id=' . (int) $curso['id']; ?>
                <article class="curso item-curso" data-status="<?php echo esc($status); ?>" data-busca="<?php echo esc(mb_strtolower($curso['titulo'] . ' ' . $curso['area_nome'])); ?>"><div class="curso-capa"><a class="curso-titulo" href="<?php echo esc($cursoUrl); ?>"><h2><?php echo esc($curso['titulo']); ?></h2></a><span class="progresso <?php echo $percentual === 0 ? 'zero' : ''; ?>"><?php echo $percentual; ?>%</span></div><div class="curso-rodape"><span class="<?php echo esc($status); ?>"><?php echo esc($rotulo); ?></span><a href="<?php echo esc($cursoUrl); ?>">Abrir →</a></div></article>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="cartao areas" id="areas">
        <div class="secao-topo"><h1>Áreas</h1><div class="filtros"><input id="busca-areas" class="busca" type="search" placeholder="⌕  Pesquisar" aria-label="Pesquisar áreas"><span class="select-wrap"><select id="filtro-areas" class="select" aria-label="Filtrar áreas"><option value="">Todos</option><option value="favorito">Favoritos</option></select></span><span class="icone-lista">⊞</span></div></div>
        <div class="grade-areas" id="lista-areas">
            <?php foreach ($areas as $area): $favorita = valorBooleano($area['favorita']); ?>
                <article class="area-item item-area" data-favorito="<?php echo $favorita ? 'favorito' : ''; ?>" data-busca="<?php echo esc(mb_strtolower($area['nome'] . ' ' . $area['descricao'])); ?>"><a class="area-link" href="inicio.php?area_id=<?php echo (int) $area['id']; ?>"><div class="area-capa"><img src="assets/areas/<?php echo esc($imagensAreas[$area['nome']] ?? 'sistemas.png'); ?>" alt=""><span><?php echo esc($area['nome']); ?></span></div><div class="area-tags"><span class="tag">STG</span><span class="tag"><?php echo esc(rtrim(rtrim(number_format((float) $area['horas'], 1, '.', ''), '0'), '.')); ?>h</span></div></a><form class="favorito-form" method="post" action="api/favorito.php"><?php echo csrfInput(); ?><input type="hidden" name="area_id" value="<?php echo (int) $area['id']; ?>"><button class="estrela <?php echo $favorita ? 'ativa' : ''; ?>" type="submit" aria-label="<?php echo $favorita ? 'Remover dos favoritos' : 'Adicionar aos favoritos'; ?>"><?php echo $favorita ? '★' : '☆'; ?></button></form></article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<script>
function filtrarCursos(){const termo=document.getElementById('busca-cursos').value.toLocaleLowerCase('pt-BR');const status=document.getElementById('status-cursos').value;document.querySelectorAll('.item-curso').forEach(item=>{item.hidden=!(item.dataset.busca.includes(termo)&&(!status||item.dataset.status===status));});}
function filtrarAreas(){const termo=document.getElementById('busca-areas').value.toLocaleLowerCase('pt-BR');const filtro=document.getElementById('filtro-areas').value;let visiveis=0;document.querySelectorAll('.item-area').forEach(item=>{const visivel=item.dataset.busca.includes(termo)&&(!filtro||item.dataset.favorito===filtro);item.hidden=!visivel;if(visivel){visiveis++;}});let vazio=document.getElementById('areas-vazio-filtro');if(!vazio){vazio=document.createElement('p');vazio.id='areas-vazio-filtro';vazio.className='vazio';vazio.textContent='Nenhuma área favorita encontrada.';document.getElementById('lista-areas').appendChild(vazio);}vazio.hidden=visiveis!==0||!filtro;}
function atualizarLayoutAreas(){const lista=document.getElementById('lista-areas');const controle=document.querySelector('.areas .icone-lista');if(!controle){return;}const listaAtiva=lista.classList.contains('layout-lista');controle.textContent=listaAtiva?'\u2637':'\u25A6';controle.setAttribute('aria-label',listaAtiva?'Mostrar áreas em grade':'Mostrar áreas em lista');controle.setAttribute('aria-pressed',listaAtiva?'true':'false');}
const filtroAreas=document.getElementById('filtro-areas');const filtroSalvo=window.localStorage.getItem('trainhub-areas-filtro');if(filtroSalvo==='favorito'){filtroAreas.value='favorito';}const listaAreas=document.getElementById('lista-areas');const layoutSalvo=window.localStorage.getItem('trainhub-areas-layout');if(layoutSalvo==='lista'){listaAreas.classList.add('layout-lista');}const controleLayout=document.querySelector('.areas .icone-lista');if(controleLayout){controleLayout.setAttribute('role','button');controleLayout.tabIndex=0;controleLayout.addEventListener('click',()=>{listaAreas.classList.toggle('layout-lista');window.localStorage.setItem('trainhub-areas-layout',listaAreas.classList.contains('layout-lista')?'lista':'grade');atualizarLayoutAreas();});controleLayout.addEventListener('keydown',evento=>{if(evento.key==='Enter'||evento.key===' '){evento.preventDefault();controleLayout.click();}});}document.getElementById('busca-cursos').addEventListener('input',filtrarCursos);document.getElementById('status-cursos').addEventListener('change',filtrarCursos);document.getElementById('busca-areas').addEventListener('input',filtrarAreas);filtroAreas.addEventListener('change',()=>{window.localStorage.setItem('trainhub-areas-filtro',filtroAreas.value);filtrarAreas();});atualizarLayoutAreas();filtrarCursos();filtrarAreas();
</script>
</body>
</html>
