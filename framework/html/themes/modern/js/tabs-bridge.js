(function () {
    'use strict';

    if (window.parent === window) return;
    var origin = window.location.origin;

    function reportTab() {
        var menu = new URLSearchParams(window.location.search).get('menu') || '';
        var heading = document.querySelector('.iss-pagehead__title, h1');
        var label = heading ? heading.textContent.trim() : '';
        window.parent.postMessage({
            type: 'tabinfo',
            key: menu,
            label: label || menu || document.title || 'Issabel',
            src: window.location.href
        }, origin);
    }

    function reportDirty(dirty) {
        window.parent.postMessage({ type: 'dirtyChanged', dirty: !!dirty }, origin);
    }

    // A language change redirects this iframe with iss_lang_changed=1. Alert the
    // shell so it reloads the whole environment (menu + every open tab) in the
    // new language, then strip the flag so it is handled only once.
    function announceLanguageChange() {
        var params = new URLSearchParams(window.location.search);
        if (params.get('iss_lang_changed') !== '1') return;
        window.parent.postMessage({ type: 'languageChanged' }, origin);
        params.delete('iss_lang_changed');
        var query = params.toString();
        window.history.replaceState(null, '',
            window.location.pathname + (query ? '?' + query : '') + window.location.hash);
    }

    function markDirty(event) {
        var form = event.target.closest('form');
        if (!form || form.dataset.issDirty === '1') return;
        form.dataset.issDirty = '1';
        reportDirty(true);
    }

    document.addEventListener('input', markDirty, true);
    document.addEventListener('change', markDirty, true);
    document.addEventListener('submit', function (event) {
        var form = event.target.closest('form');
        if (form && form.checkValidity()) {
            form.dataset.issDirty = '';
            reportDirty(false);
        }
    }, true);

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link || (link.target && link.target !== '_self') || event.ctrlKey ||
            event.metaKey || event.shiftKey || event.altKey) return;

        var url = new URL(link.href, window.location.href);
        if (url.origin !== origin || !url.searchParams.has('menu')) return;
        var currentModule = document.getElementById('issabel_framework_module_id');
        var currentMenu = currentModule ? currentModule.value :
            (new URLSearchParams(window.location.search).get('menu') || '');
        if (url.pathname === window.location.pathname && url.searchParams.get('menu') === currentMenu) {
            // Keep actions and item IDs inside the current module iframe.
            return;
        }
        event.preventDefault();
        window.parent.postMessage({ type: 'navigateModule', href: url.href }, origin);
    }, true);

    window.addEventListener('message', function (event) {
        if (event.origin !== origin || event.source !== window.parent) return;
        var message = event.data || {};
        if (message.type === 'themeChanged' && (message.theme === 'light' || message.theme === 'dark')) {
            document.documentElement.setAttribute('data-bs-theme', message.theme);
        }
    });

    function boot() {
        reportTab();
        announceLanguageChange();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
}());
