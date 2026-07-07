import asyncio
from playwright.async_api import async_playwright

async def main():
    async with async_playwright() as p:
        # Launch browser
        try:
            browser = await p.chromium.launch()
        except Exception as e:
            print(f"Error launching chromium: {e}")
            print("Trying to install chromium...")
            import subprocess
            subprocess.run(["playwright", "install", "chromium"])
            browser = await p.chromium.launch()
            
        page = await browser.new_page()
        
        # Listen for console events
        errors = []
        page.on("pageerror", lambda err: errors.append(f"Page Error: {err.message}\n{err.stack}"))
        page.on("console", lambda msg: errors.append(f"Console {msg.type}: {msg.text}"))
        
        # Navigate to the page
        print("Navigating to http://localhost:8080/pmapc.html...")
        try:
            await page.goto("http://localhost:8080/pmapc.html", timeout=10000)
            await page.wait_for_timeout(2000) # Wait 2 seconds for any async load
        except Exception as e:
            print(f"Navigation error: {e}")
            
        # Get placeholders
        placeholder = await page.eval_on_selector("#select-producer-input", "el => el.placeholder")
        print(f"Selector input placeholder: {placeholder}")
        
        print("\n--- Console and Page Errors ---")
        for err in errors:
            print(err)
            
        await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
