<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/sion-portal-private/lib.php';
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: public, max-age=60');header('X-Content-Type-Options: nosniff');
try{$items=[];foreach(state_record()['data']['properties'] as $p){if(!empty($p['archived']))continue;$l=$p['listing']??[];if(($l['availability']??'draft')!=='vacant'||empty($l['title'])||empty($l['address'])||empty($l['description'])||($l['price']??0)<=0)continue;$items[]=array_intersect_key($l,array_flip(['title','address','description','price','beds','baths','area','photos','availableDate']));}echo json_encode(['rentals'=>$items],JSON_HEX_TAG|JSON_HEX_AMP|JSON_THROW_ON_ERROR);}catch(Throwable $e){http_response_code(503);echo '{"rentals":[],"error":"Lista temporariamente indisponível."}';}
