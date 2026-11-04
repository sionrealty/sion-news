/* Public rentals: CSP-safe interactions, no external JS libraries. */
document.addEventListener('DOMContentLoaded',()=>{
 const list=document.getElementById('rental-results'),panel=document.getElementById('sion-map-panel'),search=document.getElementById('zip-search'),count=document.getElementById('rental-count'),empty=document.getElementById('no-matches');
 const listBtn=document.querySelector('[data-view="list"]'),mapBtn=document.querySelector('[data-view="map"]');
 if(!list||!listBtn||!mapBtn)return;
 const cards=[...list.querySelectorAll('[data-rental]')],smallMap=document.getElementById('sion-list-map'),smallFrame=document.getElementById('sion-list-map-frame');let mode='list';
 const visible=()=>cards.filter(c=>!c.hidden);
 function render(){
  const q=(search?.value||'').trim().toLowerCase();
  const dialog=document.getElementById('sion-photo-dialog'),expanded=document.getElementById('sion-photo-expanded'),photoTitle=document.getElementById('sion-photo-title'),photoCounter=document.getElementById('sion-photo-counter');
 let selectedGallery=null;
 function displayExpanded(){
  if(!selectedGallery||!expanded)return;
  const {sources,index,title}=selectedGallery;
  expanded.src=sources[index].src;
  expanded.alt=title+' — photo '+(index+1);
  if(photoTitle)photoTitle.textContent=title;
  if(photoCounter)photoCounter.textContent=(index+1)+' / '+sources.length;
 }
 function stepExpanded(delta){
  if(!selectedGallery)return;
  selectedGallery.index=(selectedGallery.index+delta+selectedGallery.sources.length)%selectedGallery.sources.length;
  selectedGallery.select(selectedGallery.index);
  displayExpanded();
 }
 document.getElementById('sion-photo-close')?.addEventListener('click',()=>dialog?.close());
 document.getElementById('sion-photo-prev')?.addEventListener('click',()=>stepExpanded(-1));
 document.getElementById('sion-photo-next')?.addEventListener('click',()=>stepExpanded(1));
 dialog?.addEventListener('click',event=>{if(event.target===dialog)dialog.close()});
 dialog?.addEventListener('keydown',event=>{if(event.key==='ArrowLeft'){event.preventDefault();stepExpanded(-1)}else if(event.key==='ArrowRight'){event.preventDefault();stepExpanded(1)}});
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
  gallery?.querySelector('[data-expand]')?.addEventListener('click',()=>{
   if(!dialog||typeof dialog.showModal!=='function')return;
   selectedGallery={sources,index,title:c.querySelector('h2')?.textContent||'Property',select};
   displayExpanded();dialog.showModal();
  });
  select(0);
 });render();
});
