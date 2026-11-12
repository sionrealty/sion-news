(()=>{
'use strict';if(document.getElementById('sion-rentals-launch'))return;
const source=new URL(document.currentScript.src);const base=new URL('./',source);if(base.origin!==location.origin)return;
const ready=async()=>{
 let config;try{const response=await fetch(new URL('status.php',base),{cache:'no-store',credentials:'same-origin'});if(!response.ok)return;config=await response.json();}catch(e){return;}
 if(!config.enabled)return;
 const style=document.createElement('style');style.textContent=`#sion-rentals-launch.sion-rentals-floating{position:fixed;bottom:22px;right:20px;z-index:9000;background:#d7b66f;color:#16120a;border:1px solid #f5deaa;border-radius:28px;padding:14px 20px;box-shadow:0 4px 24px #0005;font:600 14px/1.4 Arial;cursor:pointer;text-decoration:none}#sion-rentals-launch:focus-visible{outline:3px solid white;outline-offset:3px}#sion-rentals-launch.sion-rentals-inline{font-family:inherit;cursor:pointer}.cinematic .actions.sion-rentals-actions{flex-wrap:wrap}@media(max-width:700px){.cinematic .actions.sion-rentals-actions{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:500px){#sion-rentals-launch.sion-rentals-floating{bottom:16px;right:12px;padding:12px 16px;font-size:13px}}`;
 document.head.append(style);
 const link=document.createElement('a');
 link.id='sion-rentals-launch';
 link.href='/rent';
 link.target='_blank';
 link.rel='noopener';
 link.textContent='Apartments for Rent';
 const actions=document.querySelector('.cinematic .actions');
 if(actions){
  link.className='button outline sion-rentals-inline';
  actions.classList.add('sion-rentals-actions');
  actions.append(link);
 }else{
  link.className='sion-rentals-floating';
  document.body.append(link);
 }
};if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',ready,{once:true});else ready();
})();
