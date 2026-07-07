import asyncio
from playwright.async_api import async_playwright

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()
        
        def handle_console(msg):
            print(f"Console [{msg.type}] message: {msg.text}")
            print(f"  Location: {msg.location}")
            
        page.on("console", handle_console)
        page.on("pageerror", lambda err: print(f"PageError: {err.message}"))
        
        await page.goto("http://localhost:8080/pmapc.html")
        await page.wait_for_timeout(1000)
        await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
