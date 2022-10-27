const puppeteer = require('puppeteer');           // include lib
const fs = require('fs');

(async () => {                                    // declare function
  const browser = await puppeteer.launch();       // run browser
  const page = await browser.newPage();           // open new tab
  await page.goto('http://localhost:8000/template.php?screenshot=1');
  await page.setViewport({
    width: 1920,
    height: 1080,
    deviceScaleFactor: 3.5
  });

  await page.waitForSelector('#end-of-page');

  const items = await page.$$eval('.label', function (els) {
    return els.map(el => ({
      id: el.id,
      type: el.getAttribute('data-attendee-type')
    }))
  })

  for (const item of items) {
    const el = await page.$('#' + item.id);
    if (!fs.existsSync(item.type)) {
      fs.mkdirSync(item.type);
    }
    await el.screenshot({ path: item.type + '/' + item.id + '.png', omitBackground: true });
  }

  await browser.close();                          // close browser
})();
