from playwright.sync_api import sync_playwright

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page(viewport={"width": 1400, "height": 900})
    page.goto('http://localhost:8000/agents')
    page.wait_for_load_state('networkidle')
    page.screenshot(path='c:/Users/Administrator/Documents/ TraeSolo/ai-agent-task-platform/screenshots/05-agents-list-fixed.png', full_page=True)
    browser.close()
    print("Done!")
