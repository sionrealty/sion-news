<?php
require_once __DIR__.'/news-common.php';
news_feed_sync(news_db());
$slug=is_string($_GET['article']??null)?$_GET['article']:'';
$editions=['pt'=>'onda-azul-2026-pt','en'=>'onda-azul-2026-en','es'=>'onda-azul-2026-es'];
$flags=['en'=>['English','<svg class="sion-flag" viewBox="0 0 30 20" aria-hidden="true" focusable="false"><rect width="30" height="20" fill="#fff"/><rect y="0.000" width="30" height="1.538" fill="#b22234"/><rect y="3.077" width="30" height="1.538" fill="#b22234"/><rect y="6.154" width="30" height="1.538" fill="#b22234"/><rect y="9.231" width="30" height="1.538" fill="#b22234"/><rect y="12.308" width="30" height="1.538" fill="#b22234"/><rect y="15.385" width="30" height="1.538" fill="#b22234"/><rect y="18.462" width="30" height="1.538" fill="#b22234"/><rect width="12" height="10.77" fill="#3c3b6e"/><circle cx="1" cy="1" r=".45" fill="white"/><circle cx="3" cy="1" r=".45" fill="white"/><circle cx="5" cy="1" r=".45" fill="white"/><circle cx="7" cy="1" r=".45" fill="white"/><circle cx="9" cy="1" r=".45" fill="white"/><circle cx="11" cy="1" r=".45" fill="white"/><circle cx="1" cy="3" r=".45" fill="white"/><circle cx="3" cy="3" r=".45" fill="white"/><circle cx="5" cy="3" r=".45" fill="white"/><circle cx="7" cy="3" r=".45" fill="white"/><circle cx="9" cy="3" r=".45" fill="white"/><circle cx="11" cy="3" r=".45" fill="white"/><circle cx="1" cy="5" r=".45" fill="white"/><circle cx="3" cy="5" r=".45" fill="white"/><circle cx="5" cy="5" r=".45" fill="white"/><circle cx="7" cy="5" r=".45" fill="white"/><circle cx="9" cy="5" r=".45" fill="white"/><circle cx="11" cy="5" r=".45" fill="white"/><circle cx="1" cy="7" r=".45" fill="white"/><circle cx="3" cy="7" r=".45" fill="white"/><circle cx="5" cy="7" r=".45" fill="white"/><circle cx="7" cy="7" r=".45" fill="white"/><circle cx="9" cy="7" r=".45" fill="white"/><circle cx="11" cy="7" r=".45" fill="white"/><circle cx="1" cy="9" r=".45" fill="white"/><circle cx="3" cy="9" r=".45" fill="white"/><circle cx="5" cy="9" r=".45" fill="white"/><circle cx="7" cy="9" r=".45" fill="white"/><circle cx="9" cy="9" r=".45" fill="white"/><circle cx="11" cy="9" r=".45" fill="white"/></svg>'],'pt'=>['Português','<svg class="sion-flag" viewBox="0 0 30 20" aria-hidden="true" focusable="false"><rect width="30" height="20" fill="#009b3a"/><path d="M15 2 28 10 15 18 2 10Z" fill="#ffdf00"/><circle cx="15" cy="10" r="5" fill="#002776"/><path d="M10.3 8.4Q15 7.6 19.6 11.8" fill="none" stroke="white" stroke-width="1"/></svg>'],'es'=>['Español','<svg class="sion-flag" viewBox="0 0 30 20" aria-hidden="true" focusable="false"><rect width="30" height="20" fill="#aa151b"/><rect y="5" width="30" height="10" fill="#f1bf00"/><path d="M8 8h4v5q-2 2-4 0Z" fill="#aa151b" stroke="#fff" stroke-width=".5"/><path d="M8 7h4M9 6h2" stroke="#aa151b"/></svg>']];
$cleanNews=preg_match('~^/news(?:/|\\?|$)~',$_SERVER['REQUEST_URI']??'')===1;
if($slug==='blue-wave-2026')$slug=$editions['en'];
$articleLang=array_search($slug,$editions,true);
if($cleanNews){
 ob_start(static function(string $html)use($editions):string{
  return preg_replace_callback('~(href|action)="([^"]+)"~',static function(array $match)use($editions):string{
   $url=html_entity_decode($match[2],ENT_QUOTES,'UTF-8');
   if(preg_match('~^(?:https://sionrealtypm\\.com)?(?:/sion-imoveis/)?news\\.php(?:\\?(.*))?$~',$url,$parts)){
    parse_str($parts[1]??'',$query);
    $slug=is_string($query['article']??null)?$query['article']:'';
    unset($query['article']);
    if(in_array($slug,$editions,true)){
     $query['lang']=$query['lang']??array_search($slug,$editions,true);
     $slug='blue-wave-2026';
    }
    if(($query['lang']??null)==='en')unset($query['lang']);
    $url='/news'.($slug!==''?'/'.rawurlencode($slug):'').($query?'?'.http_build_query($query,'','&',PHP_QUERY_RFC3986):'');
    if($match[1]==='action')$url='/news';
    return $match[1].'="'.h($url).'"';
   }
   if($url==='index.php')return 'href="/imoveis"';
   if(preg_match('~^[a-z0-9_-]+\\.(?:php|css)$~i',$url))return $match[1].'="/sion-imoveis/'.h($url).'"';
   return $match[0];
  },$html)??$html;
 });
}

