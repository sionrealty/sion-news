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
$map=['home/index.html'=>'public_html/index.html','private/lib.php'=>'sion-portal-private/lib.php'];
foreach(['api.php','app.php','bootstrap.php','index.php','rentals.php','.htaccess'] as $f)$map['portal/'.$f]='public_html/reports/'.$f;
foreach(['accounts.js','annual-exports.js','annual.js','app.js','core.js','exports.js','jszip.min.js','listings.js','logo.png','pdf-lib.min.js','portal.css','server.js'] as $f)$map['portal/assets/'.$f]='public_html/reports/assets/'.$f;
try{
 $release=json_decode(file_get_contents($repo.'/managed/release.json'),true,512,JSON_THROW_ON_ERROR);
 if(($release['version']??0)!==1||!is_array($release['files']??null))throw new RuntimeException('Invalid release manifest');
 $jobs=[];$seen=[];
 foreach($release['files'] as $f){$key=$f['path']??'';if(!isset($map[$key])||isset($seen[$key]))throw new RuntimeException('Path not allowed: '.$key);$seen[$key]=true;
  $src=$repo.'/managed/files/'.$key;$dst=$home.'/'.$map[$key];
  foreach([$src,$dst,dirname($dst)] as $p)if(is_link($p))throw new RuntimeException('Symlink refused');
  if(!is_file($src)||!is_file($dst))throw new RuntimeException('Missing existing code file: '.$key);
  if(!preg_match('/^[a-f0-9]{64}$/D',$f['sha256']??'')||!hash_equals($f['sha256'],hash_file('sha256',$src)))throw new RuntimeException('Hash mismatch: '.$key);
  if(str_ends_with($key,'.php')){exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($src).' 2>&1',$output,$code);if($code!==0)throw new RuntimeException('PHP syntax error: '.$key);}
  if(!hash_equals(hash_file('sha256',$src),hash_file('sha256',$dst)))$jobs[]=[$src,$dst,$map[$key],fileperms($dst)&0777];
 }
 $back=$work.'/backups/'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));$applied=[];$staged=[];
 if($jobs){mkdir($back,0700,true);
  // Stage and back up every changed file before touching any destination.
  foreach($jobs as [$src,$dst,$relative,$mode]){$backup=$back.'/'.$relative;if(!is_dir(dirname($backup)))mkdir(dirname($backup),0700,true);if(!copy($dst,$backup))throw new RuntimeException('Backup failed');chmod($backup,0600);$tmp=tempnam(dirname($dst),'.sion-code-');if(!$tmp||!copy($src,$tmp))throw new RuntimeException('Staging failed');chmod($tmp,$mode);$staged[]=[$tmp,$dst,$backup,$mode];}
  try{foreach($staged as [$tmp,$dst,$backup,$mode]){if(!rename($tmp,$dst))throw new RuntimeException('Activation failed');$applied[]=[$dst,$backup,$mode];}}
  catch(Throwable $e){foreach(array_reverse($applied) as [$dst,$backup,$mode]){copy($backup,$dst);chmod($dst,$mode);}throw $e;}
 }
 $status=['checked_at'=>gmdate('c'),'release'=>$release['release']??'configuration','files_checked'=>count($release['files']),'files_updated'=>count($jobs),'state'=>'ok'];
 file_put_contents($work.'/status.json',json_encode($status,JSON_PRETTY_PRINT),LOCK_EX);
 echo 'Sion code automation OK: '.count($jobs).' code files updated; database, credentials and reports untouched.'.PHP_EOL;
}catch(Throwable $e){fwrite(STDERR,'Managed code update failed: '.$e->getMessage().PHP_EOL);exit(1);}
finally{foreach($staged??[] as [$tmp])if(is_file($tmp))unlink($tmp);}
