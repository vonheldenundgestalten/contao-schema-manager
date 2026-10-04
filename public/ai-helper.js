(() => {
'use strict';
const scriptVersion='2026-10-04-multilingual-1';
if(window.schemaAiLoaded===scriptVersion)return;
// Turbo can load a new asset into a window still running the old handlers.
// Refresh once so old and new versions cannot coexist. GET never starts analysis.
if(window.schemaAiLoaded){location.reload();return;}
window.schemaAiLoaded=scriptVersion;
// Keep the job outside a particular DOM node: Turbo may replace that node mid-request.
let activeRun=null;
const syncRunView=()=>{
 const run=activeRun;if(!run||location.href!==run.url)return;
 const form=document.querySelector('[data-sai-run]');if(!form)return;
 const start=form.querySelector('[data-sai-start]'),cancel=form.querySelector('[data-sai-stop]'),progress=form.querySelector('[data-sai-progress]');
 start.disabled=true;cancel.hidden=false;cancel.disabled=run.stop;
 form.dataset.remaining=String(run.remaining);
 progress.textContent=`${run.phase==='localize'?form.dataset.translationLabel:progress.dataset.label} ${run.total-run.remaining}/${run.total} · ${Math.floor((Date.now()-run.begun)/1000)}s`;
 document.querySelectorAll('[data-sai-review] button').forEach(el=>el.disabled=true);
};
const analyze=async form=>{
 if(activeRun)return;
 const run={url:location.href,body:new FormData(form),total:Number(form.dataset.total),remaining:Number(form.dataset.remaining),phase:form.dataset.phase,begun:Date.now(),stop:false,leave:false};
 activeRun=run;syncRunView();const timer=setInterval(syncRunView,1000);
 try{do{
  run.begun=Date.now();syncRunView();
  const response=await fetch(run.url,{method:'POST',body:run.body,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}});
  if(!(response.headers.get('content-type')||'').includes('application/json'))throw new Error('The connection ended unexpectedly. Reload to check the saved run before retrying.');
  const result=await response.json();
  if(run.leave||location.href!==run.url)return;
  if(!response.ok||result.paused)throw new Error(result.message||'Analysis paused. Reload to review.');
  run.phase=result.phase||'analyze';
  if(Number.isFinite(result.total))run.total=result.total;
  if(Number.isFinite(result.remaining))run.remaining=result.remaining;
  syncRunView();
  if(result.done||run.stop){location.reload();return;}
 }while(!run.stop);
 }catch(error){
  if(!run.leave&&location.href===run.url){
   const current=document.querySelector('[data-sai-run]');
   if(current){current.querySelector('[data-sai-progress]').textContent=error.message;current.querySelector('[data-sai-start]').disabled=false;current.querySelector('[data-sai-stop]').hidden=true;}
   document.querySelectorAll('[data-sai-review] button').forEach(el=>el.disabled=false);
  }
 }finally{clearInterval(timer);if(activeRun===run)activeRun=null;}
};
window.addEventListener('pagehide',()=>{if(activeRun)activeRun.leave=true;});
document.addEventListener('turbo:before-visit',event=>{
 if(activeRun&&event.detail?.url&&new URL(event.detail.url,location.href).href!==activeRun.url)activeRun.leave=true;
});
// DOM attributes survive Turbo snapshots; event listeners do not. Track live nodes only.
const initialized=new WeakSet();
const boot=()=>document.querySelectorAll('[data-schema-ai]').forEach(root=>{
 if(initialized.has(root))return;initialized.add(root);delete root.dataset.ready;
 const checks=()=>Array.from(root.querySelectorAll('input[name="selected[]"]'));
 const dependencies=el=>JSON.parse(el.dataset.depends||'[]');
 const requireParents=(el,seen=new Set())=>{if(seen.has(el.value))return;seen.add(el.value);dependencies(el).forEach(id=>{const parent=checks().find(x=>x.value===String(id));if(parent){parent.checked=true;requireParents(parent,seen);}});};
 const updateSelection=()=>{
  const count=checks().filter(el=>el.checked).length;
  const label=root.querySelector('[data-sai-count]');if(label)label.textContent=`${count} ${label.dataset.label}`;
  root.querySelectorAll('[data-sai-review] button[name="ai_action"]').forEach(el=>el.disabled=count===0);
 };
 checks().forEach(el=>el.addEventListener('change',()=>{
  if(el.checked)requireParents(el);
  else {let changed;do{changed=false;checks().forEach(child=>{if(child.checked&&dependencies(child).some(id=>checks().some(parent=>parent.value===String(id)&&!parent.checked))){child.checked=false;changed=true;}});}while(changed);}
  updateSelection();
 }));
 root.querySelector('[data-sai-all]')?.addEventListener('click',()=>{checks().forEach(el=>{el.checked=true;requireParents(el);});updateSelection();});
 root.querySelector('[data-sai-clear]')?.addEventListener('click',()=>{checks().forEach(el=>el.checked=false);updateSelection();});
 root.querySelectorAll('[data-sai-edit]').forEach(el=>el.addEventListener('input',()=>{const value=root.querySelector(`[data-sai-value="${el.dataset.saiEdit}"]`);if(value)value.textContent=el.value;}));
 updateSelection();
 const feedback=root.querySelector('[data-sai-feedback]');
 if(feedback){let sending=false;feedback.addEventListener('submit',async event=>{
  event.preventDefault();if(sending)return;sending=true;
  const button=feedback.querySelector('button[type="submit"]'),status=feedback.querySelector('[data-sai-feedback-status]');
  button.disabled=true;const begun=Date.now();const show=()=>status.textContent=`${status.dataset.label} ${Math.floor((Date.now()-begun)/1000)}s`;
  show();const tick=setInterval(show,1000);
  try{
   const response=await fetch(location.href,{method:'POST',body:new FormData(feedback),credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}});
   if(!(response.headers.get('content-type')||'').includes('application/json'))throw new Error('The connection ended. Check recent analyses before retrying; a revised review may already be saved.');
   const result=await response.json();if(!response.ok||!result.url)throw new Error(result.message||'Refinement could not complete.');
   if(root.isConnected)location.assign(result.url);
  }catch(error){if(root.isConnected)status.textContent=error.message;button.disabled=false;sending=false;}finally{clearInterval(tick);}
 });}
 const form=root.querySelector('[data-sai-run]');if(!form)return;
 const start=form.querySelector('[data-sai-start]'),cancel=form.querySelector('[data-sai-stop]');
 start.addEventListener('click',()=>analyze(form));
 cancel.addEventListener('click',()=>{if(activeRun){activeRun.stop=true;syncRunView();}});
 form.addEventListener('submit',event=>{event.preventDefault();analyze(form);});
 start.disabled=false;cancel.hidden=true;root.querySelector('[data-sai-loading]')?.setAttribute('hidden','');
 syncRunView();
});
document.addEventListener('turbo:load',boot);if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
