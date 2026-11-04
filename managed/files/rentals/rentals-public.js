/* Public rentals: CSP-safe interactions, no external JS libraries. */
document.addEventListener('DOMContentLoaded',()=>{
 const list=document.getElementById('rental-results'),panel=document.getElementById('sion-map-panel'),search=document.getElementById('zip-search'),count=document.getElementById('rental-count'),empty=document.getElementById('no-matches');
 const listBtn=document.querySelector('[data-view="list"]'),mapBtn=document.querySelector('[data-view="map"]');
 if(!list||!listBtn||!mapBtn)return;
 const cards=[...list.querySelectorAll('[data-rental]')],smallMap=document.getElementById('sion-list-map'),smallFrame=document.getElementById('sion-list-map-frame');let mode='list';
 const visible=()=>cards.filter(c=>!c.hidden);
 function render(){
  const q=(search?.value||'').trim().toLowerCase();
  cards.forEach(c=>{c.hidden=!!q&&!String(c.dataset.zip||'').toLowerCase().includes(q)});
  const active=visible();if(count)count.textContent=active.length+' available';
  if(empty)empty.hidden=active.length!==0;
  list.hidden=mode==='map';if(panel)panel.hidden=mode!=='map';if(smallMap)smallMap.hidden=mode==='map'||active.length===0;
  if(mode==='list'&&smallFrame&&active.length){const address=active[0].dataset.address||'Orlando, FL';const url='https://maps.google.com/maps?q='+encodeURIComponent(address+', Orlando, FL')+'&z=13&output=embed';if(smallFrame.getAttribute('src')!==url)smallFrame.src=url;}
  listBtn.setAttribute('aria-pressed',String(mode==='list'));mapBtn.setAttribute('aria-pressed',String(mode==='map'));
  if(mode==='map')showMap(active);
 }
 function showMap(active){
  const iframe=document.getElementById('sion-all-map'),links=document.getElementById('sion-map-links'),msg=document.getElementById('sion-map-message');
  if(!iframe||!links)return;
  links.replaceChildren();
  const addresses=active.map(c=>({address:c.dataset.address||'',title:c.querySelector('h2')?.textContent||'Property'})).filter(x=>x.address);
  for(const a of addresses){const link=document.createElement('a');link.href='https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(a.address);link.target='_blank';link.rel='noopener';link.textContent=a.title+' — '+a.address;links.append(link)}
  const q=addresses.length?addresses[0].address+', Orlando, FL':'Orlando, FL';
  iframe.src='https://maps.google.com/maps?q='+encodeURIComponent(q)+'&z=12&output=embed';
  if(msg)msg.textContent=addresses.length+' properties listed below the map. Select an address to view its exact location.';
 }
 search?.addEventListener('input',render);
 listBtn.addEventListener('click',()=>{mode='list';render()});
 mapBtn.addEventListener('click',()=>{mode='map';render()});
 cards.forEach(c=>{
  const gallery=c.querySelector('[data-gallery]'),images=[...(gallery?.querySelectorAll('img')||[])],counter=gallery?.querySelector('[data-count]');let index=0;
  const show=()=>{images.forEach((img,i)=>{img.hidden=i!==index});if(counter)counter.textContent=(index+1)+' / '+images.length};
  gallery?.querySelector('[data-prev]')?.addEventListener('click',()=>{index=(index-1+images.length)%images.length;show()});
  gallery?.querySelector('[data-next]')?.addEventListener('click',()=>{index=(index+1)%images.length;show()});
  if(images.length)show();
 });render();
});
