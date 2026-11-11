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

    var DRAG_THRESHOLD = 5;
    var AUTO_SCROLL_EDGE = 28;
    var AUTO_SCROLL_MAX = 18;
    var navScrollRaf = 0;
    var drag = {
        armed: false,
        active: false,
        item: null,
        pointerId: null,
        startX: 0,
        startY: 0,
        grabOffsetX: 0,
        offsetX: 0,
        lastClientX: 0,
        raf: 0,
        suppressClickUntil: 0,
        order: null
    };

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
        // Rebuilding the bar must not send the strip back to the start: a
        // background tabinfo/dirtyChanged would otherwise scroll it away.
        var scrollLeft = tabbar.scrollLeft;
        var fragment = document.createDocumentFragment();
        state.tabs.forEach(function (tab) {
            var item = document.createElement('div');
            item.className = 'iss-tabbar__item';
            item.dataset.tabId = tab.id;
            item.classList.toggle('active', Number(tab.id) === Number(state.activeTab));

            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'iss-tabbar__tab';
            button.dataset.action = 'activate';
            button.dataset.tabId = tab.id;
            button.setAttribute('role', 'tab');
            button.setAttribute('aria-selected', Number(tab.id) === Number(state.activeTab) ? 'true' : 'false');
            button.title = (config.messages && config.messages.dragHint) || 'Drag to reorder tabs';
            button.setAttribute('aria-keyshortcuts', 'Alt+ArrowLeft Alt+ArrowRight');

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

        if (state.tabs.length < Number(config.max || 10)) {
            var add = document.createElement('button');
            add.type = 'button';
            add.className = 'iss-tabbar__add';
            add.dataset.action = 'create';
            add.setAttribute('aria-label', (config.messages && config.messages.openNewTab) || 'Open a new tab');
            add.textContent = '+';
            fragment.appendChild(add);
        }

        tabbar.replaceChildren(fragment);
        tabbar.scrollLeft = scrollLeft;
        updateTabStripNav();
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
        // A drag owns the bar DOM: rebuilding it mid-gesture would detach the
        // dragged node and lose its pointer capture. Deferred renders resume
        // as soon as the gesture ends (see finishDrag).
        if (drag.active) return;
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
        var previousActive = Number(state.activeTab);
        if (Array.isArray(next.tabs)) {
            state.tabs = next.tabs;
            state.activeTab = next.activeTab;
        }
        renderState();
        if (Number(state.activeTab) !== previousActive) revealActiveTab();
        loadActiveFrame();
    }

    function loadTabFrame(tab) {
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

    function loadActiveFrame() {
        loadTabFrame(getTab(state.activeTab));
    }

    // issabelPBX tab labels are persisted in the store and preferred over the
    // freshly translated sidebar, so they only refresh when their iframe
    // reloads and reports a translated tabinfo. Inactive panes are display:none,
    // so a lazy iframe would never load: force eager loading (and bypass the HTTP
    // cache) for every pbx tab after a language change. Issabel tabs already
    // derive their labels from the reloaded sidebar.
    function reloadPbxFramesAfterLanguageChange() {
        var flag = 'issabel_reload_pbx_tabs';
        var token = window.sessionStorage.getItem(flag);
        if (!token) return;
        window.sessionStorage.removeItem(flag);
        state.tabs.forEach(function (tab) {
            if (tab.kind !== 'pbx') return;
            var frame = document.getElementById('issTab-' + tab.id);
            var source = safeFrameSource(tab);
            if (!frame || !source) return;
            frame.setAttribute('loading', 'eager');
            frame.dataset.issSrc = source;
            frame.src = source + (source.indexOf('?') === -1 ? '?' : '&')
                + '_issr=' + encodeURIComponent(token);
        });
    }

    function handleLanguageChange() {
        var dirty = state.tabs.some(function (tab) { return tab.dirty; });
        if (dirty) {
            var confirmMessage = (config.messages && config.messages.languageReload) ||
                'Changing the language will reload all open tabs and discard unsaved changes. Continue?';
            if (!window.confirm(confirmMessage)) {
                showNotice((config.messages && config.messages.languageReloadDeferred) ||
                    'The language will fully apply the next time you reload the page.', 'info', 6000);
                return;
            }
        }
        try {
            window.sessionStorage.setItem('issabel_reload_pbx_tabs', String(Date.now()));
        } catch (e) {}
        window.location.reload();
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
        updateTabStripNav();

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

    // ------------------------------------------------------------------
    // Tab strip navigation (arrows for overflowing titles)
    // ------------------------------------------------------------------

    var navPrev = document.getElementById('iss-tabbar-prev');
    var navNext = document.getElementById('iss-tabbar-next');

    function reduceMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function navTargets() {
        var targets = tabItems();
        var add = addTabButton();
        if (add) targets.push(add);
        return targets;
    }

    // Overflow and edge state come from element rectangles, not from
    // scrollLeft, so the same test works in LTR and RTL.
    function tabStripState() {
        if (!tabbar || tabbar.hidden || tabbar.scrollWidth - tabbar.clientWidth <= 1) {
            return { overflow: false, atStart: true, atEnd: true };
        }

        var targets = navTargets();
        if (!targets.length) return { overflow: false, atStart: true, atEnd: true };

        var bar = tabbar.getBoundingClientRect();
        var rtl = window.getComputedStyle(tabbar).direction === 'rtl';
        var first = targets[0].getBoundingClientRect();
        var last = targets[targets.length - 1].getBoundingClientRect();

        return {
            overflow: true,
            atStart: rtl ? (first.right <= bar.right + 1) : (first.left >= bar.left - 1),
            atEnd: rtl ? (last.left >= bar.left - 1) : (last.right <= bar.right + 1)
        };
    }

    function updateTabStripNav() {
        if (!navPrev || !navNext) return;
        var strip = tabStripState();
        navPrev.hidden = !strip.overflow;
        navNext.hidden = !strip.overflow;
        navPrev.disabled = !strip.overflow || strip.atStart;
        navNext.disabled = !strip.overflow || strip.atEnd;
    }

    function fullVisibleRange() {
        var bar = tabbar.getBoundingClientRect();
        var first = -1;
        var last = -1;
        navTargets().forEach(function (item, index) {
            var rect = item.getBoundingClientRect();
            if (rect.left >= bar.left - 1 && rect.right <= bar.right + 1) {
                if (first < 0) first = index;
                last = index;
            }
        });
        return { first: first, last: last };
    }

    // One click reveals exactly one more target (tab or the "+") in DOM order;
    // scrollIntoView handles direction, clamping and RTL.
    function scrollTabsByStep(step) {
        var targets = navTargets();
        if (!targets.length) return;

        var range = fullVisibleRange();
        var target;
        if (step > 0) {
            target = (range.last < 0) ? 0 : Math.min(range.last + 1, targets.length - 1);
        } else {
            target = (range.first < 0) ? targets.length - 1 : Math.max(range.first - 1, 0);
        }

        targets[target].scrollIntoView({
            block: 'nearest',
            inline: 'nearest',
            behavior: reduceMotion() ? 'auto' : 'smooth'
        });
    }

    function revealActiveTab() {
        if (drag.active || tabbar.hidden) return;
        var item = tabbar.querySelector('.iss-tabbar__item[data-tab-id="' + state.activeTab + '"]');
        if (!item) return;
        item.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }

    function initTabStripNav() {
        if (!navPrev || !navNext) return;

        var rtl = window.getComputedStyle(tabbar).direction === 'rtl';

        var prevIcon = document.createElement('i');
        prevIcon.className = 'ti ' + (rtl ? 'ti-chevron-right' : 'ti-chevron-left');
        prevIcon.setAttribute('aria-hidden', 'true');
        navPrev.appendChild(prevIcon);

        var nextIcon = document.createElement('i');
        nextIcon.className = 'ti ' + (rtl ? 'ti-chevron-left' : 'ti-chevron-right');
        nextIcon.setAttribute('aria-hidden', 'true');
        navNext.appendChild(nextIcon);

        var prevLabel = (config.messages && config.messages.prevTabs) || 'Show previous tabs';
        var nextLabel = (config.messages && config.messages.nextTabs) || 'Show next tabs';
        navPrev.setAttribute('aria-label', prevLabel);
        navPrev.title = prevLabel;
        navNext.setAttribute('aria-label', nextLabel);
        navNext.title = nextLabel;

        navPrev.addEventListener('click', function () { scrollTabsByStep(-1); });
        navNext.addEventListener('click', function () { scrollTabsByStep(1); });

        tabbar.addEventListener('scroll', function () {
            if (navScrollRaf) return;
            navScrollRaf = window.requestAnimationFrame(function () {
                navScrollRaf = 0;
                updateTabStripNav();
            });
        }, { passive: true });

        if ('ResizeObserver' in window) {
            // Observe the topbar, not the strip: toggling the arrows changes
            // the strip width, which would re-notify the observer.
            new ResizeObserver(function () { updateTabStripNav(); })
                .observe(tabbar.parentNode || tabbar);
        } else {
            window.addEventListener('resize', updateTabStripNav);
        }

        // Web fonts can change the tab widths after the first paint.
        window.addEventListener('load', updateTabStripNav);
    }

    initTabStripNav();

    // ------------------------------------------------------------------
    // Tab reordering (drag & drop + keyboard)
    // ------------------------------------------------------------------

    function tabItems() {
        return Array.prototype.slice.call(tabbar.querySelectorAll('.iss-tabbar__item'));
    }

    function addTabButton() {
        return tabbar.querySelector('.iss-tabbar__add');
    }

    function tabOrder() {
        return tabItems().map(function (item) { return Number(item.dataset.tabId); });
    }

    // Reorder the in-memory registry by id. Never touches panes or iframes, so
    // unsaved form state inside a tab survives a reorder untouched.
    function applyLocalOrder(ids) {
        var byId = Object.create(null);
        state.tabs.forEach(function (tab) { byId[String(tab.id)] = tab; });
        var next = [];
        ids.forEach(function (id) {
            var tab = byId[String(id)];
            if (tab) next.push(tab);
        });
        if (next.length === state.tabs.length) state.tabs = next;
    }

    function setDragOffset(dx) {
        drag.offsetX = dx;
        if (drag.item) drag.item.style.transform = dx ? 'translateX(' + dx + 'px)' : '';
    }

    function dragLayoutLeft() {
        if (!drag.item) return 0;
        return drag.item.getBoundingClientRect().left - drag.offsetX;
    }

    function snapshotLefts() {
        var lefts = Object.create(null);
        tabItems().forEach(function (item) {
            if (item === drag.item) return;
            lefts[String(item.dataset.tabId)] = item.getBoundingClientRect().left;
        });
        return lefts;
    }

    // FLIP: animate neighbours from their previous to their new position so an
    // insertion reads as a displacement instead of a jump.
    function clearShifts() {
        tabItems().forEach(function (item) {
            if (item === drag.item || !item.style.transform) return;
            item.classList.remove('is-shifting');
            item.style.transform = '';
        });
    }

    function flipNeighbours(lefts) {
        tabItems().forEach(function (item) {
            if (item === drag.item) return;
            var before = lefts[String(item.dataset.tabId)];
            if (typeof before !== 'number') return;
            var delta = before - item.getBoundingClientRect().left;
            if (!delta) return;
            item.classList.remove('is-shifting');
            item.style.transform = 'translateX(' + delta + 'px)';
            void item.offsetWidth;
            item.classList.add('is-shifting');
            item.style.transform = '';
        });
    }

    function moveDraggedTo(clientX) {
        if (!drag.active || !drag.item) return;
        var dragged = drag.item;
        var reference = null;
        var items = tabItems();
        for (var i = 0; i < items.length; i++) {
            if (items[i] === dragged) continue;
            var rect = items[i].getBoundingClientRect();
            if (clientX < rect.left + rect.width / 2) {
                reference = items[i];
                break;
            }
        }

        var next = dragged.nextElementSibling;
        if (reference === next) return;
        if (reference === null && (next === null || next === addTabButton())) return;

        var visualLeft = dragged.getBoundingClientRect().left;
        clearShifts();
        var lefts = snapshotLefts();
        tabbar.insertBefore(dragged, reference || addTabButton() || null);
        flipNeighbours(lefts);
        setDragOffset(visualLeft - dragLayoutLeft());
    }

    function updateDrag(clientX) {
        if (!drag.active || !drag.item) return;
        drag.lastClientX = clientX;
        setDragOffset((clientX - drag.grabOffsetX) - dragLayoutLeft());
        moveDraggedTo(clientX);
    }

    function autoScrollStep() {
        drag.raf = 0;
        if (!drag.active) return;

        var rect = tabbar.getBoundingClientRect();
        var speed = 0;
        if (tabbar.scrollWidth > tabbar.clientWidth) {
            if (drag.lastClientX < rect.left + AUTO_SCROLL_EDGE) {
                speed = -Math.min(AUTO_SCROLL_MAX,
                    Math.ceil((rect.left + AUTO_SCROLL_EDGE - drag.lastClientX) / 3));
            } else if (drag.lastClientX > rect.right - AUTO_SCROLL_EDGE) {
                speed = Math.min(AUTO_SCROLL_MAX,
                    Math.ceil((drag.lastClientX - (rect.right - AUTO_SCROLL_EDGE)) / 3));
            }
        }

        if (speed) {
            var before = tabbar.scrollLeft;
            tabbar.scrollLeft += speed;
            if (tabbar.scrollLeft !== before) updateDrag(drag.lastClientX);
        }

        drag.raf = window.requestAnimationFrame(autoScrollStep);
    }

    function startAutoScroll() {
        if (!drag.raf) drag.raf = window.requestAnimationFrame(autoScrollStep);
    }

    function stopAutoScroll() {
        if (drag.raf) window.cancelAnimationFrame(drag.raf);
        drag.raf = 0;
    }

    function beginDrag() {
        if (!drag.armed || drag.active || !drag.item) return;
        drag.active = true;
        drag.item.classList.add('is-dragging');
        document.body.classList.add('iss-tab-dragging');
        try { drag.item.setPointerCapture(drag.pointerId); } catch (e) {}
        updateDrag(drag.lastClientX);
        startAutoScroll();
    }

    function resetDrag() {
        stopAutoScroll();
        if (drag.item) {
            drag.item.classList.remove('is-dragging');
            drag.item.style.transform = '';
        }
        document.body.classList.remove('iss-tab-dragging');
        drag.armed = false;
        drag.active = false;
        drag.item = null;
        drag.pointerId = null;
        drag.offsetX = 0;
        drag.order = null;
    }

    function persistOrder(ids) {
        request('reorder', { order: ids.join(',') }).then(function (response) {
            if (response && response.reordered === false) {
                // Stale permutation: adopt the authoritative registry instead of
                // guessing. Panes are still untouched (no src change).
                applyResponse(response);
                return;
            }
            var next = stateFrom(response);
            if (Array.isArray(next.tabs) && next.tabs.length === ids.length &&
                next.tabs.every(function (tab, index) { return Number(tab.id) === ids[index]; })) {
                state.tabs = next.tabs;
                if (typeof next.activeTab !== 'undefined') state.activeTab = next.activeTab;
            }
        }).catch(function (error) {
            var data = error && error.response;
            if (data && data.reason === 'not_permutation' && data.state) {
                // Expected when the registry changed under the gesture (a tab was
                // created or closed meanwhile): resync silently, nothing is lost.
                applyResponse(data);
                return;
            }
            showError(error.message);
            request('init').then(applyResponse).catch(function () {});
        });
    }

    function finishDrag() {
        var wasActive = drag.active;
        var moved = false;
        var ids = null;

        if (wasActive) {
            ids = tabOrder();
            var before = drag.order || [];
            moved = ids.length !== before.length || ids.some(function (id, index) {
                return id !== before[index];
            });
            if (moved) applyLocalOrder(ids);
            drag.suppressClickUntil = Date.now() + 350;
        }

        resetDrag();

        if (wasActive) renderState();
        if (moved) persistOrder(ids);
    }

    // A cancelled gesture must not persist: the registry still holds the old
    // order, so a plain re-render restores the bar.
    function cancelDrag() {
        if (!drag.armed) return;
        var wasActive = drag.active;
        if (wasActive) drag.suppressClickUntil = Date.now() + 350;
        resetDrag();
        if (wasActive) renderState();
    }

    function announceTabPosition(index) {
        var template = (config.messages && config.messages.movedToPosition) || 'Tab moved to position %s';
        showNotice(template.replace('%s', String(index + 1)), 'info', 2000);
    }

    function focusTab(tabId) {
        var button = tabbar.querySelector('.iss-tabbar__item[data-tab-id="' + tabId + '"] .iss-tabbar__tab');
        if (button) button.focus();
    }

    function moveTabByKeyboard(tabId, step) {
        var ids = tabOrder();
        var index = ids.indexOf(Number(tabId));
        var target = index + step;
        if (index === -1 || target < 0 || target >= ids.length) return;

        ids.splice(target, 0, ids.splice(index, 1)[0]);
        applyLocalOrder(ids);
        renderState();
        focusTab(tabId);
        announceTabPosition(target);
        persistOrder(ids);
    }

    tabbar.addEventListener('pointerdown', function (event) {
        if (!event.isPrimary || drag.active) return;
        if (drag.armed) resetDrag();
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

        var target = event.target;
        var item = target && target.closest ? target.closest('.iss-tabbar__item') : null;
        if (!item || !item.dataset.tabId) return;
        if (target.closest('.iss-tabbar__close, .iss-tabbar__add')) return;

        drag.armed = true;
        drag.active = false;
        drag.item = item;
        drag.pointerId = event.pointerId;
        drag.startX = event.clientX;
        drag.startY = event.clientY;
        drag.grabOffsetX = event.clientX - item.getBoundingClientRect().left;
        drag.lastClientX = event.clientX;
        drag.offsetX = 0;
        drag.order = tabOrder();
    });

    document.addEventListener('pointermove', function (event) {
        if (!drag.armed || event.pointerId !== drag.pointerId) return;
        drag.lastClientX = event.clientX;

        if (!drag.active) {
            var dx = Math.abs(event.clientX - drag.startX);
            var dy = Math.abs(event.clientY - drag.startY);
            if (dx < DRAG_THRESHOLD || dx < dy) return;
            beginDrag();
            return;
        }

        event.preventDefault();
        updateDrag(event.clientX);
    });

    function endPointerDrag(event) {
        if (!drag.armed) return;
        if (event && typeof event.pointerId === 'number' && event.pointerId !== drag.pointerId) return;
        finishDrag();
    }

    document.addEventListener('pointerup', endPointerDrag);
    document.addEventListener('pointercancel', cancelDrag);
    window.addEventListener('blur', cancelDrag);

    tabbar.addEventListener('keydown', function (event) {
        if (!event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;

        var target = event.target;
        var button = target && target.closest ? target.closest('.iss-tabbar__tab') : null;
        if (!button) return;

        var rtl = window.getComputedStyle(tabbar).direction === 'rtl';
        var step = ((event.key === 'ArrowRight') !== rtl) ? 1 : -1;
        event.preventDefault();
        moveTabByKeyboard(Number(button.dataset.tabId), step);
    });

    tabbar.addEventListener('click', function (event) {
        if (Date.now() < drag.suppressClickUntil) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }
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
        } else if (message.type === 'languageChanged') {
            handleLanguageChange();
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
            updateTabStripNav();
            loadActiveFrame();
            reloadPbxFramesAfterLanguageChange();
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
