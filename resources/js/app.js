/**
 * LoanPro front end.
 *
 * Bootstrap 5 for layout/interaction, Chart.js for dashboards, and a small
 * set of helpers (AJAX table refresh, toasts, OTP, uploads, wizard).
 */

import * as bootstrap from 'bootstrap';
import Chart from 'chart.js/auto';

window.bootstrap = bootstrap;
window.Chart = Chart;

/* ------------------------------------------------------------------ csrf */

const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

/* --------------------------------------------------------------- helpers */

window.LoanPro = {
    /**
     * Fetch wrapper that always sends the CSRF token and JSON headers.
     */
    async request(url, options = {}) {
        const config = {
            method: options.method ?? 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                ...(options.body instanceof FormData
                    ? {}
                    : { 'Content-Type': 'application/json' }),
                ...(options.headers ?? {}),
            },
            credentials: 'same-origin',
            body: options.body instanceof FormData
                ? options.body
                : options.body
                    ? JSON.stringify(options.body)
                    : undefined,
        };

        const response = await fetch(url, config);
        const contentType = response.headers.get('content-type') ?? '';
        const payload = contentType.includes('application/json')
            ? await response.json()
            : { success: response.ok, message: await response.text() };

        if (!response.ok) {
            throw Object.assign(new Error(payload.message ?? 'Request failed'), { payload, status: response.status });
        }

        return payload;
    },

    toast(message, type = 'success', title = null) {
        const stack = document.getElementById('lp-toast-stack');

        if (!stack) {
            return;
        }

        const icons = {
            success: 'bi-check-circle-fill',
            danger: 'bi-exclamation-octagon-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill',
        };

        const el = document.createElement('div');
        el.className = `lp-toast lp-toast--${type}`;
        el.innerHTML = `
            <i class="bi ${icons[type] ?? icons.info}"></i>
            <div class="flex-grow-1">
                ${title ? `<div class="fw-semibold">${title}</div>` : ''}
                <div class="text-muted small">${message}</div>
            </div>
            <button type="button" class="btn-close btn-close-sm ms-2" aria-label="Close"></button>`;

        el.querySelector('.btn-close').addEventListener('click', () => el.remove());
        stack.appendChild(el);
        setTimeout(() => el.remove(), 6000);
    },

    confirm(options = {}) {
        const modalEl = document.getElementById('lp-confirm-modal');

        if (!modalEl) {
            return Promise.resolve(window.confirm(options.message ?? 'Are you sure?'));
        }

        return new Promise((resolve) => {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            modalEl.querySelector('[data-confirm-title]').textContent = options.title ?? 'Please confirm';
            modalEl.querySelector('[data-confirm-message]').textContent = options.message ?? 'Are you sure you want to continue?';
            const confirmBtn = modalEl.querySelector('[data-confirm-button]');
            confirmBtn.textContent = options.confirmText ?? 'Confirm';
            confirmBtn.className = `btn btn-${options.variant ?? 'danger'}`;

            const handler = () => {
                confirmBtn.removeEventListener('click', handler);
                modal.hide();
                resolve(true);
            };

            confirmBtn.addEventListener('click', handler);
            modalEl.addEventListener('hidden.bs.modal', () => resolve(false), { once: true });
            modal.show();
        });
    },

    /** Serialises a form into a plain object (files excluded). */
    formData(form) {
        return Object.fromEntries(new FormData(form).entries());
    },

    /** Replaces the contents of a table container by hitting a listing endpoint. */
    async reloadTable(url, containerSelector, params = {}) {
        const container = document.querySelector(containerSelector);

        if (!container) {
            return;
        }

        container.classList.add('opacity-50');
        const query = new URLSearchParams({ ...params, _ts: Date.now() }).toString();
        const payload = await LoanPro.request(`${url}?${query}`, {
            headers: { Accept: 'application/json' },
        });
        container.innerHTML = payload.html;
        container.classList.remove('opacity-50');
    },

    /**
     * Wires a filter panel (and the pagination rendered inside the table) to a
     * server-rendered listing container, so filters, sorting and paging all
     * refresh the table over AJAX without a full page reload.
     */
    bindTableFilters({ url, container, form = '.lp-filter-panel form', afterReload = null } = {}) {
        const formEl = typeof form === 'string' ? document.querySelector(form) : form;
        const containerEl = typeof container === 'string' ? document.querySelector(container) : container;

        if (!url || !containerEl) {
            return;
        }

        const load = async (extra = {}) => {
            const params = formEl
                ? Object.fromEntries(new URLSearchParams(new FormData(formEl)).entries())
                : {};

            await LoanPro.reloadTable(url, container, { ...params, ...extra });
            bindPagination();
            bindSorting();
            afterReload?.(containerEl);
        };

        const bindPagination = () => {
            containerEl.querySelectorAll('.pagination a').forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    const page = new URL(link.href, window.location.origin).searchParams.get('page') ?? 1;
                    load({ page });
                });
            });
        };

        formEl?.addEventListener('submit', (event) => {
            event.preventDefault();
            load();
        });

        formEl?.querySelectorAll('select, input[type="date"]').forEach((field) => {
            field.addEventListener('change', () => load());
        });

        const bindSorting = () => {
            let activeSort = formEl?.querySelector('[name="sort"]')?.value ?? new URL(window.location.href).searchParams.get('sort');
            let activeDirection = formEl?.querySelector('[name="direction"]')?.value ?? new URL(window.location.href).searchParams.get('direction') ?? 'desc';

            containerEl.querySelectorAll('th[data-sort]').forEach((header) => {
                const trigger = (event) => {
                    event.preventDefault();
                    const column = header.dataset.sort;
                    activeDirection = activeSort === column && activeDirection === 'asc' ? 'desc' : 'asc';
                    activeSort = column;

                    formEl?.querySelector('[name="sort"]') && (formEl.querySelector('[name="sort"]').value = column);
                    formEl?.querySelector('[name="direction"]') && (formEl.querySelector('[name="direction"]').value = activeDirection);

                    load({ sort: column, direction: activeDirection });
                };

                header.addEventListener('click', trigger);
                header.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        trigger(event);
                    }
                });
            });
        };

        bindPagination();
        bindSorting();

        return { load };
    },
};

