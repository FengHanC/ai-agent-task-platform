from playwright.sync_api import sync_playwright

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page(viewport={"width": 1400, "height": 900})

    # 1. Welcome 页
    page.goto('http://localhost:8000')
    page.wait_for_load_state('networkidle')
    page.screenshot(path='c:/Users/Administrator/Documents/ TraeSolo/ai-agent-task-platform/screenshots/01-welcome.png', full_page=True)

    # 2. Dashboard 页
    page.goto('http://localhost:8000/dashboard')
    page.wait_for_load_state('networkidle')
    page.screenshot(path='c:/Users/Administrator/Documents/ TraeSolo/ai-agent-task-platform/screenshots/02-dashboard.png', full_page=True)

    # 3. Agent 列表页
    page.goto('http://localhost:8000/agents')
    page.wait_for_load_state('networkidle')
    page.screenshot(path='c:/Users/Administrator/Documents/ TraeSolo/ai-agent-task-platform/screenshots/03-agents-list.png', full_page=True)

    # 4. Agent 创建页
    page.goto('http://localhost:8000/agents/create')
    page.wait_for_load_state('networkidle')
    page.screenshot(path='c:/Users/Administrator/Documents/ TraeSolo/ai-agent-task-platform/screenshots/04-agents-create.png', full_page=True)

    browser.close()
    print("All screenshots saved!")
