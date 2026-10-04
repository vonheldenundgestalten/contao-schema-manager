(() => {
  'use strict';
  if (window.schemaRelationshipMapLoaded) return;
  window.schemaRelationshipMapLoaded = true;
  const boot = () => document.querySelectorAll('[data-schema-graph]').forEach(root => {
    if (root.sgInstance || !window.cytoscape) return;
    const {elements, labels:l} = JSON.parse(root.querySelector('[data-sg-data]').textContent);
    const q = selector => root.querySelector(selector);
    const colors = {Organization:'#b7dcff',LocalBusiness:'#a9e1d2',Person:'#d4c5f4',Service:'#ffe1a3',Product:'#ffcbb5',Event:'#ffc8e1',Missing:'#ffaaaa'};
    elements.nodes.forEach(n => { n.data.label = `${n.data.type}\n${n.data.name}${n.data.detail ? '\n'+n.data.detail : ''}`; n.data.color=colors[n.data.type] || '#cbd5e1'; });
    const cy = window.cytoscape({container:q('[data-sg-canvas]'),elements, minZoom:.12,maxZoom:2.5,wheelSensitivity:.25,
      style:[{selector:'node',style:{label:'data(label)','background-color':'data(color)',shape:'round-rectangle',width:190,height:90,'text-wrap':'wrap','text-max-width':174,'font-size':17,'line-height':1.35,color:'#14253b','text-valign':'center','border-width':1,'border-color':'#8597aa'}},
        {selector:'node[type = "LocalBusiness"]',style:{height:140}},
        {selector:'node[!published]',style:{'border-style':'dashed','border-width':2}},
        {selector:'node[?missing]',style:{'border-color':'#b42318','border-width':3}},
        {selector:'edge',style:{width:1.6,'curve-style':'bezier','target-arrow-shape':'triangle','line-color':'#9baabd','target-arrow-color':'#9baabd'}},
        {selector:'.isolated',style:{'border-color':'#c57512','border-width':3}},
        {selector:'.dim',style:{opacity:.13}},
        {selector:'.focused',style:{'border-width':3,'border-color':'#155f9b'}},
        {selector:'edge.focused',style:{label:'data(label)','font-size':10,'text-background-color':'#fff','text-background-opacity':.95,'text-background-padding':3,'line-color':'#155f9b','target-arrow-color':'#155f9b',width:2.5}}],
      layout:{name:'fcose',quality:'proof',animate:false,nodeDimensionsIncludeLabels:true,packComponents:false,nodeRepulsion:()=>12000,idealEdgeLength:()=>90,tilingPaddingVertical:40,tilingPaddingHorizontal:40,padding:35}});
    root.sgInstance=cy;
    const real=cy.nodes().filter(n=>!n.data('missing')), isolated=real.filter(n=>n.degree()===0);
    isolated.addClass('isolated');
    q('[data-sg-summary]').textContent=`${real.length} ${l.entities} · ${cy.edges().length} ${l.relations} · ${isolated.length} ${l.isolated} · ${cy.elements().components().length} ${l.groups}`;
    const select=q('[data-sg-select]');
    cy.nodes().forEach(n=> {const opt=document.createElement('option');opt.value=n.id();opt.textContent=`${n.data('type')} · ${n.data('name')}${n.data('detail')?' · '+n.data('detail'):''}`;select.append(opt);});
    const add=(parent,tag,text,cls)=>{const el=document.createElement(tag);el.textContent=text;if(cls)el.className=cls;parent.append(el);return el;};
    const details=q('[data-sg-details]');
    function focus(n){
      cy.elements().removeClass('focused dim');cy.elements().difference(n.closedNeighborhood()).addClass('dim');n.closedNeighborhood().addClass('focused');
      select.value=n.id();details.replaceChildren();add(details,'p',n.data('type'));add(details,'h3',n.data('name'));if(n.data('detail'))add(details,'p',n.data('detail'));
      add(details,'p',n.data('missing')?l.missing:n.data('published')?l.published:l.draft,'sg-status');
      if(n.degree()===0)add(details,'p',l.unconnected);
      if(!n.data('missing')){const a=add(details,'a',l.edit,'sg-edit');a.href=`?do=schema_manager&act=edit&id=${n.data('record')}`;a.dataset.turbo='false';}
      if(n.data('identity'))add(details,'code',n.data('identity'));
      const homes=n.data('homes');if(homes.length){add(details,'h4',l.homes);const list=add(details,'ul','');homes.forEach(h=>add(list,'li',`${h.language} · ${l.page} #${h.page} · ${h.published?l.published:l.draft}${h.name?' · '+h.name:''}`));}
      if(n.degree()){add(details,'h4',l.relations);const ul=add(details,'ul','');n.connectedEdges().forEach(e=>{const outgoing=e.source().id()===n.id(), other=outgoing?e.target():e.source();const li=add(ul,'li','');const btn=add(li,'button',`${outgoing?'→':'←'} ${e.data('label')} · ${other.data('name')}`);btn.type='button';btn.addEventListener('click',()=>{focus(other);cy.animate({center:{eles:other},duration:200});});});}
      cy.animate({center:{eles:n},duration:200});
    }
    cy.on('tap','node',e=>focus(e.target));select.addEventListener('change',()=>{const n=cy.getElementById(select.value);if(n.length)focus(n);});
    function filter(){const term=q('[data-sg-search]').value.trim().toLocaleLowerCase();const only=q('[data-sg-isolated]').checked;cy.elements().removeClass('dim focused');
      const found=cy.nodes().filter(n=>(!only || (n.degree()===0&&!n.data('missing')))&&(!term || `${n.data('name')} ${n.data('type')} ${n.data('detail')}`.toLocaleLowerCase().includes(term)));
      if(term||only){cy.elements().difference(found).addClass('dim');found.addClass('focused');if(found.length)cy.fit(found,60);}
      else cy.fit(undefined,45);
    }
    q('[data-sg-search]').addEventListener('input',filter);q('[data-sg-isolated]').addEventListener('change',filter);
    q('[data-sg-fit]').addEventListener('click',()=>cy.fit(undefined,45));
    q('[data-sg-reset]').addEventListener('click',()=>{q('[data-sg-search]').value='';q('[data-sg-isolated]').checked=false;select.value='';filter();details.replaceChildren();add(details,'h3',l.select);add(details,'p',l.help);});
    const observer=new ResizeObserver(()=>cy.resize());observer.observe(q('[data-sg-canvas]'));root.sgCleanup=()=>{observer.disconnect();cy.destroy();root.sgInstance=null;};
  });
  document.addEventListener('turbo:load',boot);
  document.addEventListener('turbo:before-cache',()=>document.querySelectorAll('[data-schema-graph]').forEach(r=>r.sgCleanup?.()));
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
