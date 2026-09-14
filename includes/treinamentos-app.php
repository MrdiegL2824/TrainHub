<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
exigirAdmin();

$erro = '';
$sucesso = '';
$editarId = max(0, (int) ($_GET['editar'] ?? $_POST['treinamento_id'] ?? 0));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = (string) ($_POST['acao'] ?? 'salvar');
    if (!validarCsrf($_POST['csrf_token'] ?? null)) {
        $erro = 'A solicitação expirou. Atualize a página.';
    } else {
        try {
            if ($acao === 'excluir') {
                $stmt = $pdo->prepare('SELECT titulo FROM treinamentos WHERE id = ?'); $stmt->execute([$editarId]); $titulo = $stmt->fetchColumn();
                if ($titulo === false) { $erro = 'Treinamento não encontrado.'; }
                else { $stmt = $pdo->prepare('DELETE FROM treinamentos WHERE id = ?'); $stmt->execute([$editarId]); registrarAtividade($pdo, (int)$_SESSION['usuario_id'], 'removeu o treinamento', (string)$titulo); $sucesso = 'Treinamento removido.'; $editarId = 0; }
            } else {
                $areaId = (int) ($_POST['area_id'] ?? 0); $titulo = trim((string)($_POST['titulo'] ?? '')); $descricao = trim((string)($_POST['descricao'] ?? '')); $carga = (float)($_POST['carga_horaria'] ?? 0); $status = (string)($_POST['status'] ?? 'rascunho');
                if ($areaId < 1 || mb_strlen($titulo) < 3 || $carga < 0 || !in_array($status, ['rascunho','publicado','arquivado'], true)) { $erro = 'Preencha corretamente área, título, carga horária e status.'; }
                else {
                    $stmt = $pdo->prepare('SELECT id FROM treinamentos WHERE LOWER(titulo) = LOWER(?) AND id <> ? LIMIT 1'); $stmt->execute([$titulo, $editarId]);
                    if ($stmt->fetchColumn() !== false) { $erro = 'Já existe um treinamento com este título.'; }
                    elseif ($editarId > 0) { $stmt = $pdo->prepare('UPDATE treinamentos SET area_id=?, titulo=?, descricao=?, carga_horaria=?, status=?, atualizado_em=CURRENT_TIMESTAMP WHERE id=?'); $stmt->execute([$areaId,$titulo,$descricao,$carga,$status,$editarId]); registrarAtividade($pdo,(int)$_SESSION['usuario_id'],'editou o treinamento',$titulo); $sucesso='Treinamento atualizado.'; }
                    else { $stmt = $pdo->prepare('INSERT INTO treinamentos (area_id,criado_por,titulo,descricao,carga_horaria,status) VALUES (?,?,?,?,?,?)'); $stmt->execute([$areaId,(int)$_SESSION['usuario_id'],$titulo,$descricao,$carga,$status]); registrarAtividade($pdo,(int)$_SESSION['usuario_id'],'criou o treinamento',$titulo); $sucesso='Treinamento cadastrado.'; }
                }
            }
        } catch (Throwable $e) { registrarErro($pdo,'gerenciamento de treinamentos',$e); $erro='Não foi possível concluir a operação.'; }
    }
}
$areas = $pdo->query('SELECT id,nome FROM areas ORDER BY nome')->fetchAll();
$treinamentos = $pdo->query('SELECT t.*,a.nome AS area_nome,(SELECT COUNT(*) FROM aulas au WHERE au.treinamento_id=t.id) AS total_aulas FROM treinamentos t JOIN areas a ON a.id=t.area_id ORDER BY t.criado_em DESC')->fetchAll();
$edicao = ['id'=>0,'area_id'=>'','titulo'=>'','descricao'=>'','carga_horaria'=>'1','status'=>'rascunho'];
if ($editarId > 0) { $stmt=$pdo->prepare('SELECT * FROM treinamentos WHERE id=?'); $stmt->execute([$editarId]); $edicao=$stmt->fetch() ?: $edicao; }
?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TrainHub - Treinamentos</title><link rel="stylesheet" href="css/app.css?v=1.0"></head><body><header class="th-topbar"><a class="th-logo" href="dashboard.php"><img src="assets/logo.png" alt="TrainHub"></a><a class="th-avatar" href="perfil.php">●</a></header><main class="th-shell"><a class="th-back" href="dashboard.php">←</a><div class="th-heading"><div><h1>Treinamentos</h1><p>Cadastre, publique e organize os treinamentos da plataforma.</p></div></div><?php if($erro):?><p class="th-alert th-alert-error"><?php echo esc($erro);?></p><?php endif;?><?php if($sucesso):?><p class="th-alert th-alert-success"><?php echo esc($sucesso);?></p><?php endif;?><div class="th-grid"><section class="th-panel"><h2>Treinamentos cadastrados</h2><div class="th-list"><?php if(!$treinamentos):?><p class="th-empty">Nenhum treinamento cadastrado.</p><?php endif;?><?php foreach($treinamentos as $item):?><article class="th-card"><div class="th-card-row"><div><span class="th-meta"><?php echo esc($item['area_nome']);?></span><h3><?php echo esc($item['titulo']);?></h3></div><span class="th-badge <?php echo $item['status']==='publicado'?'th-badge-success':($item['status']==='arquivado'?'th-badge-danger':'th-badge-warning');?>"><?php echo esc($item['status']);?></span></div><p><?php echo esc($item['descricao'] ?: 'Sem descrição.');?></p><div class="th-card-row"><span class="th-meta"><?php echo esc($item['carga_horaria']);?>h · <?php echo (int)$item['total_aulas'];?> aulas</span><div class="th-actions"><a class="th-button th-button-secondary" href="treinamento-detalhe.php?id=<?php echo (int)$item['id'];?>">Visualizar</a><a class="th-button" href="treinamentos.php?editar=<?php echo (int)$item['id'];?>">Editar</a></div></div></article><?php endforeach;?></div></section><aside class="th-panel"><h2><?php echo $editarId?'Editar treinamento':'Novo treinamento';?></h2><form class="th-form" method="post"><?php echo csrfInput();?><input type="hidden" name="acao" value="salvar"><input type="hidden" name="treinamento_id" value="<?php echo (int)$editarId;?>"><label class="th-field">Área<select name="area_id" required><option value="">Selecione</option><?php foreach($areas as $area):?><option value="<?php echo (int)$area['id'];?>" <?php echo (int)$edicao['area_id']===(int)$area['id']?'selected':'';?>><?php echo esc($area['nome']);?></option><?php endforeach;?></select></label><label class="th-field">Título<input name="titulo" maxlength="200" value="<?php echo esc($edicao['titulo']);?>" required></label><label class="th-field">Descrição<textarea name="descricao"><?php echo esc($edicao['descricao']);?></textarea></label><div class="th-form-row"><label class="th-field">Carga horária<input name="carga_horaria" type="number" min="0" step="0.5" value="<?php echo esc($edicao['carga_horaria']);?>" required></label><label class="th-field">Status<select name="status"><option value="rascunho" <?php echo $edicao['status']==='rascunho'?'selected':'';?>>Rascunho</option><option value="publicado" <?php echo $edicao['status']==='publicado'?'selected':'';?>>Publicado</option><option value="arquivado" <?php echo $edicao['status']==='arquivado'?'selected':'';?>>Arquivado</option></select></label></div><button class="th-button"><?php echo $editarId?'Salvar alterações':'Cadastrar treinamento';?></button></form><?php if($editarId):?><form method="post" style="margin-top:10px" onsubmit="return confirm('Remover o treinamento e todo o conteúdo relacionado?')"><?php echo csrfInput();?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="treinamento_id" value="<?php echo $editarId;?>"><button class="th-button th-button-danger">Remover treinamento</button></form><a class="th-button th-button-secondary" style="margin-top:10px" href="treinamentos.php">Cancelar edição</a><?php endif;?></aside></div></main></body></html>
