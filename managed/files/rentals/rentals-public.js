/* Public rentals: CSP-safe list, map and photo galleries. */
document.addEventListener('DOMContentLoaded',()=>{
 const list=document.getElementById('rental-results'),panel=document.getElementById('sion-map-panel'),search=document.getElementById('zip-search'),count=document.getElementById('rental-count'),empty=document.getElementById('no-matches');
 const listBtn=document.querySelector('[data-view="list"]'),mapBtn=document.querySelector('[data-view="map"]');
 if(!list||!listBtn||!mapBtn)return;
 const cards=[...list.querySelectorAll('[data-rental]')],smallMap=document.getElementById('sion-list-map'),smallFrame=document.getElementById('sion-list-map-frame');
 let mode='list';
 const visible=()=>cards.filter(c=>!c.hidden);
 function render(){
  const q=(search?.value||'').trim().toLowerCase();
  cards.forEach(c=>{c.hidden=!!q&&!String(c.dataset.zip||'').toLowerCase().includes(q)});
  const active=visible();
  if(count)count.textContent=active.length+' available';
  if(empty)empty.hidden=active.length!==0;
  list.hidden=mode==='map';
  if(panel)panel.hidden=mode!=='map';
  if(smallMap)smallMap.hidden=mode==='map'||active.length===0;
  if(mode==='list'&&smallFrame&&active.length){
   const address=active[0].dataset.address||'Orlando, FL';
   const url='https://maps.google.com/maps?q='+encodeURIComponent(address+', Orlando, FL')+'&z=13&output=embed';
   if(smallFrame.getAttribute('src')!==url)smallFrame.src=url;
  }
  listBtn.setAttribute('aria-pressed',String(mode==='list'));
  mapBtn.setAttribute('aria-pressed',String(mode==='map'));
  if(mode==='map')showMap(active);
 }
 function showMap(active){
  const iframe=document.getElementById('sion-all-map'),links=document.getElementById('sion-map-links'),msg=document.getElementById('sion-map-message');
  if(!iframe||!links)return;
  links.replaceChildren();
  const addresses=active.map(c=>({address:c.dataset.address||'',title:c.querySelector('h2')?.textContent||'Property'})).filter(x=>x.address);
  for(const a of addresses){
   const link=document.createElement('a');
   link.href='https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(a.address);
   link.target='_blank';link.rel='noopener';link.textContent=a.title+' — '+a.address;links.append(link);
  }
  const q=addresses.length?addresses[0].address+', Orlando, FL':'Orlando, FL';
  iframe.src='https://maps.google.com/maps?q='+encodeURIComponent(q)+'&z=12&output=embed';
  if(msg)msg.textContent=addresses.length+' properties listed below the map. Select an address to view its exact location.';
 }
 search?.addEventListener('input',render);
 listBtn.addEventListener('click',()=>{mode='list';render()});
 mapBtn.addEventListener('click',()=>{mode='map';render()});
 const dialog=document.getElementById('sion-photo-dialog'),expanded=document.getElementById('sion-photo-expanded'),photoTitle=document.getElementById('sion-photo-title'),photoCounter=document.getElementById('sion-photo-counter');
 let selectedGallery=null;
 function displayExpanded(){
  if(!selectedGallery||!expanded)return;
  const {sources,index,title}=selectedGallery;
  expanded.src=sources[index].src;expanded.alt=title+' — photo '+(index+1);
  if(photoTitle)photoTitle.textContent=title;
  if(photoCounter)photoCounter.textContent=(index+1)+' / '+sources.length;
 }
 function stepExpanded(delta){
  if(!selectedGallery)return;
  selectedGallery.index=(selectedGallery.index+delta+selectedGallery.sources.length)%selectedGallery.sources.length;
  selectedGallery.select(selectedGallery.index);displayExpanded();
 }
 document.getElementById('sion-photo-close')?.addEventListener('click',()=>dialog?.close());
 document.getElementById('sion-photo-prev')?.addEventListener('click',()=>stepExpanded(-1));
 document.getElementById('sion-photo-next')?.addEventListener('click',()=>stepExpanded(1));
 dialog?.addEventListener('click',event=>{if(event.target===dialog)dialog.close()});
 dialog?.addEventListener('keydown',event=>{
  if(event.key==='ArrowLeft'){event.preventDefault();stepExpanded(-1)}
  else if(event.key==='ArrowRight'){event.preventDefault();stepExpanded(1)}
 });
 cards.forEach(c=>{
  const gallery=c.querySelector('[data-gallery]'),main=gallery?.querySelector('[data-main-photo]'),thumbs=[...(gallery?.querySelectorAll('[data-thumb]')||[])],counter=gallery?.querySelector('[data-count]');
  if(!main)return;
  const sources=thumbs.length?thumbs.map(t=>t.querySelector('img')).filter(Boolean):[main];
  let index=0;
  function select(next){
   index=(next+sources.length)%sources.length;
   main.src=sources[index].src;
   main.alt=(c.querySelector('h2')?.textContent||'Property')+' — photo '+(index+1);
   thumbs.forEach((t,i)=>t.setAttribute('aria-pressed',String(i===index)));
   if(counter)counter.textContent=(index+1)+' / '+sources.length;
  }
  thumbs.forEach((t,i)=>t.addEventListener('click',()=>select(i)));
  gallery?.querySelector('[data-prev]')?.addEventListener('click',()=>select(index-1));
  gallery?.querySelector('[data-next]')?.addEventListener('click',()=>select(index+1));
  const expandPhoto=()=>{
   if(!dialog||typeof dialog.showModal!=='function')return;
   selectedGallery={sources,index,title:c.querySelector('h2')?.textContent||'Property',select};
   displayExpanded();dialog.showModal();
  };
  gallery?.querySelector('[data-expand]')?.addEventListener('click',expandPhoto);
  gallery?.querySelector('[data-open-larger]')?.addEventListener('click',expandPhoto);
  select(0);
 });
 render();
});
