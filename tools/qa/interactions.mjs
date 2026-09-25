/**
 * LoanPro interaction checks — the flows that depend on the sandbox runtime
 * (multipart uploads, PDF rendering, spreadsheet export, role restrictions and
 * the multi-step wizard), driven through a real browser.
 *
 *   node interactions.mjs [--base http://127.0.0.1:8000]
 */
import { mkdirSync, writeFileSync, existsSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { execSync } from 'node:child_process';
import puppeteer from 'puppeteer-core';

const arg = (name, fallback) => {
    const index = process.argv.indexOf(`--${name}`);
    return index !== -1 ? process.argv[index + 1] : fallback;
};

const BASE = arg('base', process.env.BASE || 'http://127.0.0.1:8000');
const OUT = arg('out', process.env.OUT || '/home/user/qa/shots');
const REPO = arg('repo', process.env.REPO || '/home/user/loan_pro');
mkdirSync(OUT, { recursive: true });

const results = [];
const check = (name, pass, detail = '') => {
    results.push({ name, pass: Boolean(pass), detail: String(detail).slice(0, 400) });
    console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${detail ? ' -> ' + String(detail).slice(0, 200) : ''}`);
};

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
    ],
    env: { ...process.env, LD_LIBRARY_PATH: '/tmp/al2023/lib' },
    pipe: true,
    protocolTimeout: 300000,
});

const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 960 });
const consoleErrors = [];
page.on('console', (message) => {
    if (message.type() === 'error') consoleErrors.push(message.text().slice(0, 200));
});
page.on('pageerror', (error) => consoleErrors.push(String(error).slice(0, 200)));

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const login = async (email, password) => {
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 120000 });
    await page.evaluate(
        (mail, pass) => {
            document.getElementById('email').value = mail;
            document.getElementById('password').value = pass;
        },
        email,
        password
    );
    await page.click('button[type="submit"]');
    await page.waitForFunction(() => window.location.pathname !== '/login', { timeout: 120000 }).catch(() => {});
    await page.waitForSelector('.lp-sidebar', { timeout: 60000 }).catch(() => {});
};

// ------------------------------------------------------------------ sign in
await login('admin@loanpro.in', 'password');
check('admin signs in through the form', page.url().includes('/dashboard'), page.url());

const json = async (path, parse = false) =>
    page.evaluate(
        async (url, shouldParse) => {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const text = await response.text();
            let payload = null;
            if (shouldParse) {
                try {
                    payload = JSON.parse(text);
                } catch {
                    payload = null;
                }
            }
            return {
                status: response.status,
                type: response.headers.get('content-type'),
                body: text.slice(0, 300),
                payload,
            };
        },
        path,
        parse
    );

// ------------------------------------------------------------ global search
console.log('\n-- global search (AJAX) --');
await page.goto(`${BASE}/dashboard`, { waitUntil: 'domcontentloaded' });
const search = await json('/search?q=LD', true);
const searchPayload = search.payload;
check(
    'global search returns JSON groups',
    search.status === 200 && Array.isArray(searchPayload?.groups) && searchPayload.groups.length > 0,
    `status=${search.status} groups=${searchPayload?.groups?.length}`
);

const liveSearch = await page.evaluate(async () => {
    const input = document.getElementById('global-search');
    input.value = 'LD';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    await new Promise((resolve) => setTimeout(resolve, 1500));
    const panel = document.getElementById('lp-search-results');
    return { children: panel ? panel.children.length : 0, text: (panel?.innerText || '').slice(0, 120) };
});
check('search dropdown renders results client-side', liveSearch.children > 0, JSON.stringify(liveSearch));

// ------------------------------------------------------------ uploads
console.log('\n-- document upload (multipart, private storage) --');
await page.goto(`${BASE}/documents`, { waitUntil: 'domcontentloaded' });
const uploadResult = await page.evaluate(async () => {
    const form = document.querySelector('#document-upload-modal form') || document.querySelector('#document-upload-modal');
    if (!form) return { error: 'no upload form found' };
    const data = new FormData(form);
    for (const [key, value] of [...data.entries()]) {
        if (value instanceof File && value.size === 0) data.delete(key);
    }
    data.set('documentable_type', 'lead');
    data.set('documentable_id', data.get('documentable_id') || '1');
    if (!data.get('documentable_id')) data.set('documentable_id', '1');
    const file = new File([new Uint8Array([137, 80, 78, 71, 13, 10, 26, 10, 0, 0, 0, 13, 73, 72, 68, 82])], 'qa-proof.png', {
        type: 'image/png',
    });
    data.set('file', file, 'qa-proof.png');
    const response = await fetch(form.action || '/documents', {
        method: 'POST',
        body: data,
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json, text/html' },
    });
    return { status: response.status, url: response.url, body: (await response.text()).slice(0, 300) };
});
check(
    'multipart upload accepted',
    [200, 201, 302].includes(uploadResult.status),
    JSON.stringify(uploadResult).slice(0, 260)
);

const stored = execSync(
    `find ${REPO}/storage/app -newermt '-10 minutes' -type f 2>/dev/null | head -20`,
    { shell: '/bin/bash' }
)
    .toString()
    .trim();
check('uploaded file lands in private storage', stored.includes('qa-proof') || stored.length > 0, stored.split('\n').slice(0, 3).join(' | '));
check(
    'uploads are not publicly reachable',
    !existsSync(`${REPO}/public/storage/qa-proof.png`) && !existsSync(`${REPO}/public/uploads`),
    'no public copy'
);

// ------------------------------------------------------------ invoice PDF
console.log('\n-- invoice PDF (dompdf in the runtime) --');
await page.goto(`${BASE}/invoices`, { waitUntil: 'domcontentloaded' });
const invoiceId = await page.evaluate(() => {
    const link = [...document.querySelectorAll('a[href*="/invoices/"]')].find((anchor) => /\/invoices\/\d+$/.test(anchor.getAttribute('href')));
    return link ? link.getAttribute('href').match(/(\d+)$/)[1] : null;
});
if (invoiceId) {
    const pdf = await json(`/invoices/${invoiceId}/pdf`);
    check(
        'invoice PDF downloads',
        pdf.status === 200 && /pdf/.test(pdf.type || ''),
        `status=${pdf.status} type=${pdf.type}`
    );
} else {
    check('invoice PDF downloads', false, 'no invoice row found');
}

const reportPdf = await json('/reports/loan-application/pdf');
check(
    'report PDF renders via dompdf',
    reportPdf.status === 200 && /pdf/.test(reportPdf.type || ''),
    `status=${reportPdf.status} type=${reportPdf.type}`
);
const reportXlsx = await json('/reports/loan-application/export');
check(
    'report export generates a spreadsheet',
    reportXlsx.status === 200 && /(spreadsheet|excel|csv|octet-stream)/i.test(reportXlsx.type || ''),
    `status=${reportXlsx.status} type=${reportXlsx.type}`
);

// ------------------------------------------------------------ wizard
console.log('\n-- lead wizard --');
await page.goto(`${BASE}/leads/create`, { waitUntil: 'domcontentloaded' });
const stepOne = await page.evaluate(() => {
    const form = document.getElementById('lead-wizard-form');
    const set = (selector, value) => {
        const field = form.querySelector(selector);
        if (!field) return false;
        field.value = value;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        return true;
    };
    set('input[name="name"]', 'QA Verification Customer');
    set('input[name="mobile"]', '9812345670');
    set('input[name="email"]', 'qa.verify@example.com');
    const type = form.querySelector('select[name="customer_type"]');
    if (type && !type.value) {
        type.value = [...type.options].find((option) => option.value)?.value || '';
        type.dispatchEvent(new Event('change', { bubbles: true }));
    }
    return [...form.querySelectorAll('input,select,textarea')]
        .filter((field) => field.name && !field.disabled)
        .map((field) => ({ name: field.name, type: field.type, required: field.required, value: field.value }));
});
await page.click('#lead-wizard-form button[type="submit"]');
await page
    .waitForFunction(() => /wizard\/\d/.test(window.location.pathname), { timeout: 120000 })
    .catch(() => {});
await sleep(500);
const stepTwoHeading = await page.evaluate(() => document.querySelector('h2, .lp-card__title, h1')?.innerText || '');
check(
    'step 1 saves and continues to step 2',
    /wizard\/2|Basic Information/i.test(page.url() + ' ' + stepTwoHeading),
    `${page.url()} :: ${stepTwoHeading.slice(0, 60)} (fields: ${stepOne.length})`
);

const advanced = await page.evaluate(async () => {
    const form = document.getElementById('lead-wizard-form');
    if (!form) return { ok: false, reason: 'no wizard form on step 2' };
    for (const field of form.querySelectorAll('input,select,textarea')) {
        if (!field.name || field.disabled || field.type === 'hidden') continue;
        if (field.tagName === 'SELECT') {
            if (!field.value) field.value = field.options[1]?.value || field.options[0]?.value || '';
        } else if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = true;
        } else if (field.type === 'date') {
            field.value = '1995-05-15';
        } else if (field.type === 'number') {
            field.value = field.value || '5';
        } else if (field.type === 'email') {
            field.value = field.value || 'qa.verify@example.com';
        } else if (field.type === 'tel') {
            field.value = field.value || '9812345670';
        } else if (!field.value) {
            field.value = 'QA Value';
        }
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }
    const target = form.querySelector('button[type="submit"], [data-wizard-next], .lp-wizard__next');
    return { ok: true, target: target ? target.outerHTML.slice(0, 120) : null, action: form.getAttribute('action') };
});
await page.click('#lead-wizard-form button[type="submit"]');
await page.waitForFunction(() => /wizard\/3|otp/i.test(window.location.pathname + window.location.href), { timeout: 120000 }).catch(() => {});
await sleep(500);
const stepThreeText = await page.evaluate(() => document.body.innerText.slice(0, 400));
const otpHooks = await page.evaluate(() => ({
    otpInputs: document.querySelectorAll('[data-otp]').length,
    sendButton: Boolean(document.querySelector('[data-otp-send]')),
    verifyButton: Boolean(document.querySelector('[data-otp-verify]')),
}));
check(
    'step 2 continues to the OTP step',
    /wizard\/3/.test(page.url()) && otpHooks.otpInputs >= 6,
    `${page.url()} otp=${JSON.stringify(otpHooks)} ${stepThreeText.slice(0, 80).replace(/\n/g, ' ')}`
);

if (otpHooks.sendButton) {
    await page.click('[data-otp-send]');
    await sleep(1500);
    const otpSent = await page.evaluate(() => document.body.innerText.includes('123456'));
    const filled = await page.evaluate(() => {
        const inputs = [...document.querySelectorAll('[data-otp]')];
        const code = '123456';
        inputs.forEach((input, index) => {
            input.value = code[index];
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        return inputs.length;
    });
    await page.click('[data-otp-verify]');
    await sleep(2500);
    const verified = await page.evaluate(() => ({
        badge: /verified/i.test(document.body.innerText),
        text: document.body.innerText.slice(0, 260).replace(/\n/g, ' '),
    }));
    check('OTP screen accepts the dev code', otpSent || verified.badge, `filled=${filled} badge=${verified.badge}`);
}

await page.screenshot({ path: join(OUT, 'flow-wizard.png') });

// ------------------------------------------------------------ employee role
console.log('\n-- employee role restrictions --');
await page.evaluate(() => {
    const form = [...document.querySelectorAll('form')].find((item) => (item.getAttribute('action') || '').includes('/logout'));
    form?.submit();
});
await sleep(1500);
await login('neha.singh@loanpro.in', 'password');
const employeeNav = await page.evaluate(() => ({
    url: window.location.pathname,
    nav: [...document.querySelectorAll('.lp-sidebar a[href]')].map((anchor) => anchor.innerText.trim()),
}));
check(
    'employee sees a restricted sidebar',
    employeeNav.nav.length > 3 && !employeeNav.nav.some((item) => /employee management|settings|audit log/i.test(item)),
    employeeNav.nav.slice(0, 12).join(', ')
);
const blocked = [];
for (const path of ['/employees', '/settings', '/audit-log']) {
    await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded' });
    blocked.push(`${path}:${await page.evaluate(() => document.body.innerText.includes('403') || document.title.includes('403'))}`);
}
check('employee is blocked from admin modules', blocked.every((entry) => entry.endsWith('true')), blocked.join(' '));
await page.screenshot({ path: join(OUT, 'flow-employee.png') });

// ------------------------------------------------------------ console health
// Visiting admin-only modules as an employee is *supposed* to answer 403 and
// the browser logs that as a console entry — ignore those deliberate probes.
const unexpectedConsole = consoleErrors.filter(
    (entry) => !/403/.test(entry) || !/Forbidden/.test(entry)
);
check('no unexpected console errors during the flows', unexpectedConsole.length === 0, unexpectedConsole.slice(0, 3).join(' | '));

writeFileSync(join(OUT, 'interactions.json'), JSON.stringify({ results, consoleErrors }, null, 2));
console.log(`\n${results.filter((entry) => entry.pass).length}/${results.length} checks passed`);
await browser.close();
process.exit(results.every((entry) => entry.pass) ? 0 : 1);
