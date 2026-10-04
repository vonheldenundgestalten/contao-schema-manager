"""Run with Python + Playwright/Chromium. No server, credentials or API calls."""
from pathlib import Path
from playwright.sync_api import sync_playwright, expect

script = (Path(__file__).resolve().parents[1] / 'public/ai-helper.js').read_text()
html = '<main data-schema-ai><form data-sai-run data-total="16" data-remaining="16"><button type="button" disabled data-sai-start>Start</button><button type="button" data-sai-stop hidden>Stop</button><span data-sai-progress data-label="Processing"></span></form></main>'
with sync_playwright() as playwright:
    browser = playwright.chromium.launch()
    for scenario in ('fresh','restored','replaced_during_batch'):
        restored=scenario=='restored'
        page = browser.new_page()
        calls = []
        page.expose_function('recordBatch', lambda: calls.append(1))
        page.route('https://fixture.test/**', lambda route: route.fulfill(content_type='text/html', body=html))
        page.goto('https://fixture.test/')
        page.evaluate("""window.fetch=async()=>{
            await window.recordBatch();
            window.batchCount=(window.batchCount||0)+1;
            await new Promise(resolve=>setTimeout(resolve,150));
            return {ok:true,headers:new Headers({'content-type':'application/json'}),json:async()=>({done:window.batchCount===2,remaining:13})};
        }""")
        page.add_script_tag(content=script)
        if restored:
            page.evaluate("""const root=document.querySelector('[data-schema-ai]');
                root.dataset.ready='1';
                root.replaceWith(root.cloneNode(true));
                document.dispatchEvent(new Event('turbo:load'));
            """)
        page.locator('[data-sai-start]').click()
        expect(page.locator('[data-sai-progress]')).to_contain_text('0/16')
        if scenario=='replaced_during_batch':
            page.evaluate("""document.dispatchEvent(new Event('turbo:before-cache'));
                const root=document.querySelector('[data-schema-ai]');root.replaceWith(root.cloneNode(true));
                document.dispatchEvent(new Event('turbo:load'));
            """)
            expect(page.locator('[data-sai-start]')).to_be_disabled()
            expect(page.locator('[data-sai-progress]')).to_contain_text('0/16')
        page.wait_for_function('!window.schemaAiLoaded')
        assert len(calls)==2, f'Expected two batches, got {len(calls)} (restored={restored})'
        page.close()
    browser.close()
print('PASS: fresh, restored and mid-request replaced backend DOM all run multiple batches and reload on completion.')
