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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', reportTab, { once: true });
    } else {
        reportTab();
    }
}());
