<?php
require_once __DIR__.'/news-common.php';
news_feed_sync(news_db());
$slug=(string)($_GET['article']??'');$a=null;
if($slug!==''){$q=news_db()->prepare("SELECT * FROM news_articles WHERE slug=? AND status='published' AND published<=?");$q->execute([$slug,date('Y-m-d')]);$a=$q->fetch();if(!$a){http_response_code(404);public_start('Article not found','Sion news','news.php');echo '<h1>Article not found</h1><a href="news.php">Back to news</a>';public_end();exit;}}
$categories=['Orange County','Market','Florida','Laws & proposals'];
$category=is_string($_GET['category']??null)&&in_array($_GET['category'],$categories,true)?$_GET['category']:'';
$page=1;$pages=1;$total=0;$perPage=8;$list=[];$listingPath='news.php';
if(!$a){
 $p=news_db();$today=date('Y-m-d');
 $where="status='published' AND published<=?";$params=[$today];
 if($category!==''){$where.=' AND category=?';$params[]=$category;}
 $count=$p->prepare('SELECT COUNT(*) FROM news_articles WHERE '.$where);$count->execute($params);$total=(int)$count->fetchColumn();
 $pages=max(1,(int)ceil($total/$perPage));
 $requested=is_string($_GET['page']??null)?filter_var($_GET['page'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]):1;
 $page=min($pages,$requested===false?1:(int)$requested);$offset=($page-1)*$perPage;
 $query=$p->prepare('SELECT * FROM news_articles WHERE '.$where.' ORDER BY published DESC,id DESC LIMIT ? OFFSET ?');
 foreach($params as $i=>$value)$query->bindValue($i+1,$value,PDO::PARAM_STR);
 $query->bindValue(count($params)+1,$perPage,PDO::PARAM_INT);$query->bindValue(count($params)+2,$offset,PDO::PARAM_INT);$query->execute();$list=$query->fetchAll();
 $urlFor=function(int $number)use($category):string{$args=[];if($category!=='')$args['category']=$category;if($number>1)$args['page']=$number;return 'news.php'.($args?'?'.http_build_query($args,'','&',PHP_QUERY_RFC3986):'');};
 $listingPath=$urlFor($page);
}
public_start($a?$a['title']:'News & local insights',$a?$a['summary']:'Housing news, market reports and owner guides for Orlando, Orange County and Florida.',($a?'news.php?article='.rawurlencode($a['slug']):$listingPath));
if($a):?>
<article class="article"><a href="news.php">← All news</a><div class="meta"><?=h($a['category'])?> · <?=h($a['kind'])?></div><h1><?=h($a['title'])?></h1><p class="lead"><?=h($a['summary'])?></p><p class="date">Published <?=h(date('F j, Y',strtotime($a['published'])))?> · Sion editorial</p>
<?php paragraphs($a['body']);$chart=json_decode($a['chart'],true);if(is_array($chart)&&count($chart)>0):?>
<figure class="chart"><figcaption>Orlando-area reported home sales — monthly comparison</figcaption><table><thead><tr><th>Period</th><th>Sales</th></tr></thead><tbody><?php foreach($chart as $point):if(!is_array($point)||count($point)!==2||!is_numeric($point[1]))continue;?><tr><th><?=h($point[0])?></th><td><?=h(number_format((float)$point[1]))?> <meter min="0" max="3000" value="<?=h($point[1])?>" aria-label="<?=h($point[0].': '.$point[1].' sales')?>"></meter></td></tr><?php endforeach?></tbody></table></figure>
<?php endif;if($a['opinion']!==''):?><aside class="opinion"><h2>Patrick’s perspective</h2><?php paragraphs($a['opinion']);?></aside><?php endif;?>
<aside class="source"><h2>Source &amp; reporting period</h2><p><?=h($a['source_date'])?></p><?php if($a['source_url']!=='' && safe_source($a['source_url'])):?><a href="<?=h($a['source_url'])?>" target="_blank" rel="noopener noreferrer"><?=h($a['source_name'])?> ↗</a><?php else:echo h($a['source_name']);endif;?></aside><a class="button" href="contact.php">Talk to Sion</a></article>
<?php else:?>

<p class="eyebrow">THE SION JOURNAL</p><h1>News &amp; local insights</h1><p class="lead">A closer look at housing in Orange County, the Orlando area and Florida.</p>
<style>
.news-pagination{display:flex;justify-content:center;align-items:center;flex-wrap:wrap;gap:8px;margin:26px 0 10px}
.news-pagination a,.news-pagination span{display:inline-flex;align-items:center;justify-content:center;min-width:42px;min-height:44px;padding:8px 12px;border:1px solid #c9b484;border-radius:4px;color:inherit;text-decoration:none}
.news-pagination a:hover,.news-pagination a:focus-visible{background:#eee5cf;color:#201c14}
.news-pagination [aria-current="page"]{background:#c9b484;color:#201c14;font-weight:600}
.news-pagination .disabled{opacity:.5}.news-pagination .ellipsis{border:0;min-width:18px;padding:0}
.news-results-count{font-size:13px;color:#777;margin:16px 0}
.filters [aria-current="true"]{text-decoration:underline;text-underline-offset:5px}
</style>
<nav class="filters" aria-label="News categories"><a href="news.php"<?=$category===''?' aria-current="true"':''?>>All</a><?php foreach($categories as $cat):?><a href="news.php?category=<?=rawurlencode($cat)?>"<?=$category===$cat?' aria-current="true"':''?>><?=h($cat)?></a><?php endforeach;?></nav>
<?php if($total>0):?><p class="news-results-count">Showing <?=h((string)($offset+1))?>–<?=h((string)min($offset+$perPage,$total))?> of <?=h((string)$total)?> articles</p><?php endif;?>
<div class="news-grid"><?php foreach($list as $row)echo news_card($row);if(!$list)echo '<p>No articles in this category yet.</p>';?></div>
<?php if($pages>1):?>
<nav class="news-pagination" aria-label="News pages">
<?php if($page>1):?><a href="<?=h($urlFor($page-1))?>" rel="prev">← Previous</a><?php else:?><span class="disabled" aria-disabled="true">← Previous</span><?php endif;?>
<?php $numbers=array_unique(array_merge([1,$pages],range(max(1,$page-2),min($pages,$page+2))));sort($numbers);$last=0;foreach($numbers as $number):if($last&&$number>$last+1):?><span class="ellipsis" aria-hidden="true">…</span><?php endif;if($number===$page):?><span aria-current="page" aria-label="Page <?=h((string)$number)?>"><?=h((string)$number)?></span><?php else:?><a href="<?=h($urlFor($number))?>" aria-label="Page <?=h((string)$number)?>"><?=h((string)$number)?></a><?php endif;$last=$number;endforeach;?>
<?php if($page<$pages):?><a href="<?=h($urlFor($page+1))?>" rel="next">Next →</a><?php else:?><span class="disabled" aria-disabled="true">Next →</span><?php endif;?>
</nav>
<?php endif;?>
<?php endif;public_end();
