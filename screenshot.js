const puppeteer = require('puppeteer');           // include lib
(async () => {                                    // declare function
  const browser = await puppeteer.launch();       // run browser
  const page = await browser.newPage();           // open new tab
  await page.goto('http://localhost:8000/template.php?screenshot=1');

  await page.waitForSelector('#end-of-page');

  const items = await page.$$eval('.label', function (els) {
    return els.map(el => ({
      id: el.id,
      type: el.getAttribute('data-attendee-type')
    }))
  })

  for (const item of items) {
    const el = await page.$('#' + item.id);
    await el.screenshot({ path: item.type + '/' + item.id + '.png' });
  }

  await browser.close();                          // close browser
})();