$lang=is_string($_GET['lang']??null)&&isset($flags[$_GET['lang']])?$_GET['lang']:($articleLang?:'en');
if($articleLang)$slug=$editions[$lang];
$a=null;
if($slug!==''){$q=news_db()->prepare("SELECT * FROM news_articles WHERE slug=? AND status='published' AND published<=?");$q->execute([$slug,date('Y-m-d')]);$a=$q->fetch();if(!$a){http_response_code(404);public_start('Article not found','Sion news','news.php');echo '<h1>Article not found</h1><a href="news.php">Back to news</a>';public_end();exit;}}
$categories=['Orange County','Market','Florida','Laws & proposals'];
$category=is_string($_GET['category']??null)&&in_array($_GET['category'],$categories,true)?$_GET['category']:'';
$page=1;$pages=1;$total=0;$perPage=8;$list=[];$listingPath='news.php';
if(!$a){
 $p=news_db();$today=date('Y-m-d');
 $where="status='published' AND published<=? AND slug NOT IN (?,?)";$params=array_merge([$today],array_values(array_diff_key($editions,[$lang=>true])));
 if($category!==''){$where.=' AND category=?';$params[]=$category;}
 $count=$p->prepare('SELECT COUNT(*) FROM news_articles WHERE '.$where);$count->execute($params);$total=(int)$count->fetchColumn();
 $pages=max(1,(int)ceil($total/$perPage));
 $requested=is_string($_GET['page']??null)?filter_var($_GET['page'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]):1;
 $page=min($pages,$requested===false?1:(int)$requested);$offset=($page-1)*$perPage;
 $query=$p->prepare('SELECT * FROM news_articles WHERE '.$where.' ORDER BY published DESC,id DESC LIMIT ? OFFSET ?');
 foreach($params as $i=>$value)$query->bindValue($i+1,$value,PDO::PARAM_STR);
 $query->bindValue(count($params)+1,$perPage,PDO::PARAM_INT);$query->bindValue(count($params)+2,$offset,PDO::PARAM_INT);$query->execute();$list=$query->fetchAll();
 $urlFor=function(int $number)use($category,$lang):string{$args=['lang'=>$lang];if($category!=='')$args['category']=$category;if($number>1)$args['page']=$number;return 'news.php'.($args?'?'.http_build_query($args,'','&',PHP_QUERY_RFC3986):'');};
 $listingPath=$urlFor($page);
}
ob_start();
public_start($a?$a['title']:'News & local insights',$a?$a['summary']:'Housing news, market reports and owner guides for Orlando, Orange County and Florida.',($a?'news.php?article='.rawurlencode($a['slug']):$listingPath));
$newsHeader=ob_get_clean();
ob_start();
?>
<span class="sion-languages" role="group" aria-label="News language"><?php foreach($flags as $code=>[$label,$flag]):
 $target=[];
 $formAction=$cleanNews?'/news':'news.php';
 if($a&&$cleanNews)$formAction.='/'.rawurlencode($articleLang?'blue-wave-2026':$slug);
 elseif($a)$target['article']=$articleLang?$editions[$code]:$slug;
 elseif($category!=='')$target['category']=$category;
