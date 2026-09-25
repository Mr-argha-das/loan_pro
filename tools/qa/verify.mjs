/**
 * LoanPro browser verification harness.
 *
 *   node verify.mjs [--base http://127.0.0.1:8000] [--out shots] [--email ...]
 *
 * Signs in through the real login form, then walks every module and records
 * console errors, failed requests, screenshots and a handful of computed-style
 * assertions that prove the stylesheet actually applied (not just HTTP 200).
 */
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import puppeteer from 'puppeteer-core';

const arg = (name, fallback) => {
    const index = process.argv.indexOf(`--${name}`);
    return index !== -1 ? process.argv[index + 1] : fallback;
};

const BASE = arg('base', process.env.BASE || 'http://127.0.0.1:8000');
const OUT = arg('out', process.env.OUT || '/home/user/qa/shots');
const EMAIL = arg('email', process.env.QA_EMAIL || 'admin@loanpro.in');
const PASSWORD = arg('password', process.env.QA_PASSWORD || 'password');

mkdirSync(OUT, { recursive: true });

const PAGES = [
    ['dashboard', '/dashboard'],
    ['leads', '/leads'],
    ['lead-create', '/leads/create'],
    ['customers', '/customers'],
    ['applications', '/applications'],
    ['insurance', '/insurance'],
    ['products', '/products'],
    ['product-loans', '/products/loans'],
    ['lenders', '/lenders'],
    ['invoices', '/invoices'],
    ['payments', '/payments'],
    ['disbursements', '/disbursements'],
    ['notifications', '/notifications'],
    ['attendance', '/attendance'],
    ['reports', '/reports'],
    ['employees', '/employees'],
    ['masters', '/masters'],
    ['settings', '/settings'],
    ['profile', '/profile'],
    ['audit-log', '/audit-log'],
];

const results = { console: [], failedRequests: [], pages: [], assertions: [] };

const browser = await puppeteer.launch({
    executablePath: '/tmp/chromium',
    headless: true,
    args: [
        '--no-sandbox',
        '--no-zygote',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--use-gl=swiftshader',
        '--enable-unsafe-swiftshader',
        '--font-render-hinting=none',
        '--hide-scrollbars',
    ],
    env: { ...process.env, LD_LIBRARY_PATH: '/tmp/al2023/lib' },
    pipe: true,
    protocolTimeout: 300000,
});

const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 960, deviceScaleFactor: 1 });

page.on('console', (message) => {
    if (['error', 'warning'].includes(message.type())) {
        results.console.push(`[${message.type()}] ${message.text()}`.slice(0, 300));
    }
});
page.on('pageerror', (error) => results.console.push(`[pageerror] ${String(error).slice(0, 300)}`));
page.on('requestfailed', (request) => {
    results.failedRequests.push(`${request.method()} ${request.url()} (${request.failure()?.errorText})`);
});
page.on('response', (response) => {
    if (response.status() >= 400) {
        results.failedRequests.push(`${response.status()} ${response.url()}`);
    }
});

const assert = (name, condition, detail = '') => {
    results.assertions.push({ name, pass: Boolean(condition), detail: String(detail).slice(0, 300) });
    console.log(`${condition ? 'PASS' : 'FAIL'}  ${name}${detail ? ' -> ' + String(detail).slice(0, 160) : ''}`);
};

const shot = async (name, fullPage = false) => {
    await page.screenshot({ path: join(OUT, `${name}.png`), fullPage });
};

const goto = async (path, { wait = 'networkidle2', timeout = 120000 } = {}) => {
    const response = await page.goto(`${BASE}${path}`, { waitUntil: wait, timeout });
    return response;
};

// ---------------------------------------------------------------- login page
console.log('\n== sign-in ==');
const loginResponse = await goto('/login');
await shot('01-login');

assert('login page returns 200', loginResponse.status() === 200, loginResponse.status());
assert(
    'login page shows the brand',
    (await page.$eval('body', (body) => body.innerText)).includes('LoanPro')
);
const loginStyle = await page.evaluate(() => {
    const card = document.querySelector('.card, .lp-auth__card, form');
    return {
        bodyBackground: getComputedStyle(document.body).backgroundColor,
        stylesheets: document.styleSheets.length,
        cardBackground: card ? getComputedStyle(card).backgroundColor : null,
        fontFamily: getComputedStyle(document.body).fontFamily,
    };
});
assert('stylesheet loaded on login page', loginStyle.stylesheets >= 1, JSON.stringify(loginStyle));
const authPanel = await page.evaluate(() => {
    const card = document.querySelector('.lp-card');
    const button = document.querySelector('button[type="submit"]');
    const body = getComputedStyle(document.body);
    return {
        hasCard: Boolean(card),
        gradient: body.backgroundImage.includes('gradient'),
        cardBackground: card ? getComputedStyle(card).backgroundColor : null,
        buttonBackground: button ? getComputedStyle(button).backgroundColor : null,
    };
});
assert('auth screen painted (brand gradient + card)', authPanel.hasCard && authPanel.gradient, JSON.stringify(authPanel));
assert(
    'primary button uses the brand blue',
    authPanel.buttonBackground === 'rgb(22, 119, 255)',
    authPanel.buttonBackground
);
assert('inter font family applied', /Inter|Plus Jakarta/.test(loginStyle.fontFamily), loginStyle.fontFamily);

