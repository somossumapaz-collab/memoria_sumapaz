import asyncio
from playwright.async_api import async_playwright

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()
        
        # Connect to CDP
        client = await page.context.new_cdp_session(page)
        await client.send("Runtime.enable")
        
        exceptions = []
        def on_exception(event):
            exceptions.append(event)
            
        client.on("Runtime.exceptionThrown", on_exception)
        
        await page.goto("http://localhost:8080/pmapc.html")
        await page.wait_for_timeout(1000)
        
        print("\n--- CDP Exception Details ---")
        for exc in exceptions:
            details = exc.get("exceptionDetails", {})
            print(f"Message: {details.get('text')}")
            print(f"Line Number: {details.get('lineNumber')}")
            print(f"Column Number: {details.get('columnNumber')}")
            print(f"URL: {details.get('url')}")
            exception = details.get("exception", {})
            print(f"Exception Class: {exception.get('className')}")
            print(f"Exception Description: {exception.get('description')}")
            
        await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
