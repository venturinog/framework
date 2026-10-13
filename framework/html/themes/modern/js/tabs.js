(function () {
    'use strict';

    var config = window.ISSABEL_TABS_CONFIG || {};
    var state = { tabs: [], activeTab: 0 };
    var claimedInstanceId = null;
    var tabbar = document.getElementById('iss-tabbar');
    var tabpanes = document.getElementById('iss-tabpanes');
    var pageTitle = document.getElementById('iss-active-page-title');
    var breadcrumbNav = document.getElementById('iss-active-breadcrumb');
    var shellPath = window.location.pathname;

    function newId() {
        var bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        return Array.prototype.map.call(bytes, function (byte) {
            return byte.toString(16).padStart(2, '0');
        }).join('');
    }

    function instanceId() {
        var id = window.sessionStorage.getItem('issabel_instance');
        if (!id) {
            id = newId();
            window.sessionStorage.setItem('issabel_instance', id);
        }
        return id;
    }

    // Browser tab duplication can clone sessionStorage. A short same-origin
    // claim elects one owner; other documents using that ID rotate to a new one.
    async function claimInstanceId() {
        if (claimedInstanceId) return claimedInstanceId;
        var id = instanceId();
        var nonce = newId();
        if (!('BroadcastChannel' in window)) throw new Error('BroadcastChannel required');

        var channel = new BroadcastChannel('issabel-tabs');
        window.issabelTabsChannel = channel;
        var peers = new Set();
        channel.onmessage = function (event) {
            var message = event.data || {};
            if (message.id !== id || message.nonce === nonce) return;
            peers.add(message.nonce);
            if (message.type === 'claim') {
                channel.postMessage({ type: 'answer', id: id, nonce: nonce });
            }
        };

        channel.postMessage({ type: 'claim', id: id, nonce: nonce });
        await new Promise(function (resolve) { window.setTimeout(resolve, 200); });
        if (Array.from(peers).some(function (peerNonce) { return nonce > peerNonce; })) {
            id = newId();
            window.sessionStorage.setItem('issabel_instance', id);
            channel.postMessage({ type: 'claim', id: id, nonce: nonce });
        }
        claimedInstanceId = id;
        return id;
    }

    function requestErrorMessage(action, status, data) {
        var messages = config.messages || {};
        var errorKeys = {
            request_rejected: 'requestRejected',
            session_expired: 'sessionExpired',
            module_not_authorized: 'moduleNotAuthorized',
            invalid_instance: 'invalidInstance',
            invalid_tab_identity: 'invalidIdentity',
            tab_not_found: 'tabNotFound',
            tab_store_error: 'tabStoreError'
        };
        var reasonKeys = {
            limit: 'limit',
            last_tab: 'lastTab',
            not_found: 'tabNotFound'
        };
        if (data && data.error && errorKeys[data.error] && messages[errorKeys[data.error]]) {
            return messages[errorKeys[data.error]];
        }
        if (data && data.reason && reasonKeys[data.reason] && messages[reasonKeys[data.reason]]) {
            return messages[reasonKeys[data.reason]];
        }
        return (messages.requestFailed || 'The tab request failed') + ' (HTTP ' + status + ').';
    }

    function request(action, values) {
        var id = instanceId();
        var body = new URLSearchParams();
        Object.keys(values || {}).forEach(function (key) {
            body.append(key, values[key]);
        });

        return window.fetch('index.php?tabmgr=' + encodeURIComponent(action), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Issabel-Instance-Id': id,
                'X-Issabel-Tabs-CSRF': config.csrf || ''
            },
            body: body.toString()
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (!response.ok) {
                    var error = new Error(requestErrorMessage(action, response.status, data));
                    error.response = data;
                    error.status = response.status;
                    throw error;
                }
                clearTransientError();
                return data;
            });
        });
    }

    function stateFrom(response) {
        return response.state || response;
    }

    function getTab(id) {
        return state.tabs.filter(function (tab) { return Number(tab.id) === Number(id); })[0] || null;
    }

    var toastTimer = null;
    function dismissToast() {
        var toast = document.getElementById('iss-tab-toast');
        if (toast) toast.classList.remove('visible');
        window.clearTimeout(toastTimer);
        toastTimer = null;
    }

    function showNotice(message, type, duration) {
        var toast = document.getElementById('iss-tab-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'iss-tab-toast';
            toast.className = 'iss-tab-toast';
            var text = document.createElement('span');
            text.className = 'iss-tab-toast__text';
            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'iss-tab-toast__close';
            close.setAttribute('aria-label', (config.messages && config.messages.dismissNotice) || 'Dismiss tab notice');
            close.textContent = '×';
            close.addEventListener('click', dismissToast);
            toast.appendChild(text);
            toast.appendChild(close);
            document.body.appendChild(toast);
        }

        toast.dataset.type = type;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
        toast.querySelector('.iss-tab-toast__text').textContent = message;
        toast.classList.toggle('iss-tab-toast--error', type === 'error');
        toast.classList.add('visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(dismissToast, duration);
    }

    function showToast(message) {
        showNotice(message || config.openedToast || (config.messages && config.messages.openedToast) ||
            'Opened in another tab', 'info', 3000);
    }

    function showError(message) {
        showNotice(message || (config.messages && config.messages.operationError) ||
            'The tab operation could not be completed.', 'error', 6000);
    }

    function clearTransientError() {
        var toast = document.getElementById('iss-tab-toast');
        if (toast && toast.dataset.type === 'error') dismissToast();
    }

    function safeFrameSource(tab) {
        if (!tab || typeof tab.src !== 'string') return '';
        var url = new URL(tab.src, window.location.href);
        return url.origin === window.location.origin ? url.href : '';
    }

    function currentTheme() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
    }

    function syncFrameTheme(frame) {
        if (!frame || !frame.contentWindow) return;
        frame.contentWindow.postMessage({ type: 'themeChanged', theme: currentTheme() }, window.location.origin);
    }

    function broadcastTheme() {
        document.querySelectorAll('.iss-tabpane__frame').forEach(syncFrameTheme);
    }

    function menuLinkFor(menuId) {
        if (!menuId) return null;
        var links = document.querySelectorAll('.iss-nav a[href]');
        for (var i = 0; i < links.length; i++) {
            try {
                var url = new URL(links[i].href, window.location.href);
                if (url.pathname === shellPath && url.searchParams.get('menu') === menuId) return links[i];
            } catch (e) {}
        }
        return null;
    }

    function linkLabel(link) {
        var label = link && link.querySelector('span');
        return (label ? label.textContent : (link ? link.textContent : '')).trim();
    }

    function menuMetadata(menuId) {
        var link = menuLinkFor(menuId);
        if (!link) return { menuId: menuId || '', title: menuId || '', breadcrumb: [] };

        var title = linkLabel(link) || menuId;
        var breadcrumb = [];
        if (link.classList.contains('iss-nav__quick')) {
            breadcrumb.push(title);
            return { menuId: menuId, title: title, breadcrumb: breadcrumb };
        }

        var group = link.closest('.iss-nav__group');
        if (group) {
            var groupToggle = group.querySelector('.iss-nav__toggle span');
            if (groupToggle && groupToggle.textContent.trim()) breadcrumb.push(groupToggle.textContent.trim());
        }

        var thirdLevel = link.closest('.iss-nav__third');
        if (thirdLevel && group) {
            var toggles = group.querySelectorAll('.iss-nav__subtoggle');
            for (var j = 0; j < toggles.length; j++) {
                if (toggles[j].getAttribute('data-target') === '#' + thirdLevel.id) {
                    var subLabel = linkLabel(toggles[j]);
                    if (subLabel) breadcrumb.push(subLabel);
                    break;
                }
            }
        }
        breadcrumb.push(title);
        return { menuId: menuId, title: title, breadcrumb: breadcrumb };
    }

    function metadataForTab(tab) {
        var menuId = tab.menuId || (/^landing-dashboard-\d+$/.test(tab.key || '') ? 'dashboard' :
            (tab.kind === 'pbx' ? 'pbxadmin' : tab.key));
        var fromMenu = menuMetadata(menuId);
        if (tab.kind === 'pbx') {
            return {
                menuId: menuId,
                title: tab.title || tab.label || fromMenu.title,
                breadcrumb: Array.isArray(tab.breadcrumb) && tab.breadcrumb.length
                    ? tab.breadcrumb : fromMenu.breadcrumb
            };
        }
        return fromMenu;
    }

    function updateSidebar(menuId) {
        var nav = document.querySelector('.iss-nav');
        if (!nav) return;
        nav.querySelectorAll('a.active, a.is-open').forEach(function (link) {
            link.classList.remove('active', 'is-open');
        });
        nav.querySelectorAll('.iss-nav__sub.expanded, .iss-nav__third.expanded').forEach(function (group) {
            group.classList.remove('expanded');
        });
        nav.querySelectorAll('.iss-nav__toggle, .iss-nav__subtoggle').forEach(function (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        });

        var link = menuLinkFor(menuId);
        if (!link) return;
        link.classList.add(link.classList.contains('iss-nav__quick') ? 'active' :
            (link.closest('.iss-nav__third') ? 'active' : 'is-open'));

        var group = link.closest('.iss-nav__group');
        if (!group) return;
        var sub = group.querySelector('.iss-nav__sub');
        var mainToggle = group.querySelector('.iss-nav__toggle');
        if (sub) sub.classList.add('expanded');
        if (mainToggle) mainToggle.setAttribute('aria-expanded', 'true');

        var third = link.closest('.iss-nav__third');
        if (third) {
            third.classList.add('expanded');
            group.querySelectorAll('.iss-nav__subtoggle').forEach(function (toggle) {
                if (toggle.getAttribute('data-target') === '#' + third.id) {
                    toggle.setAttribute('aria-expanded', 'true');
                }
            });
        }
    }

    function renderBreadcrumb(crumbs) {
        if (!breadcrumbNav) return;
        var home = breadcrumbNav.querySelector('a[href="index.php"]');
        breadcrumbNav.replaceChildren();
        if (home) breadcrumbNav.appendChild(home);
        (crumbs || []).forEach(function (crumb) {
            if (!crumb) return;
            var separator = document.createElement('i');
            separator.className = 'ti ti-chevron-right';
            breadcrumbNav.appendChild(separator);
            var item = document.createElement('span');
            item.textContent = crumb;
            breadcrumbNav.appendChild(item);
        });
    }

    function renderBar() {
        var fragment = document.createDocumentFragment();
        state.tabs.forEach(function (tab) {
            var item = document.createElement('div');
            item.className = 'iss-tabbar__item';
            item.classList.toggle('active', Number(tab.id) === Number(state.activeTab));

            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'iss-tabbar__tab';
            button.dataset.action = 'activate';
            button.dataset.tabId = tab.id;
            button.setAttribute('role', 'tab');
            button.setAttribute('aria-selected', Number(tab.id) === Number(state.activeTab) ? 'true' : 'false');

            var label = document.createElement('span');
            label.className = 'iss-tabbar__label';
            label.textContent = metadataForTab(tab).title || tab.label || tab.key || 'Tab';
            button.appendChild(label);

            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'iss-tabbar__close';
            close.dataset.action = 'close';
            close.dataset.tabId = tab.id;
            var closeTemplate = (config.messages && config.messages.closeTab) || 'Close %s';
            close.setAttribute('aria-label', closeTemplate.replace('%s', metadataForTab(tab).title || tab.label || 'Tab'));
            close.textContent = '×';
            close.disabled = state.tabs.length <= 1;

            item.appendChild(button);
            item.appendChild(close);
            fragment.appendChild(item);
        });

        if (state.tabs.length < Number(config.max || 8)) {
            var add = document.createElement('button');
            add.type = 'button';
            add.className = 'iss-tabbar__add';
            add.dataset.action = 'create';
            add.setAttribute('aria-label', (config.messages && config.messages.openNewTab) || 'Open a new tab');
            add.textContent = '+';
            fragment.appendChild(add);
        }

        tabbar.replaceChildren(fragment);
    }

    function ensurePane(tab) {
        var pane = document.getElementById('iss-tabpane-' + tab.id);
        if (pane) return pane;

        pane = document.createElement('div');
        pane.id = 'iss-tabpane-' + tab.id;
        pane.className = 'iss-tabpane';
        pane.dataset.tabId = tab.id;

        var frame = document.createElement('iframe');
        frame.id = 'issTab-' + tab.id;
        frame.className = 'iss-tabpane__frame';
        frame.title = tab.label || (config.messages && config.messages.moduleFrame) || 'Issabel module';
        frame.setAttribute('loading', 'lazy');
        frame.setAttribute('referrerpolicy', 'same-origin');
        frame.addEventListener('load', function () { syncFrameTheme(frame); });
        pane.appendChild(frame);
        tabpanes.appendChild(pane);
        return pane;
    }

    function renderPanes() {
        var liveIds = Object.create(null);
        state.tabs.forEach(function (tab) {
            liveIds[String(tab.id)] = true;
            var pane = ensurePane(tab);
            pane.classList.toggle('active', Number(tab.id) === Number(state.activeTab));
            var frame = pane.querySelector('iframe');
            frame.title = tab.label || (config.messages && config.messages.moduleFrame) || 'Issabel module';
        });

        Array.prototype.forEach.call(tabpanes.querySelectorAll('.iss-tabpane'), function (pane) {
            if (!liveIds[pane.dataset.tabId]) pane.remove();
        });
    }

    function renderState() {
        renderBar();
        renderPanes();
        broadcastTheme();
        var active = getTab(state.activeTab);
        if (active) {
            var metadata = metadataForTab(active);
            if (pageTitle) pageTitle.textContent = metadata.title || active.label || active.key || 'Dashboard';
            renderBreadcrumb(metadata.breadcrumb);
            updateSidebar(metadata.menuId);
        }
        broadcastTabs();
    }

    function applyResponse(response) {
        var next = stateFrom(response);
        if (Array.isArray(next.tabs)) {
            state.tabs = next.tabs;
            state.activeTab = next.activeTab;
        }
        renderState();
        loadActiveFrame();
    }

    function loadActiveFrame() {
        var tab = getTab(state.activeTab);
        if (!tab) return;
        var frame = document.getElementById('issTab-' + tab.id);
        if (!frame) return;
        var source = safeFrameSource(tab);
        if (!source) return;
        if (frame.dataset.issSrc !== source) {
            frame.dataset.issSrc = source;
            frame.src = source;
        }
    }

    function activateTab(id) {
        return request('activate', { id: id }).then(function (response) {
            applyResponse(response);
            loadActiveFrame();
        });
    }

    function navigatePbx(tabId) {
        var metadata = menuMetadata('pbxadmin');
        return request('update', {
            id: tabId,
            key: 'pbx|landing',
            kind: 'pbx',
            label: metadata.title || 'PBX Configuration',
            title: metadata.title || 'PBX Configuration',
            breadcrumb: JSON.stringify(metadata.breadcrumb),
            menuId: 'pbxadmin'
        }).then(function (response) {
            applyResponse(response);
            if (response.duplicate) showToast(config.openedToast);
            loadActiveFrame();
        });
    }

    function navigateModule(menu, tabId) {
        if (!/^[a-zA-Z0-9_.-]{1,80}$/.test(menu)) return Promise.resolve();
        if (menu === 'pbxadmin') return navigatePbx(tabId);

        var hit = state.tabs.filter(function (tab) {
            return tab.key === menu && Number(tab.id) !== Number(tabId);
        })[0];
        if (hit) {
            return activateTab(hit.id).then(function () {
                showToast(config.openedToast);
            });
        }

        var current = getTab(tabId);
        if (current && current.key === menu) return activateTab(current.id);

        var metadata = menuMetadata(menu);
        return request('update', {
            id: tabId,
            key: menu,
            kind: 'issabel',
            label: metadata.title || menu,
            title: metadata.title || menu,
            breadcrumb: JSON.stringify(metadata.breadcrumb),
            menuId: menu,
            history: '1'
        }).then(function (response) {
            applyResponse(response);
            if (response.duplicate) {
                state.activeTab = response.duplicate.id;
                renderState();
                showToast(config.openedToast);
            }
            loadActiveFrame();
        }).catch(function (error) {
            showError(error.message);
        });
    }

    function clearInitError() {
        var error = document.getElementById('iss-tabs-init-error');
        if (error) error.remove();
    }

    function showInitError(message) {
        tabbar.hidden = true;
        tabpanes.hidden = true;

        var error = document.getElementById('iss-tabs-init-error');
        if (!error) {
            error = document.createElement('div');
            error.id = 'iss-tabs-init-error';
            error.className = 'iss-tabs-init-error alert alert-danger';
            error.setAttribute('role', 'alert');
            var text = document.createElement('span');
            text.className = 'iss-tabs-init-error__text';
            var retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'btn btn-sm btn-outline-danger';
            retry.textContent = (config.messages && config.messages.retry) || 'Retry';
            retry.addEventListener('click', function () {
                retry.disabled = true;
                initialize();
            });
            error.appendChild(text);
            error.appendChild(retry);
            tabpanes.parentNode.insertBefore(error, tabpanes);
        }
        error.querySelector('.iss-tabs-init-error__text').textContent = message;
        var retryButton = error.querySelector('button');
        retryButton.disabled = false;
        retryButton.textContent = (config.messages && config.messages.retry) || 'Retry';
        error.hidden = false;
    }

    function initialNavigation() {
        var menu = new URL(window.location.href).searchParams.get('menu');
        if (menu) navigateModule(menu, state.activeTab).catch(function (error) { showError(error.message); });
    }

    tabbar.addEventListener('click', function (event) {
        var control = event.target.closest('[data-action]');
        if (!control) return;
        var action = control.dataset.action;
        var id = Number(control.dataset.tabId);

        if (action === 'create') {
            request('create').then(function (response) {
                applyResponse(response);
                loadActiveFrame();
            }).catch(function (error) { showError(error.message); });
        } else if (action === 'activate') {
            activateTab(id).catch(function (error) { showError(error.message); });
        } else if (action === 'close') {
            var tab = getTab(id);
            var closeWarning = (config.messages && config.messages.closeUnsaved) ||
                'This tab has unsaved changes. Close it anyway?';
            if (tab && tab.dirty && !window.confirm(closeWarning)) return;
            request('close', { id: id }).then(function (response) {
                applyResponse(response);
                loadActiveFrame();
            }).catch(function (error) { showError(error.message); });
        }
    });

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link || link.target && link.target !== '_self' || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        var url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || url.pathname !== shellPath) return;
        var menu = url.searchParams.get('menu');
        if (!menu || url.searchParams.get('logout') === 'yes') return;

        event.preventDefault();
        navigateModule(menu, state.activeTab).catch(function (error) { showError(error.message); });
    }, true);

    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin || !event.source) return;
        var sourceTab = state.tabs.filter(function (tab) {
            var frame = document.getElementById('issTab-' + tab.id);
            return frame && frame.contentWindow === event.source;
        })[0];
        if (!sourceTab) return;

        var message = event.data || {};
        if (message.type === 'navigateModule') {
            if (typeof message.href !== 'string') return;
            var url = new URL(message.href, window.location.href);
            if (url.origin !== window.location.origin || url.pathname !== shellPath) return;
            var menu = url.searchParams.get('menu');
            if (menu) navigateModule(menu, sourceTab.id).catch(function (error) { showError(error.message); });
        } else if (message.type === 'tabinfo') {
            // A new tab intentionally starts on Dashboard; keep its landing
            // identity distinct until the user navigates away from it.
            if (/^landing-dashboard-\d+$/.test(sourceTab.key) && message.key === 'dashboard') return;
            var metadata = sourceTab.kind === 'pbx'
                ? {
                    menuId: 'pbxadmin',
                    title: message.title || message.label || sourceTab.title || sourceTab.label,
                    breadcrumb: Array.isArray(message.breadcrumb) ? message.breadcrumb : sourceTab.breadcrumb
                }
                : menuMetadata(message.key || sourceTab.key);
            request('update', {
                id: sourceTab.id,
                key: message.key || sourceTab.key,
                kind: sourceTab.kind || 'issabel',
                label: metadata.title || message.label || sourceTab.label,
                title: metadata.title || message.label || sourceTab.title || sourceTab.label,
                breadcrumb: JSON.stringify(metadata.breadcrumb || []),
                menuId: metadata.menuId || sourceTab.menuId || sourceTab.key
            }).then(function (response) {
                applyResponse(response);
                if (response.duplicate) showToast(config.openedToast);
            }).catch(function () {});
        } else if (message.type === 'dirtyChanged') {
            request('setDirty', { id: sourceTab.id, dirty: message.dirty ? '1' : '0' })
                .then(applyResponse).catch(function () {});
        } else if (message.type === 'focusTab') {
            activateTab(message.tabId).then(function () {
                showToast(config.openedToast);
            }).catch(function () {});
        }
    });

    window.addEventListener('issabel-theme-changed', broadcastTheme);

    function broadcastTabs() {
        document.querySelectorAll('.iss-tabpane__frame').forEach(function (frame) {
            if (!frame.contentWindow) return;
            frame.contentWindow.postMessage({ tabs: state.tabs, activeTab: state.activeTab, type: 'tabsChanged' }, window.location.origin);
        });
    }

    var initializing = false;
    async function initialize() {
        if (initializing) return;
        initializing = true;
        try {
            var id = await claimInstanceId();
            window.sessionStorage.setItem('issabel_instance', id);
            var response = await request('init');
            applyResponse(response);
            clearInitError();
            tabbar.hidden = false;
            tabpanes.hidden = false;
            loadActiveFrame();
            initialNavigation();
        } catch (error) {
            var messages = config.messages || {};
            var initError = messages.initFailed || 'Could not initialize tabs.';
            if (error.message === 'BroadcastChannel required') {
                initError = messages.browserUnsupported || initError;
            } else if (error.status && error.message) {
                initError += ' ' + error.message;
            }
            showInitError(initError);
        } finally {
            initializing = false;
        }
    }

    initialize();
}());
