# Offline checks: python3 tests/browser-relationship-map.py (Playwright + Chromium required).
from pathlib import Path
from playwright.sync_api import sync_playwright, expect
root=Path(__file__).resolve().parents[1]
labels={k:k for k in ['entities','relations','isolated','groups','missing','published','draft','unconnected','edit','homes','page','select','help']}
nodes=[{'data':dict(id='entity-'+str(i),record=i,name=name,type=kind,detail=detail,identity='',published=published,missing=missing,homes=[])} for i,name,kind,detail,published,missing in [
 (1,'Example company','Organization','',True,False),(2,'Example company','LocalBusiness','Main Street 3, 70173 Stuttgart',True,False),
 (3,'Unconnected draft','Person','',False,False),(4,'#4','Missing','',False,True),(5,'</script><script>window.attacked=true</script>','Service','',True,False)]]
edges=[{'data':dict(id='edge-'+str(i),source=source,target=target,label=label)} for i,source,target,label in [(1,'entity-2','entity-1','parentOrganization'),(2,'entity-1','entity-2','location'),(3,'entity-1','entity-4','memberOf')]]
html='''<div data-schema-graph><div class="sg-toolbar"><select data-sg-language><option value="*">All</option></select><select data-sg-layout><option value="relationships">Relationships</option><option value="type">Type</option></select><input type="search" data-sg-search><select data-sg-select><option value="">Select</option></select><input type="checkbox" data-sg-isolated><input type="checkbox" data-sg-topics><button data-sg-fit>Fit</button><button data-sg-reset>Reset</button></div><p data-sg-summary></p><div class="sg-workspace"><div data-sg-canvas class="sg-canvas"></div><aside data-sg-details class="sg-details"></aside></div><script type="application/json" data-sg-data></script></div>'''
with sync_playwright() as p:
 browser=p.chromium.launch()
 page=browser.new_page(viewport={'width':1440,'height':1000})
 errors=[]; page.on('pageerror',lambda e:errors.append(e.stack))
 page.set_content(html)
 page.add_style_tag(path=str(root/'public/relationship-map.css'))
 page.locator('[data-sg-data]').evaluate('(el,payload)=>el.textContent=JSON.stringify(payload)',{'elements':{'nodes':nodes,'edges':edges},'labels':labels})
 for file in ['vendor/cytoscape/cytoscape.min.js','vendor/layout-base/layout-base.js','vendor/cose-base/cose-base.js','vendor/cytoscape-fcose/cytoscape-fcose.js','relationship-map.js']:
  page.add_script_tag(path=str(root/'public'/file))
 cy="document.querySelector('[data-schema-graph]').sgInstance"
 assert page.evaluate(cy+'.nodes().length')==5
 assert page.evaluate(cy+".nodes('.isolated').length")==2
 assert page.evaluate(cy+".getElementById('entity-3').style('border-style')")=='dashed'
 page.locator('[data-sg-select]').select_option('entity-2')
 expect(page.locator('[data-sg-details]')).to_contain_text('70173 Stuttgart')
 expect(page.locator('[data-sg-details]')).to_contain_text('parentOrganization')
 page.locator('[data-sg-select]').select_option('entity-4')
 assert page.locator('[data-sg-details] a').count()==0
 page.locator('[data-sg-select]').select_option('entity-5')
 assert not page.evaluate('Boolean(window.attacked)')
 page.locator('[data-sg-isolated]').check()
 assert page.evaluate(cy+".nodes('.focused').length")==2
 page.locator('[data-sg-reset]').click()
 page.locator('[data-sg-search]').fill('Stuttgart')
 assert page.evaluate(cy+".nodes('.focused').length")==1
 page.set_viewport_size({'width':600,'height':900})
 assert page.locator('[data-sg-canvas]').bounding_box()['width']<=600
 page.evaluate("document.dispatchEvent(new Event('turbo:before-cache'))")
 page.locator('[data-sg-data]').evaluate('(el,payload)=>el.textContent=JSON.stringify(payload)',{'elements':{'nodes':[],'edges':[]},'labels':labels})
 page.evaluate("document.dispatchEvent(new Event('turbo:load'))")
 assert page.evaluate(cy+'.nodes().length')==0
 assert not errors,errors
 import subprocess,json
 output=subprocess.check_output(['php','-r',"require 'tests/content-relationship-map.php'; echo json_encode($map);"],cwd=root,text=True)
 content=json.loads(output.split('\n',1)[1])
 page.evaluate("document.dispatchEvent(new Event('turbo:before-cache'))")
 page.locator('[data-sg-data]').evaluate('(el,payload)=>el.textContent=JSON.stringify(payload)',{'elements':content,'labels':labels})
 page.evaluate("document.dispatchEvent(new Event('turbo:load'))")
 page.locator('[data-sg-language]').select_option('*')
 page.locator('[data-sg-select]').select_option('news-31')
 assert 'do=news' in page.locator('[data-sg-details] a').get_attribute('href')
 expect(page.locator('[data-sg-details]')).to_contain_text('author')
 page.locator('[data-sg-select]').select_option('page-11')
 assert 'do=page' in page.locator('[data-sg-details] a').get_attribute('href')
 page.locator('[data-sg-reset]').click()
 page.locator('[data-sg-topics]').check()
 assert page.evaluate(cy+".nodes('.focused').length")==3
 page.locator('[data-sg-reset]').click()
 assert not page.locator('[data-sg-topics]').is_checked()
 assert not errors,errors

 page.locator('[data-sg-language]').select_option('en')
 assert page.evaluate(cy+".getElementById('news-31').length")==0
 assert page.evaluate(cy+".getElementById('news-32').length")==1
 assert page.evaluate(cy+".getElementById('entity-3').data('name')")=='Service'
 page.locator('[data-sg-language]').select_option('de')
 assert page.evaluate(cy+".getElementById('entity-3').data('name')")=='Leistung'
 assert page.locator('[data-sg-select] option[value="news-32"]').count()==0
 overlap="""()=>{const nodes=document.querySelector('[data-schema-graph]').sgInstance.nodes();for(let i=0;i<nodes.length;i++)for(let j=i+1;j<nodes.length;j++){const a=nodes[i].boundingBox(),b=nodes[j].boundingBox();if(a.x1<b.x2&&a.x2>b.x1&&a.y1<b.y2&&a.y2>b.y1)return true;}return false;}"""
 for lang in ['*','en','de']:
  page.locator('[data-sg-language]').select_option(lang)
  for layout in ['type','relationships']:
   page.locator('[data-sg-layout]').select_option(layout)
   assert not page.evaluate(overlap),(lang,layout)
 assert page.locator('[data-sg-search]').bounding_box()['height']==page.locator('[data-sg-select]').bounding_box()['height']
 assert not errors,errors
 browser.close()
print('PASS: isolated, draft and missing nodes, office labels, selection, search, hostile labels, mobile width and empty graph/Turbo reinitialization.')