// sign in through the form (real user flow, not a cookie shortcut)
await page.evaluate(
    (email, password) => {
        const emailField = document.getElementById('email');
        const passwordField = document.getElementById('password');
        emailField.value = email;
        passwordField.value = password;
        emailField.dispatchEvent(new Event('input', { bubbles: true }));
        passwordField.dispatchEvent(new Event('input', { bubbles: true }));
    },
    EMAIL,
    PASSWORD
);
await page.click('button[type="submit"]');
await page
    .waitForFunction(() => window.location.pathname !== '/login', { timeout: 180000 })
    .catch(() => {});
await page.waitForSelector('.lp-sidebar', { timeout: 60000 }).catch(() => {});

assert('signed in and landed on the dashboard', page.url().includes('/dashboard'), page.url());
await shot('02-dashboard');
await shot('02-dashboard-full', true);

const dashboard = await page.evaluate(() => {
    const sidebar = document.querySelector('.lp-sidebar');
    const navLinks = document.querySelectorAll('.lp-sidebar a[href], .lp-nav a[href]');
    const statCards = document.querySelectorAll('.lp-stat');
    const canvases = document.querySelectorAll('canvas');
    return {
        sidebarBackground: sidebar ? getComputedStyle(sidebar).backgroundColor : null,
        sidebarVisible: sidebar ? sidebar.getBoundingClientRect().width : 0,
        navLinks: navLinks.length,
        statCards: statCards.length,
        canvases: canvases.length,
        chartIds: [...document.querySelectorAll('canvas')].map((canvas) => canvas.id),
        recentTable: Boolean(document.querySelector('table')),
        kpiLabels: [...document.querySelectorAll('.lp-stat__label')].map((el) => el.innerText.trim()),
        title: document.title,
        bodyText: document.body.innerText.slice(0, 300),
    };
});
assert('sidebar rendered with the navy surface', dashboard.sidebarVisible > 200, dashboard.sidebarVisible);
assert('dashboard KPI cards rendered', dashboard.statCards >= 8, dashboard.statCards);
assert(
    'dashboard chart set rendered',
    ['chart-funnel', 'chart-monthly', 'chart-category', 'chart-status', 'chart-disbursement'].every((id) =>
        dashboard.chartIds.includes(id)
    ),
    dashboard.chartIds.join(',')
);
assert('dashboard recent-records table rendered', dashboard.recentTable);
assert(
    'dashboard KPI set rendered',
    ['Total Leads', 'New Leads', 'Active Applications', 'Approved', 'Pending', 'Disbursed Loans', 'Total Disbursement', 'Pending Payments'].every(
        (label) => dashboard.kpiLabels.some((value) => value.toLowerCase().includes(label.toLowerCase()))
    ),
    dashboard.kpiLabels.join(' | ')
);
assert('sidebar navigation populated', dashboard.navLinks >= 15, dashboard.navLinks);

// ------------------------------------------------------------ module walk
console.log('\n== module walk ==');
for (const [name, path] of PAGES) {
    const started = Date.now();
    let status = 0;
    let error = null;
    const consoleIndex = results.console.length;
    try {
        const response = await goto(path, { wait: 'domcontentloaded' });
        status = response.status();
        await page
            .waitForFunction(() => document.querySelector('.lp-page, .lp-shell, main, form'), { timeout: 30000 })
            .catch(() => {});
        await new Promise((resolve) => setTimeout(resolve, 400));
    } catch (exception) {
        error = exception.message;
    }
    const info = await page
        .evaluate(() => {
            const heading = document.querySelector('h1, .lp-page__title');
            const shell = document.querySelector('.lp-shell');
            return {
                title: document.title,
                heading: heading ? heading.innerText.trim() : null,
                shell: Boolean(shell),
                sidebar: Boolean(document.querySelector('.lp-sidebar')),
                navLinks: document.querySelectorAll('.lp-sidebar a[href]').length,
                authenticated: !/Welcome back/.test(heading ? heading.innerText : ''),
                textLength: document.body.innerText.trim().length,
                stylesheets: document.styleSheets.length,
            };
        })
        .catch(() => ({}));

    results.pages.push({ name, path, status, ms: Date.now() - started, error, ...info, console: results.console.slice(consoleIndex) });
    console.log(
        `${status >= 200 && status < 400 ? 'OK  ' : 'ERR '} ${path.padEnd(20)} ${status} ${info.heading || ''} (${Date.now() - started}ms)`
    );
    await shot(`page-${name}`);
}

// ------------------------------------------------------------ assertions
console.log('\n== assertions ==');
const ok = results.pages.filter((entry) => entry.status >= 200 && entry.status < 400 && !entry.error);
assert('every audited page renders', ok.length === results.pages.length, `${ok.length}/${results.pages.length}`);
const authenticated = results.pages.filter((entry) => entry.authenticated && entry.sidebar && entry.navLinks >= 10);
assert(
    'every page rendered inside the authenticated shell',
    authenticated.length === results.pages.length,
    authenticated.map((entry) => entry.name).length + '/' + results.pages.length
);
assert(
    'no page rendered unstyled',
    results.pages.every((entry) => (entry.stylesheets ?? 0) >= 1),
    results.pages.filter((entry) => (entry.stylesheets ?? 0) < 1).map((entry) => entry.name).join(',')
);
assert('no console errors', results.console.length === 0, results.console.slice(0, 3).join(' | '));
assert('no failed requests', results.failedRequests.length === 0, results.failedRequests.slice(0, 3).join(' | '));

writeFileSync(join(OUT, 'results.json'), JSON.stringify(results, null, 2));
console.log(`\nscreenshots: ${OUT}`);
console.log(`pages: ${ok.length}/${results.pages.length} | console: ${results.console.length} | failed requests: ${results.failedRequests.length}`);

await browser.close();
process.exit(results.assertions.every((entry) => entry.pass) ? 0 : 1);
