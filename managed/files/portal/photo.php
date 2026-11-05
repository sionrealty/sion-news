<?php
// Endpoint para exibir somente fotos de imóveis anunciados ou a administradores autenticados.
require __DIR__.'/bootstrap.php';
$name=$_GET['f']??'';
if(!is_string($name)||!preg_match('/^[a-f0-9]{64}\.(?:jpg|png|webp)$/D',$name)){http_response_code(404);exit;}
$path=sion_photo_dir().'/'.$name;
if(!is_file($path)){http_response_code(404);exit;}
$wanted=sion_photo_url($name);$allowed=false;
try{
 $data=state_record()['data'];
 foreach($data['properties'] as $property){
  $listing=$property['listing']??[];
  if(!empty($property['archived'])||($listing['availability']??'draft')!=='vacant'||empty($listing['title'])||empty(trim((string)($listing['address']??'')))||!is_numeric($listing['price']??null)||$listing['price']<=0)continue;
  if(in_array($wanted,$listing['photos']??[],true)){$allowed=true;break;}
 }
 if(!$allowed){$user=current_user();$allowed=$user&&in_array($user['role'],['admin','editor'],true);}
}catch(Throwable $e){$allowed=false;}
if(!$allowed){http_response_code(404);exit;}
header('Content-Type: '.(['jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'][pathinfo($name,PATHINFO_EXTENSION)]));
header('X-Content-Type-Options: nosniff');header('Cache-Control: private, max-age=60');
header('Content-Length: '.filesize($path));readfile($path);
