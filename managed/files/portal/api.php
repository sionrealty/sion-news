<?php require __DIR__.'/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
function result(array $v,int $code=200): never {http_response_code($code);echo json_encode($v,JSON_THROW_ON_ERROR|JSON_HEX_TAG|JSON_HEX_AMP);exit;}
try{
$action=$_GET['a']??'';$u=need_user();$write=in_array($action,['save','invite','block','logout'],true);if($write){if($_SERVER['REQUEST_METHOD']!=='POST')result(['error'=>'Método inválido.'],405);csrf();}$raw=file_get_contents('php://input');if(strlen($raw)>30*1024*1024)result(['error'=>'Arquivo grande demais.'],413);$input=$raw?json_decode($raw,true,512,JSON_THROW_ON_ERROR):[];
if($action==='logout'){session_destroy();result(['ok'=>true]);}
if($action==='users'){
 if($u['role']!=='admin')result(['error'=>'Somente administrador.'],403);
 $rows=query('SELECT id,name,email,role,owner_ids,blocked FROM users ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);foreach($rows as &$r){$r['ownerIds']=json_decode($r['owner_ids'],true);unset($r['owner_ids']);}result(['users'=>$rows]);
}
if($action==='invite'){
 if($u['role']!=='admin')result(['error'=>'Somente administrador.'],403);
 $email=trim($input['email']??'');$name=trim($input['name']??'');$role=$input['role']??'';$ids=$input['ownerIds']??[];
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($name)<2||!in_array($role,['editor','investor','accountant'],true)||!is_array($ids))result(['error'=>'Confira nome, e-mail e perfil.'],422);
 $valid=array_column(state_record()['data']['owners'],'id');foreach($ids as $id)if(!in_array($id,$valid,true))result(['error'=>'Investidor inválido.'],422);
 if(in_array($role,['investor','accountant'],true)&&!$ids)result(['error'=>'Selecione um investidor.'],422);
 $existing=query('SELECT id,blocked FROM users WHERE email=?',[$email])->fetch(PDO::FETCH_ASSOC);if($existing)result(['error'=>'E-mail já cadastrado. Use recuperação de senha ou desbloqueio.'],409);
 $id=bin2hex(random_bytes(12));query('INSERT INTO users(id,name,email,role,owner_ids,hash) VALUES(?,?,?,?,?,?)',[$id,$name,$email,$role,json_encode($ids),password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT)]);$sent=send_access(['id'=>$id,'email'=>$email]);query('INSERT INTO audit VALUES(?,?,?)',[time(),$u['id'],'invite:'.$id]);result(['ok'=>true,'emailSent'=>$sent]);
}
if($action==='block'){
 if($u['role']!=='admin'||($input['id']??'')===$u['id'])result(['error'=>'Ação não permitida.'],403);
 $target=query('SELECT role FROM users WHERE id=?',[$input['id']??''])->fetchColumn();if(!$target||$target==='admin')result(['error'=>'Conta protegida ou inexistente.'],422);
 query('UPDATE users SET blocked=?,session_version=session_version+1 WHERE id=?',[!empty($input['blocked'])?1:0,$input['id']]);query('DELETE FROM tokens WHERE user_id=?',[$input['id']]);query('INSERT INTO audit VALUES(?,?,?)',[time(),$u['id'],'access:'.$input['id']]);result(['ok'=>true]);
}
if($action==='save'){
 need_user(true);$db=sql();$db->beginTransaction();$old=state_record();if(($input['revision']??null)!==$old['revision']){$db->rollBack();result(['error'=>'Outro usuário atualizou os dados. Faça backup das suas alterações e reabra o portal antes de continuar.'],409);}
 $d=$input['data']??[];foreach(['owners','properties','reports'] as $key)if(!isset($d[$key])||!is_array($d[$key]))throw new RuntimeException('Dados incompletos.');
 foreach($old['data']['reports'] as $r)if(!empty($r['historical'])){$found=false;foreach($d['reports'] as $n)if($n['id']===$r['id']){$found=$n==$r;break;}if(!$found)throw new RuntimeException('Histórico original não pode ser alterado ou removido.');}
 foreach(($d['historicalOccupancy']??[]) as $rid=>$rows){$match=null;foreach($old['data']['reports'] as $r)if($r['id']===$rid&&!empty($r['historical']))$match=$r;if(!$match||!is_array($rows))throw new RuntimeException('Relatório histórico inválido.');foreach($rows as $pid=>$status){if(!in_array((string)$pid,array_column($match['rows'],'propertyId'),true)||!in_array($status,['unknown','occupied','vacant'],true))throw new RuntimeException('Situação histórica inválida.');}}
 $allowed=['version','logo','owners','properties','reports','hoa','annual','trash','historicalOccupancy'];$next=$old['data'];foreach($allowed as $key)if(array_key_exists($key,$d))$next[$key]=$d[$key];$next['users']=[];
 if($u['role']!=='admin'){foreach($next['owners'] as &$o){$oldOwner=null;foreach($old['data']['owners'] as $oo)if($oo['id']===$o['id'])$oldOwner=$oo;$o['blocked']=$oldOwner['blocked']??false;}unset($o);}
 query('UPDATE state SET revision=revision+1,data=? WHERE id=1',[sealed(json_encode($next,JSON_THROW_ON_ERROR))]);query('INSERT INTO audit VALUES(?,?,?)',[time(),$u['id'],'save']);$db->commit();result(['ok'=>true,'revision'=>$old['revision']+1]);
}
result(['error'=>'Ação não encontrada.'],404);
}catch(Throwable $e){if(sql()->inTransaction())sql()->rollBack();result(['error'=>$e instanceof RuntimeException?$e->getMessage():'Não foi possível concluir.'],422);}