?><form action="<?=h($formAction)?>" method="get"><?php foreach($target as $name=>$value):?><input type="hidden" name="<?=h($name)?>" value="<?=h($value)?>"><?php endforeach;?><button type="submit" name="lang" value="<?=h($code)?>" aria-label="<?=h($label)?>" title="<?=h($label)?>" aria-pressed="<?=$code===$lang?'true':'false'?>"><?=$flag?></button></form><?php endforeach;?></span>
<?php
$newsControls=ob_get_clean();
$newsHeader=preg_replace_callback('~(<a\\b[^>]*href="(?:/sion-imoveis/)?contact\\.php"[^>]*>[^<]*</a>)(?=\\s*</nav>)~i',static function(array $match)use($newsControls):string{
 return '<span class="news-contact-languages">'.$match[1].$newsControls.'</span>';
},$newsHeader,1,$newsControlsPlaced)??$newsHeader;
echo $newsHeader;
if(!$newsControlsPlaced)echo $newsControls;
?>
<style>
.site-header nav .news-contact-languages{display:inline-flex;align-items:center;gap:10px;white-space:nowrap;vertical-align:middle}
.site-header nav .sion-languages{display:inline-flex;align-items:center;gap:5px;flex-wrap:nowrap}
.site-header nav .sion-languages form{display:inline-flex;margin:0;padding:0}
.site-header nav .sion-languages button{display:inline-flex;align-items:center;justify-content:center;box-sizing:border-box;min-width:36px;min-height:36px;margin:0;padding:6px;background:transparent;color:inherit;border:1px solid #b99b5866;border-radius:5px;cursor:pointer}
.site-header nav .sion-languages button[aria-pressed="true"]{background:#f2e8d1;border-color:#b99b58}
.site-header nav .sion-languages button:focus-visible{outline:2px solid #b99b58;outline-offset:2px}
.site-header nav .sion-languages button svg.sion-flag{display:block;width:22px;height:15px;min-width:22px;max-width:none;visibility:visible;opacity:1;border-radius:2px}
@media(max-width:700px){.site-header nav .news-contact-languages{gap:6px}.site-header nav .sion-languages{gap:3px}.site-header nav .sion-languages button{min-width:32px;min-height:40px;padding:3px}}
</style>
<?php if($a):?>
<?php if(!$articleLang&&$lang!=='en'):?><p lang="<?=h($lang)?>"><?=h($lang==='pt'?'Esta matéria está disponível em inglês.':'Este artículo está disponible en inglés.')?></p><?php endif;?>
<article class="article" lang="<?=h($articleLang?$lang:'en')?>"><a href="news.php?lang=<?=h($lang)?>"><?=h(['pt'=>'← Todas as notícias','en'=>'← All news','es'=>'← Todas las noticias'][$lang])?></a><div class="meta"><?=h($a['category'])?> · <?=h($a['kind'])?></div><h1><?=h($a['title'])?></h1><p class="lead"><?=h($a['summary'])?></p><p class="date">Published <?=h(date('F j, Y',strtotime($a['published'])))?> · Sion editorial</p>
<?php paragraphs($a['body']);$chart=json_decode($a['chart'],true);if(is_array($chart)&&count($chart)>0):?>
<figure class="chart"><figcaption>Orlando-area reported home sales — monthly comparison</figcaption><table><thead><tr><th>Period</th><th>Sales</th></tr></thead><tbody><?php foreach($chart as $point):if(!is_array($point)||count($point)!==2||!is_numeric($point[1]))continue;?><tr><th><?=h($point[0])?></th><td><?=h(number_format((float)$point[1]))?> <meter min="0" max="3000" value="<?=h($point[1])?>" aria-label="<?=h($point[0].': '.$point[1].' sales')?>"></meter></td></tr><?php endforeach?></tbody></table></figure>
<?php endif;if($a['opinion']!==''):?><aside class="opinion"><h2>Patrick’s perspective</h2><?php paragraphs($a['opinion']);?></aside><?php endif;?>
<aside class="source"><h2>Source &amp; reporting period</h2><p><?=h($a['source_date'])?></p><?php if($a['source_url']!=='' && safe_source($a['source_url'])):?><a href="<?=h($a['source_url'])?>" target="_blank" rel="noopener noreferrer"><?=h($a['source_name'])?> ↗</a><?php else:echo h($a['source_name']);endif;?></aside><a class="button" href="contact.php">Talk to Sion</a></article>
<?php else:?>

<p class="eyebrow">THE SION JOURNAL</p><h1><?=h(['pt'=>'Notícias e informações locais','en'=>'News & local insights','es'=>'Noticias e información local'][$lang])?></h1><p class="lead"><?=h(['pt'=>'Notícias sobre moradia em Orange County, na região de Orlando e na Flórida.','en'=>'A closer look at housing in Orange County, the Orlando area and Florida.','es'=>'Noticias sobre vivienda en Orange County, el área de Orlando y Florida.'][$lang])?></p>
<style>
.news-pagination{display:flex;justify-content:center;align-items:center;flex-wrap:wrap;gap:8px;margin:26px 0 10px}
.news-pagination a,.news-pagination span{display:inline-flex;align-items:center;justify-content:center;min-width:42px;min-height:44px;padding:8px 12px;border:1px solid #c9b484;border-radius:4px;color:inherit;text-decoration:none}
.news-pagination a:hover,.news-pagination a:focus-visible{background:#eee5cf;color:#201c14}
.news-pagination [aria-current="page"]{background:#c9b484;color:#201c14;font-weight:600}
.news-pagination .disabled{opacity:.5}.news-pagination .ellipsis{border:0;min-width:18px;padding:0}
.news-results-count{font-size:13px;color:#777;margin:16px 0}
.filters [aria-current="true"]{text-decoration:underline;text-underline-offset:5px}
</style>
<nav class="filters" aria-label="News categories"><a href="news.php?lang=<?=h($lang)?>"<?=$category===''?' aria-current="true"':''?>>All</a><?php foreach($categories as $cat):?><a href="news.php?category=<?=rawurlencode($cat)?>&amp;lang=<?=h($lang)?>"<?=$category===$cat?' aria-current="true"':''?>><?=h($cat)?></a><?php endforeach;?></nav>
<?php if($total>0):?><p class="news-results-count">Showing <?=h((string)($offset+1))?>–<?=h((string)min($offset+$perPage,$total))?> of <?=h((string)$total)?> articles</p><?php endif;?>
<div class="news-grid"><?php foreach($list as $row)echo preg_replace('/(href="news\.php\?article=[^"]+)"/', '$1&amp;lang='.h($lang).'"',news_card($row));if(!$list)echo '<p>No articles in this category yet.</p>';?></div>
<?php if($pages>1):?>
<nav class="news-pagination" aria-label="News pages">
<?php if($page>1):?><a href="<?=h($urlFor($page-1))?>" rel="prev">← Previous</a><?php else:?><span class="disabled" aria-disabled="true">← Previous</span><?php endif;?>
<?php $numbers=array_unique(array_merge([1,$pages],range(max(1,$page-2),min($pages,$page+2))));sort($numbers);$last=0;foreach($numbers as $number):if($last&&$number>$last+1):?><span class="ellipsis" aria-hidden="true">…</span><?php endif;if($number===$page):?><span aria-current="page" aria-label="Page <?=h((string)$number)?>"><?=h((string)$number)?></span><?php else:?><a href="<?=h($urlFor($number))?>" aria-label="Page <?=h((string)$number)?>"><?=h((string)$number)?></a><?php endif;$last=$number;endforeach;?>
<?php if($page<$pages):?><a href="<?=h($urlFor($page+1))?>" rel="next">Next →</a><?php else:?><span class="disabled" aria-disabled="true">Next →</span><?php endif;?>
</nav>
<?php endif;?>
<?php endif;public_end();