/* --------------------------------------------------------------- behaviour */

document.addEventListener('DOMContentLoaded', () => {
    /* sidebar */
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const collapse = document.querySelector('[data-sidebar-collapse]');

    toggle?.addEventListener('click', () => document.body.classList.toggle('lp-sidebar-open'));

    collapse?.addEventListener('click', () => {
        document.body.classList.toggle('lp-sidebar-collapsed');
        document.cookie = `lp_sidebar=${document.body.classList.contains('lp-sidebar-collapsed') ? 'collapsed' : 'expanded'};path=/;max-age=31536000`;
    });

    document.querySelector('.lp-backdrop')?.addEventListener('click', () => document.body.classList.remove('lp-sidebar-open'));

    /* auto-dismiss flash toasts */
    document.querySelectorAll('.lp-toast[data-autohide]').forEach((el) => {
        setTimeout(() => el.remove(), 6000);
    });

    /* global search */
    const searchInput = document.querySelector('[data-global-search]');
    const searchResults = document.getElementById('lp-search-results');

    if (searchInput && searchResults) {
        let timer;
        let controller;

        const close = () => searchResults.classList.remove('is-open');

        searchInput.addEventListener('input', () => {
            clearTimeout(timer);
            const term = searchInput.value.trim();

            if (term.length < 2) {
                close();
                return;
            }

            timer = setTimeout(async () => {
                controller?.abort();
                controller = new AbortController();

                try {
                    const payload = await LoanPro.request(`/search?q=${encodeURIComponent(term)}`);
                    searchResults.innerHTML = payload.groups.length
                        ? payload.groups.map((group) => `
                            <div class="lp-search-results__group">
                                <div class="lp-search-results__label"><i class="bi ${group.icon} me-1"></i>${group.label}</div>
                                ${group.items.map((item) => `
                                    <a class="lp-search-results__item" href="${item.url}">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">${item.title}</div>
                                            <div class="text-muted small">${item.subtitle}</div>
                                        </div>
                                        <span class="lp-badge lp-badge--dot text-${item.color} bg-${item.color}-subtle bg-opacity-10">${item.badge}</span>
                                    </a>`).join('')}
                            </div>`).join('')
                        : '<div class="lp-empty"><i class="bi bi-search"></i>No matches found</div>';
                    searchResults.classList.add('is-open');
                } catch (error) {
                    LoanPro.toast(error.message, 'danger');
                }
            }, 260);
        });

        document.addEventListener('click', (event) => {
            if (!searchResults.contains(event.target) && event.target !== searchInput) {
                close();
            }
        });

        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
                searchInput.blur();
            }

            if (event.key === '/' && document.activeElement !== searchInput) {
                searchInput.focus();
            }
        });
    }

    /* keyboard shortcut: "/" focuses global search */
    document.addEventListener('keydown', (event) => {
        if (event.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
            event.preventDefault();
            document.querySelector('[data-global-search]')?.focus();
        }
    });

    /* confirm dialogs for destructive actions */
    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-confirm]');

        if (!trigger) {
            return;
        }

        event.preventDefault();
        const confirmed = await LoanPro.confirm({
            title: trigger.dataset.confirmTitle ?? 'Please confirm',
            message: trigger.dataset.confirm,
            confirmText: trigger.dataset.confirmText ?? 'Yes, continue',
            variant: trigger.dataset.confirmVariant ?? 'danger',
        });

        if (!confirmed) {
            return;
        }

        if (trigger.dataset.form) {
            document.querySelector(trigger.dataset.form)?.submit();
            return;
        }

        if (trigger.tagName === 'A' && trigger.href) {
            window.location.href = trigger.href;
            return;
        }

        const method = (trigger.dataset.method ?? 'DELETE').toLowerCase();
        const url = trigger.dataset.url;

        if (url) {
            try {
                await LoanPro.request(url, { method });
                LoanPro.toast(trigger.dataset.successMessage ?? 'Done successfully.');
                setTimeout(() => window.location.reload(), 700);
            } catch (error) {
                LoanPro.toast(error.message, 'danger');
            }
        }
    });

    /* AJAX forms */
    document.querySelectorAll('form[data-ajax]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submit = form.querySelector('[type="submit"]');
            const original = submit?.innerHTML;

            if (submit) {
                submit.disabled = true;
                submit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
            }

            try {
                const payload = await LoanPro.request(form.action, {
                    method: (form.method || 'POST').toUpperCase(),
                    body: new FormData(form),
                });

                LoanPro.toast(payload.message ?? 'Saved successfully.');
                form.dispatchEvent(new CustomEvent('loanpro:saved', { detail: payload }));

                if (form.dataset.reload !== 'false') {
                    setTimeout(() => window.location.reload(), 800);
                }

                if (payload.redirect) {
                    window.location.href = payload.redirect;
                }
            } catch (error) {
                const errors = error.payload?.errors ?? {};
                const messages = Object.values(errors).flat();

                if (messages.length) {
                    messages.slice(0, 3).forEach((message) => LoanPro.toast(message, 'danger'));
                } else {
                    LoanPro.toast(error.message, 'danger');
                }
            } finally {
                if (submit) {
                    submit.disabled = false;
                    submit.innerHTML = original;
                }
            }
        });
    });

    /* toast helper for session flashes rendered server side */
    document.querySelectorAll('[data-flash]').forEach((el) => {
        LoanPro.toast(el.dataset.message, el.dataset.flash);
        el.remove();
    });

    /* notification bell */
    document.querySelector('[data-notification-bell]')?.addEventListener('click', async (event) => {
        event.preventDefault();
        const payload = await LoanPro.request('/notifications/feed');
        const dropdown = document.getElementById('lp-notification-feed');

        if (dropdown) {
            dropdown.innerHTML = payload.html;
            bootstrap.Dropdown.getOrCreateInstance(event.currentTarget).show();
        }
    });

    /* file uploaders: reflect the chosen file and highlight drag & drop */
    document.querySelectorAll('[data-dropzone]').forEach((zone) => {
        const input = zone.querySelector('input[type="file"]');
        const label = zone.querySelector('[data-file-name]');
        if (!input) return;

        const show = () => {
            const file = input.files?.[0];
            if (!label) return;
            label.textContent = file ? `${file.name} · ${(file.size / 1024).toFixed(0)} KB` : '';
            label.classList.toggle('d-none', !file);
            zone.classList.toggle('is-filled', Boolean(file));
        };

        input.addEventListener('change', show);
        show();

        ['dragenter', 'dragover'].forEach((eventName) =>
            zone.addEventListener(eventName, (event) => {
                event.preventDefault();
                zone.classList.add('is-dragging');
            }));
        ['dragleave', 'drop'].forEach((eventName) =>
            zone.addEventListener(eventName, (event) => {
                event.preventDefault();
                zone.classList.remove('is-dragging');
            }));
        zone.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];
            if (!file) return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            show();
        });
    });

    /* tooltips */
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));
});
