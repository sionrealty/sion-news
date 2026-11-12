<?php
declare(strict_types=1);
// This installer deploys CODE ONLY. Account data and installation scripts are excluded.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
umask(0077);
$home=dirname(__DIR__,3);
$repo=dirname(__DIR__);
// Test roots can be supplied only to this CLI entry point.
if(getenv('SION_DEPLOY_TEST_HOME'))$home=getenv('SION_DEPLOY_TEST_HOME');
$work=$home.'/sion-code-deploy';
if(!is_dir($work)&&!mkdir($work,0700,true))throw new RuntimeException('Cannot create deployment directory');
$lock=fopen($work.'/lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit("Another code deployment is active.\n");
$map=['rentals/rentals-public.css'=>'public_html/sion-imoveis/rentals-public.css','rentals/rentals-public.js'=>'public_html/sion-imoveis/rentals-public.js','rentals/index.php'=>'public_html/sion-imoveis/index.php','rentals/route.php'=>'public_html/sion-imoveis/rent-route.php','rentals/route.htaccess'=>'public_html/.htaccess','rentals/widget.js'=>'public_html/sion-imoveis/widget.js','rentals/sitemap.xml'=>'public_html/sitemap.xml','home/index.html'=>'public_html/index.html','home/regioes.html'=>'public_html/regioes.html','private/lib.php'=>'sion-portal-private/lib.php'];
foreach(['api.php','app.php','bootstrap.php','index.php','rentals.php','photo.php','.htaccess'] as $f)$map['portal/'.$f]='public_html/reports/'.$f;
foreach(['accounts.js','annual-exports.js','annual.js','app.js','core.js','exports.js','jszip.min.js','listings.js','logo.png','pdf-lib.min.js','portal.css','server.js'] as $f)$map['portal/assets/'.$f]='public_html/reports/assets/'.$f;
try{
 $release=json_decode(file_get_contents($repo.'/managed/release.json'),true,512,JSON_THROW_ON_ERROR);
 if(($release['version']??0)!==1||!is_array($release['files']??null))throw new RuntimeException('Invalid release manifest');
 $jobs=[];$seen=[];
 foreach($release['files'] as $f){$key=$f['path']??'';if(!isset($map[$key])||isset($seen[$key]))throw new RuntimeException('Path not allowed: '.$key);$seen[$key]=true;
  $src=$repo.'/managed/files/'.$key;$dst=$home.'/'.$map[$key];
  foreach([$src,$dst,dirname($dst)] as $p)if(is_link($p))throw new RuntimeException('Symlink refused');

  if($key==='rentals/route.htaccess'){
   if(!is_file($dst)||!is_file($src)||!hash_equals($f['sha256'],hash_file('sha256',$src)))throw new RuntimeException('Invalid rental routing baseline');
   if(file_exists($home.'/public_html/rent')||is_link($home.'/public_html/rent'))throw new RuntimeException('Public rent path already exists');
   $current=file_get_contents($dst);
   $rules=file_get_contents($src);
   if($current===false||$rules===false)throw new RuntimeException('Cannot read rental routing rules');
   if(str_starts_with($current,$rules))continue;
   if(str_contains($current,'SION RENT ROUTE')||!str_contains($current,'# BEGIN SION PUBLIC ALIASES'))throw new RuntimeException('Unexpected rental routing baseline');
   $f['base_sha256']=hash('sha256',$current);
   $desired=$rules.$current;
   $f['sha256']=hash('sha256',$desired);
   $src=tempnam($work,'.rent-route-');
   if(!$src)throw new RuntimeException('Cannot stage rental routing rules');
   $patchSources[]=$src;
   if(file_put_contents($src,$desired)!==strlen($desired))throw new RuntimeException('Cannot write rental routing rules');
  }

  // Home patches preserve production content and can externalize approved embedded images.
  if(in_array($key,['home/index.html','home/regioes.html'],true)&&isset($f['patch'])){
   $expectedPatch=$key==='home/index.html'?'home/index.patch.json':'home/regioes.patch.json';
   if($f['patch']!==$expectedPatch)throw new RuntimeException('Patch path not allowed');
   $patchPath=$repo.'/managed/files/'.$expectedPatch;
   if(is_link($patchPath)||!is_file($patchPath)||!is_file($dst))throw new RuntimeException('Missing home patch or destination');
   $patch=json_decode(file_get_contents($patchPath),true,512,JSON_THROW_ON_ERROR);
   if(!hash_equals($f['sha256'],$patch['sha256']??''))throw new RuntimeException('Patch output hash mismatch');
   $current=file_get_contents($dst);
   $currentHash=hash('sha256',$current);
   $installed=hash_equals($f['sha256'],$currentHash);
   if(!$installed&&!hash_equals($patch['base_sha256']??'',$currentHash))throw new RuntimeException('Homepage changed since patch preparation');
   if(isset($patch['assets'])){
    $assets=$patch['assets'];
    if($key!=='home/index.html'||!is_array($assets)||count($assets)>12)throw new RuntimeException('Invalid homepage assets');
    $assetDir=$home.'/public_html/sion-imoveis';
    if(is_link($assetDir)||!is_dir($assetDir))throw new RuntimeException('Invalid homepage asset directory');
    $embedded=[];
    if(!$installed){
     $current=preg_replace_callback('~data:image/(png|jpeg);base64,([A-Za-z0-9+/=]+)~',static function(array $match)use($assets,&$embedded):string{
      $data=base64_decode($match[2],true);
      if($data===false)throw new RuntimeException('Invalid embedded homepage image');
      $hash=hash('sha256',$data);
      $ext=$match[1]==='png'?'png':'jpg';
      if(($assets[$hash]??null)!==$ext)throw new RuntimeException('Unlisted embedded homepage image');
      $embedded[$hash]=$data;
      return '/sion-imoveis/home-asset-'.substr($hash,0,24).'.'.$ext;
     },$current);
     if($current===null||count($embedded)!==count($assets))throw new RuntimeException('Incomplete homepage image set');
    }
    foreach($assets as $hash=>$ext){
     if(!is_string($hash)||!preg_match('/^[a-f0-9]{64}$/D',$hash)||!in_array($ext,['png','jpg'],true))throw new RuntimeException('Invalid homepage asset entry');
     $name='home-asset-'.substr($hash,0,24).'.'.$ext;
     $asset=$assetDir.'/'.$name;
     if(is_link($asset))throw new RuntimeException('Homepage asset symlink refused');
     if(is_file($asset)&&hash_equals($hash,hash_file('sha256',$asset)))continue;
     if($installed||file_exists($asset))throw new RuntimeException('Homepage asset is missing or differs');
     $assetSource=tempnam($work,'.home-asset-');
     if(!$assetSource)throw new RuntimeException('Cannot stage homepage asset');
     $patchSources[]=$assetSource;
     if(file_put_contents($assetSource,$embedded[$hash])!==strlen($embedded[$hash]))throw new RuntimeException('Cannot write homepage asset');
     $jobs[]=[$assetSource,$asset,'public_html/sion-imoveis/'.$name,0644];
    }
   }
   if($installed){$src=$dst;}
   else{
    if(!is_array($patch['replacements']??null)||count($patch['replacements'])>10)throw new RuntimeException('Invalid home patch');
    foreach($patch['replacements'] as $op){
     if(!is_string($op['find']??null)||$op['find']===''||!is_string($op['replace']??null)||substr_count($current,$op['find'])!==1)throw new RuntimeException('Home patch requires a unique match');
     $current=str_replace($op['find'],$op['replace'],$current);
    }
    if(!hash_equals($f['sha256'],hash('sha256',$current)))throw new RuntimeException('Home patch result mismatch');
    $src=tempnam($work,'.home-source-');if(!$src)throw new RuntimeException('Cannot stage home source');
    $patchSources[]=$src;if(file_put_contents($src,$current)===false)throw new RuntimeException('Cannot write home source');
   }
  }
  if(!is_file($src)||(!is_file($dst)&&!in_array($key,['rentals/rentals-public.css','rentals/rentals-public.js','rentals/route.php','portal/photo.php'],true)))throw new RuntimeException('Missing existing code file: '.$key);
  if(!preg_match('/^[a-f0-9]{64}$/D',$f['sha256']??'')||!hash_equals($f['sha256'],hash_file('sha256',$src)))throw new RuntimeException('Hash mismatch: '.$key);
  if(str_ends_with($key,'.php')){exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($src).' 2>&1',$output,$code);if($code!==0)throw new RuntimeException('PHP syntax error: '.$key);}
  if(array_key_exists('base_sha256',$f)){
   $base=$f['base_sha256'];$current=is_file($dst)?hash_file('sha256',$dst):null;
   if($current!==$f['sha256']&&$current!==$base)throw new RuntimeException('Production baseline changed: '.$key);
  }
  if(!is_file($dst)||!hash_equals(hash_file('sha256',$src),hash_file('sha256',$dst)))$jobs[]=[$src,$dst,$map[$key],is_file($dst)?(fileperms($dst)&0777):0644];
 }
 $back=$work.'/backups/'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));$applied=[];$staged=[];
 if($jobs){mkdir($back,0700,true);
  // Stage and back up every changed file before touching any destination.
  foreach($jobs as [$src,$dst,$relative,$mode]){$backup=$back.'/'.$relative;if(!is_dir(dirname($backup)))mkdir(dirname($backup),0700,true);if(is_file($dst)){if(!copy($dst,$backup))throw new RuntimeException('Backup failed');chmod($backup,0600);}$tmp=tempnam(dirname($dst),'.sion-code-');if(!$tmp||!copy($src,$tmp))throw new RuntimeException('Staging failed');chmod($tmp,$mode);$staged[]=[$tmp,$dst,$backup,$mode];}
  try{foreach($staged as [$tmp,$dst,$backup,$mode]){if(!rename($tmp,$dst))throw new RuntimeException('Activation failed');$applied[]=[$dst,$backup,$mode];}}
  catch(Throwable $e){foreach(array_reverse($applied) as [$dst,$backup,$mode]){if(is_file($backup)){copy($backup,$dst);chmod($dst,$mode);}else{unlink($dst);}}throw $e;}
 }
 $status=['checked_at'=>gmdate('c'),'release'=>$release['release']??'configuration','files_checked'=>count($release['files']),'files_updated'=>count($jobs),'state'=>'ok'];
 file_put_contents($work.'/status.json',json_encode($status,JSON_PRETTY_PRINT),LOCK_EX);
 echo 'Sion code automation OK: '.count($jobs).' code files updated; database, credentials and reports untouched.'.PHP_EOL;
}catch(Throwable $e){fwrite(STDERR,'Managed code update failed: '.$e->getMessage().PHP_EOL);exit(1);}
finally{foreach($staged??[] as [$tmp])if(is_file($tmp))unlink($tmp);foreach($patchSources??[] as $tmp)if(is_file($tmp))unlink($tmp);}
