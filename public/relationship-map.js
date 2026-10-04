(() => {
  'use strict';
  if (window.schemaRelationshipMapLoaded) return;
  window.schemaRelationshipMapLoaded = true;
  const boot = () => document.querySelectorAll('[data-schema-graph]').forEach(root => {
    if (root.sgInstance || !window.cytoscape) return;
    const {elements, labels:l} = JSON.parse(root.querySelector('[data-sg-data]').textContent);
    const q = selector => root.querySelector(selector);
    const events=new AbortController();
    const listen=(element,type,callback)=>element?.addEventListener(type,callback,{signal:events.signal});
    const colors = {Organization:'#b7dcff',LocalBusiness:'#a9e1d2',Person:'#d4c5f4',Service:'#ffe1a3',Product:'#ffcbb5',Event:'#ffc8e1',Missing:'#ffaaaa',WebSite:'#91d8ea',WebPage:'#dbe6ef',AboutPage:'#dbe6ef',ContactPage:'#dbe6ef',CollectionPage:'#dbe6ef',ProfilePage:'#dbe6ef',ItemPage:'#dbe6ef',BlogPosting:'#d5edb0',Article:'#d5edb0',NewsArticle:'#d5edb0',JobPosting:'#f2d0aa'};
    elements.nodes.forEach(n => { n.data.label = `${n.data.type}\n${n.data.name.length>65?n.data.name.slice(0,62)+'…':n.data.name}${n.data.detail ? '\n'+n.data.detail : ''}`; n.data.color=colors[n.data.type] || '#cbd5e1'; });
    const cy = window.cytoscape({container:q('[data-sg-canvas]'),elements, minZoom:.12,maxZoom:2.5,wheelSensitivity:.25,
      style:[{selector:'node',style:{label:'data(label)','background-color':'data(color)',shape:'round-rectangle',width:190,height:90,'text-wrap':'wrap','text-max-width':174,'font-size':17,'line-height':1.35,color:'#14253b','text-valign':'center','border-width':1,'border-color':'#8597aa'}},
        {selector:'node[type = "LocalBusiness"]',style:{height:140}},
        {selector:'node[kind = "news"], node[kind = "page"]',style:{width:230,height:140,'text-max-width':212}},
        {selector:'node[!published]',style:{'border-style':'dashed','border-width':2}},
        {selector:'node[?missing]',style:{'border-color':'#b42318','border-width':3}},
        {selector:'edge',style:{width:1.6,'curve-style':'bezier','target-arrow-shape':'triangle','line-color':'#9baabd','target-arrow-color':'#9baabd'}},
        {selector:'.isolated',style:{'border-color':'#c57512','border-width':3}},
        {selector:'.dim',style:{opacity:.13}},
        {selector:'.focused',style:{'border-width':3,'border-color':'#155f9b'}},
        {selector:'edge.focused',style:{label:'data(label)','font-size':10,'text-background-color':'#fff','text-background-opacity':.95,'text-background-padding':3,'line-color':'#155f9b','target-arrow-color':'#155f9b',width:2.5}}],
      layout:{name:'fcose',quality:'proof',animate:false,nodeDimensionsIncludeLabels:true,packComponents:false,nodeRepulsion:()=>100000,idealEdgeLength:()=>150,tilingPaddingVertical:40,tilingPaddingHorizontal:40,padding:35}});
    // Dense publisher/site hubs can pull rectangular cards into each other.
    // Resolve overlaps after the force layout while preserving its neighborhoods.
    const boxes=cy.nodes().map(n=>{const b=n.boundingBox({includeLabels:true});return {node:n,x:n.position('x'),y:n.position('y'),w:b.w,h:b.h};});
    for(let pass=0;pass<120;pass++){
      let moved=false;
      for(let i=0;i<boxes.length;i++)for(let j=i+1;j<boxes.length;j++){
        const a=boxes[i],b=boxes[j],dx=a.x-b.x,dy=a.y-b.y;
        const ox=(a.w+b.w)/2+24-Math.abs(dx),oy=(a.h+b.h)/2+24-Math.abs(dy);
        if(ox<=0||oy<=0)continue;
        moved=true;
        if(ox<oy){const shift=(ox/2+.5)*(dx>=0?1:-1);a.x+=shift;b.x-=shift;}
        else{const shift=(oy/2+.5)*(dy>=0?1:-1);a.y+=shift;b.y-=shift;}
      }
      if(!moved)break;
    }
    cy.batch(()=>boxes.forEach(b=>b.node.position({x:b.x,y:b.y})));
    cy.fit(undefined,35);
    root.sgInstance=cy;
    const real=cy.nodes().filter(n=>!n.data('missing')), isolated=real.filter(n=>n.degree()===0);
    isolated.addClass('isolated');
    q('[data-sg-summary]').textContent=`${real.length} ${l.entities} · ${cy.edges().length} ${l.relations} · ${isolated.length} ${l.isolated} · ${cy.elements().components().length} ${l.groups}`;
    const select=q('[data-sg-select]');select.options.length=1;
    cy.nodes().forEach(n=> {const opt=document.createElement('option');opt.value=n.id();opt.textContent=`${n.data('type')} · ${n.data('name')}${n.data('detail')?' · '+n.data('detail'):''}`;select.append(opt);});
    const add=(parent,tag,text,cls)=>{const el=document.createElement(tag);el.textContent=text;if(cls)el.className=cls;parent.append(el);return el;};
    const details=q('[data-sg-details]');
    function focus(n){
      cy.elements().removeClass('focused dim');cy.elements().difference(n.closedNeighborhood()).addClass('dim');n.closedNeighborhood().addClass('focused');
      select.value=n.id();details.replaceChildren();add(details,'p',n.data('type'));add(details,'h3',n.data('name'));if(n.data('detail'))add(details,'p',n.data('detail'));
      add(details,'p',n.data('missing')?l.missing:n.data('published')?l.published:l.draft,'sg-status');
      if(n.degree()===0)add(details,'p',l.unconnected);
      if(!n.data('missing') && n.data('kind')!=='coreAuthor'){
        const kind=n.data('kind')||'entity', record=n.data('record');
        const a=add(details,'a',kind==='news'?l.editNews:kind==='page'?l.editPage:l.edit,'sg-edit');
        a.href=kind==='news'?`?do=news&table=tl_news&act=edit&id=${record}`:kind==='page'?`?do=page&act=edit&id=${record}`:`?do=schema_manager&act=edit&id=${record}`;a.dataset.turbo='false';
      }
      (n.data('warnings')||[]).forEach(w=>add(details,'p',l[w]||w,'sg-status'));
      if(n.data('needsService'))add(details,'p',l.noService);
      if(n.data('identity'))add(details,'code',n.data('identity'));
      const homes=n.data('homes');if(homes.length){add(details,'h4',l.homes);const list=add(details,'ul','');homes.forEach(h=>add(list,'li',`${h.language} · ${l.page} #${h.page} · ${h.published?l.published:l.draft}${h.name?' · '+h.name:''}`));}
      if(n.degree()){add(details,'h4',l.relations);const ul=add(details,'ul','');n.connectedEdges().forEach(e=>{const outgoing=e.source().id()===n.id(), other=outgoing?e.target():e.source();const li=add(ul,'li','');const btn=add(li,'button',`${outgoing?'→':'←'} ${e.data('label')} · ${other.data('name')}`);btn.type='button';listen(btn,'click',()=>{focus(other);});});}
      cy.fit(n.closedNeighborhood(),50);
    }
    cy.on('tap','node',e=>focus(e.target));listen(select,'change',()=>{const n=cy.getElementById(select.value);if(n.length)focus(n);});
    function filter(){const term=q('[data-sg-search]').value.trim().toLocaleLowerCase();const only=q('[data-sg-isolated]').checked;const topics=q('[data-sg-topics]')?.checked;cy.elements().removeClass('dim focused');
      const found=cy.nodes().filter(n=>(!only || (n.degree()===0&&!n.data('missing')))&&(!topics || n.data('needsService'))&&(!term || `${n.data('name')} ${n.data('type')} ${n.data('detail')}`.toLocaleLowerCase().includes(term)));
      if(term||only||topics){cy.elements().difference(found).addClass('dim');found.addClass('focused');if(found.length)cy.fit(found,60);}
      else cy.fit(undefined,45);
    }
    listen(q('[data-sg-search]'),'input',filter);listen(q('[data-sg-isolated]'),'change',filter);listen(q('[data-sg-topics]'),'change',filter);
    listen(q('[data-sg-fit]'),'click',()=>cy.fit(undefined,45));
    listen(q('[data-sg-reset]'),'click',()=>{q('[data-sg-search]').value='';q('[data-sg-isolated]').checked=false;if(q('[data-sg-topics]'))q('[data-sg-topics]').checked=false;select.value='';filter();details.replaceChildren();add(details,'h3',l.select);add(details,'p',l.help);});
    const observer=new ResizeObserver(()=>{if(!cy.destroyed())cy.resize();});observer.observe(q('[data-sg-canvas]'));root.sgCleanup=()=>{events.abort();observer.disconnect();cy.stop();cy.destroy();root.sgInstance=null;};
  });
  document.addEventListener('turbo:load',boot);
  document.addEventListener('turbo:before-cache',()=>document.querySelectorAll('[data-schema-graph]').forEach(r=>r.sgCleanup?.()));
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
