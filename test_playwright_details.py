import asyncio
from playwright.async_api import async_playwright

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()
        
        errors = []
        def handle_error(err):
            errors.append({
                "message": err.message,
                "stack": err.stack,
                "name": getattr(err, 'name', None),
                "repr": repr(err),
                "vars": vars(err) if hasattr(err, '__dict__') else None
            })
        
        page.on("pageerror", handle_error)
        
        try:
            await page.goto("http://localhost:8080/pmapc.html", timeout=10000)
            await page.wait_for_timeout(1000)
        except Exception as e:
            print("Goto error:", e)
            
        print("\n--- Detailed Errors ---")
        for e in errors:
            print("MESSAGE:", e["message"])
            print("STACK:", e["stack"])
            print("NAME:", e["name"])
            print("REPR:", e["repr"])
            print("VARS:", e["vars"])
            
        await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
