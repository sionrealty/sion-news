<?php require __DIR__.'/common.php'; $items=[]; {
    $feed = @file_get_contents('https://sionrealtypm.com/reports/rentals.php', false, stream_context_create(['http'=>['timeout'=>3,'follow_location'=>0]]), 0, 24000000);
    $data = is_string($feed) ? json_decode($feed,true) : null;
    foreach (($data['rentals'] ?? []) as $ad) {
        if (!is_array($ad) || !is_string($ad['address'] ?? null) || !is_string($ad['title'] ?? null)) continue;
        $pics = array_values(array_filter((array)($ad['photos'] ?? []), static fn($v) => is_string($v) && preg_match('~^data:image/(?:jpeg|png|webp);base64,[A-Za-z0-9+/=]+$~D',$v)));
        preg_match('/\b[0-9]{5}(?:-[0-9]{4})?\b/', $ad['address'], $zip);
        $items[] = ['title'=>$ad['title'],'address'=>$ad['address'],'description'=>(is_string($ad['description']??null)?$ad['description']:''),'price'=>(is_numeric($ad['price']??null)?max(0,(float)$ad['price'])/100:0),'beds'=>(int)($ad['beds']??0),'baths'=>(float)($ad['baths']??0),'location'=>is_string($ad['area']??null)?$ad['area']:'','zip'=>$zip[0]??'','available'=>is_string($ad['availableDate']??null)?$ad['availableDate']:'','map_embed'=>'','portal_photos'=>$pics];
    }
}
page_start('Apartments for Rent','en','public-rentals'); ?>
<link rel="stylesheet" href="rentals.css?v=5">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
/* Presentation only: compact rows, one shared map, original listing content. */
#rental-results{display:block!important}
#rental-results .rental.panel{display:grid!important;grid-template-columns:148px minmax(0,1fr)!important;grid-template-rows:auto!important;gap:14px!important;padding:12px!important;margin:0 0 10px!important;min-height:0!important;align-items:start!important}
#rental-results .rental .gallery{grid-column:1!important;grid-row:1!important;width:148px!important;max-width:148px!important;min-width:0!important;height:auto!important;min-height:0!important;margin:0!important;padding:0!important;overflow:hidden}
#rental-results .rental .gallery img:not([hidden]){display:block!important;width:148px!important;height:112px!important;max-width:148px!important;max-height:112px!important;object-fit:cover!important;border-radius:8px!important}
#rental-results .rental .gallery img[hidden]{display:none!important}
#rental-results .rental .rental-copy{grid-column:2!important;grid-row:1!important;display:block!important;min-width:0!important;width:auto!important;margin:0!important;padding:0!important}
#rental-results .rental .rental-copy p{margin:3px 0!important;line-height:1.35!important}
#rental-results .rental .rental-copy h2{font-size:1.12rem!important;margin:3px 0!important}
#rental-results .rental .rental-copy .price{font-size:1.1rem!important;margin:0 0 4px!important}
#rental-results .rental .rental-copy .description{display:-webkit-box!important;-webkit-line-clamp:2!important;-webkit-box-orient:vertical!important;overflow:hidden!important}
#rental-results .rental .rental-copy .button{padding:7px 10px!important;margin-top:5px!important}
#rental-results .rental .gallery-controls{position:static!important;display:flex!important;align-items:center!important;justify-content:space-between!important;gap:4px!important;margin-top:4px!important}
#rental-results .rental .gallery-controls button{position:static!important;padding:2px 8px!important}
#rental-results .rental .property-map{display:none!important}
#rental-results[hidden],#sion-map-panel[hidden],#no-matches[hidden],#rental-results .rental[hidden]{display:none!important}
#sion-map-panel{padding:14px!important;margin:12px 0!important}
#sion-all-map{height:520px;width:100%;border-radius:9px;z-index:0;background:#e9efeb}
@media(max-width:650px){#rental-results .rental.panel{grid-template-columns:90px minmax(0,1fr)!important;gap:9px!important;padding:9px!important}#rental-results .rental .gallery{width:90px!important;max-width:90px!important}#rental-results .rental .gallery img:not([hidden]){width:90px!important;height:90px!important}#sion-all-map{height:410px}}
</style>
<header class="bar rentals-bar"><img class="rentals-logo" src="sion-logo.png" alt="Sion Realty &amp; Property Management"><div><span class="eyebrow">SION REALTY & PROPERTY MANAGEMENT</span><h1>Apartments for Rent</h1><p>Find your next home in Orlando.</p></div></header>
<?php if(!$items):?><section class="panel"><h2>No rentals listed right now</h2><p>Please contact us to ask about availability.</p><a class="button" href="https://wa.me/13214427790" target="_blank" rel="noopener">Information on WhatsApp</a></section><?php endif?>
<?php if($items):?><div class="rental-tools"><label for="zip-search">Search by ZIP code<input type="search" id="zip-search" inputmode="numeric" maxlength="10" placeholder="e.g. 32835"></label><div class="view-switch" aria-label="Rental view"><button type="button" data-view="list" aria-pressed="true">List</button><button type="button" data-view="map" aria-pressed="false">Map</button></div><p class="muted small" id="rental-count" role="status"><?=count($items)?> available</p></div><?php endif?>
<?php if($items):?><section id="sion-map-panel" class="panel" hidden><h2>Available properties on the map</h2><p id="sion-map-message" class="muted small">Select Map to see all available properties.</p><div id="sion-all-map" aria-label="Map of available rental properties"></div></section><?php endif?>
<div class="rentals" id="rental-results"><?php foreach($items as $item):$pics=$item['portal_photos'];$address=trim($item['address']);$search=$address?:$item['location'];$mapAddress=$address;if($mapAddress && $item['zip'] && !str_contains($mapAddress,$item['zip']))$mapAddress.=' '.$item['zip'];$automaticMap=$mapAddress?'https://maps.google.com/maps?q='.rawurlencode($mapAddress).'&z=16&output=embed':'';$message='Hello! I saw '.$item['title'].($item['price']>0?' listed for '.money($item['price']).' per month':'').' on the Sion website. I would like more information.';$whats='https://wa.me/13214427790?text='.rawurlencode($message);$mapSearch='https://www.google.com/maps/search/?api=1&query='.rawurlencode($search);$directions='https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($search);?>
<article class="rental panel" data-rental data-zip="<?=h($item['zip']?:$item['location'])?>"><div class="gallery" data-gallery><?php if(!$pics):?><p class="muted">Pictures Coming Soon</p><?php endif?><?php foreach($pics as $i=>$pic):?><img src="<?=h(is_string($pic)?$pic:'media.php?id='.rawurlencode((string)$pic['id']))?>" alt="<?=h($item['title'])?> — photo <?=$i+1?>" <?=$i?'hidden loading="lazy"':''?>><?php endforeach?><?php if(count($pics)>1):?><div class="gallery-controls"><button type="button" data-prev aria-label="Previous photo">←</button><span data-count>1 / <?=count($pics)?></span><button type="button" data-next aria-label="Next photo">→</button></div><?php endif?></div>
<div class="rental-copy"><p class="price"><?php if($item['price']>0):?><?=h(money($item['price']))?><span> / month</span><?php else:?>Contact us for rent<?php endif?></p><h2><?=h($item['title'])?></h2><p class="muted"><?=h($item['location'])?></p><?php if($address):?><p class="rental-address"><a href="<?=h($mapSearch)?>" target="_blank" rel="noopener"><?=h($address)?><?= $item['zip'] && !str_contains($address,$item['zip'])?' · '.h($item['zip']):''?></a></p><?php endif?><?php if($item['beds']>0 || $item['baths']>0):?><p class="facts"><?php if($item['beds']>0):?><?=(int)$item['beds']?> beds<?php endif?><?php if($item['beds']>0 && $item['baths']>0):?> · <?php endif?><?php if($item['baths']>0):?><?=h((float)$item['baths'])?> baths<?php endif?></p><?php endif?><?php if($item['available']):?><p>Available <?=h(date('M j, Y',strtotime($item['available'])))?></p><?php endif?><?php if($item['description']!==''):?><p class="description"><?=nl2br(h($item['description']))?></p><?php endif?><a class="button" href="<?=h($whats)?>" target="_blank" rel="noopener">Information on WhatsApp</a><?php if($address):?><a class="map-link" href="<?=h($directions)?>" target="_blank" rel="noopener">Get directions ↗</a><?php endif?></div>
<details class="property-map" aria-label="Location of <?=h($item['title'])?>"><summary>View property map · <?=h($address?:$item['location'])?></summary><?php $mapSource=$automaticMap?:$item['map_embed'];if($mapSource):?><iframe src="<?=h($mapSource)?>" title="Google Maps — <?=h($search)?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe><p class="muted small"><?=h($address?:$item['location'])?> · Use + / − to zoom and drag the map to explore.</p><?php else:?><p class="muted">The location map will appear here when the property address is added.</p><?php endif?></details></article>
<?php endforeach?></div><p id="no-matches" class="panel" hidden>No available rentals match this ZIP code. Try another ZIP or clear the search.</p><p class="muted small">Availability and rental terms are subject to confirmation.</p><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
 const list=document.getElementById('rental-results'),panel=document.getElementById('sion-map-panel');
 const listButton=document.querySelector('.view-switch [data-view="list"]'),mapButton=document.querySelector('.view-switch [data-view="map"]');
 const search=document.getElementById('zip-search'),count=document.getElementById('rental-count'),noMatches=document.getElementById('no-matches'),message=document.getElementById('sion-map-message');
 if(!list||!panel||!listButton||!mapButton)return;
 const cards=Array.from(list.querySelectorAll('[data-rental]'));let map=null,layer=null,mode='list',requestId=0;
 function filtered(){return cards.filter(card=>!card.hidden);}
 function filter(){
   const q=(search?.value||'').trim().toLowerCase();
   cards.forEach(card=>card.hidden=!!q&&!((card.dataset.zip||'').toLowerCase().includes(q)));
   const n=filtered().length;if(count)count.textContent=n+' available';if(noMatches)noMatches.hidden=n!==0;
   if(mode==='map')renderMap();
 }
 function show(which){
   mode=which;const isMap=which==='map';panel.hidden=!isMap;list.hidden=isMap;
   listButton.setAttribute('aria-pressed',String(!isMap));mapButton.setAttribute('aria-pressed',String(isMap));
   if(isMap)renderMap();
 }
 listButton.addEventListener('click',()=>show('list'));
 mapButton.addEventListener('click',()=>show('map'));
 if(search)search.addEventListener('input',filter);
 list.querySelectorAll('[data-gallery]').forEach(gallery=>{
   const images=Array.from(gallery.querySelectorAll('img'));if(!images.length)return;let current=0;
   function update(){images.forEach((img,i)=>img.hidden=i!==current);const counter=gallery.querySelector('[data-count]');if(counter)counter.textContent=(current+1)+' / '+images.length;}
   gallery.querySelector('[data-prev]')?.addEventListener('click',()=>{current=(current-1+images.length)%images.length;update();});
   gallery.querySelector('[data-next]')?.addEventListener('click',()=>{current=(current+1)%images.length;update();});
   update();
 });
 async function renderMap(){
   const current=++requestId;
   if(typeof L==='undefined'){message.textContent='The map could not load. Please refresh this page.';return;}
   if(!map){map=L.map('sion-all-map').setView([28.49,-81.46],11);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap contributors'}).addTo(map);layer=L.featureGroup().addTo(map);}
   setTimeout(()=>map.invalidateSize(),100);layer.clearLayers();
   const visible=filtered(),locations=new Map(),bounds=[];let placed=0;
   for(const card of visible){const a=card.querySelector('.rental-address a');const address=a?.textContent?.trim()?.replace(/ · \d{5}$/, '')||'';if(address){if(!locations.has(address))locations.set(address,[]);locations.get(address).push(card);}}
   message.textContent='Locating properties…';
   for(const [address,related] of locations){
     if(current!==requestId||mode!=='map')return;
     let pos=null;const key='sion-rental-location:'+address;
     try{pos=JSON.parse(sessionStorage.getItem(key)||'null');}catch(e){}
     if(!pos){
       try{
         const ctrl=new AbortController(),timer=setTimeout(()=>ctrl.abort(),9000);
         let res;try{res=await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=us&q='+encodeURIComponent(address+', Orlando, FL, USA'),{signal:ctrl.signal});}finally{clearTimeout(timer);}
         if(res.ok){const json=await res.json();if(json.length)pos=[Number(json[0].lat),Number(json[0].lon)];}
         if(pos)try{sessionStorage.setItem(key,JSON.stringify(pos));}catch(e){}
       }catch(e){}
       await new Promise(resolve=>setTimeout(resolve,1150));
     }
     if(current!==requestId||mode!=='map')return;
     if(!pos||!Number.isFinite(pos[0])||!Number.isFinite(pos[1]))continue;
     for(const card of related){
       const title=card.querySelector('h2')?.textContent||'Available rental',price=card.querySelector('.price')?.textContent||'';
       const popup=document.createElement('div'),heading=document.createElement('strong'),p=document.createElement('p');
       heading.textContent=title;p.textContent=price;popup.append(heading,p);
       const link=card.querySelector('.rental-address a');if(link){const a=document.createElement('a');a.href=link.href;a.target='_blank';a.rel='noopener';a.textContent='View address';popup.append(a);}
       L.marker(pos).addTo(layer).bindPopup(popup);bounds.push(pos);placed++;
     }
     message.textContent=placed+' of '+visible.length+' properties on the map.';
   }
   if(bounds.length)map.fitBounds(bounds,{padding:[40,40],maxZoom:15});
   message.textContent=placed===visible.length?placed+' available properties on the map.':placed+' of '+visible.length+' properties located. Check List for all properties.';
 }
 filter();
});
</script><?php page_end(); ?>