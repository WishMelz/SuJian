/*!
 * SuJian（素笺）前台交互脚本
 * 搜索弹窗、外观菜单（浅色 / 深色 / 跟随系统）、移动端抽屉导航、返回顶部、
 * 顶栏滚动状态、文章页返回列表与评论反垃圾令牌。
 * 不依赖任何框架，使用 ES5 语法以兼容较旧的移动端浏览器。
 */
(function () {
    'use strict';

    var doc = document;
    var root = doc.documentElement;
    var body = doc.body;

    var STORAGE_KEY = 'sujian-color-scheme';
    var LEGACY_STORAGE_KEY = 'wish-color-scheme';
    var LIST_STORAGE_KEY = 'sujian-last-list';
    var THEME_COLORS = { light: '#f8f6f1', dark: '#171614' };
    var THEME_LABELS = { light: '浅色', dark: '深色', system: '跟随系统' };
    var CLOSE_ANIMATION_MS = 200;
    var OPEN_SETTLE_MS = 260;

    /* ---------- 基础工具 ---------- */

    function toArray(list) {
        return Array.prototype.slice.call(list || []);
    }

    function matches(element, selector) {
        var fn = element.matches || element.msMatchesSelector || element.webkitMatchesSelector;
        return fn ? fn.call(element, selector) : false;
    }

    function closest(element, selector) {
        while (element && element.nodeType === 1) {
            if (matches(element, selector)) return element;
            element = element.parentElement;
        }
        return null;
    }

    function media(query) {
        return window.matchMedia ? window.matchMedia(query) : null;
    }

    function onMediaChange(mql, handler) {
        if (!mql) return;
        if (mql.addEventListener) mql.addEventListener('change', handler);
        else if (mql.addListener) mql.addListener(handler);
    }

    var supportsPassive = (function () {
        var supported = false;
        try {
            var options = Object.defineProperty({}, 'passive', {
                get: function () { supported = true; return true; }
            });
            window.addEventListener('sujian-passive-test', null, options);
            window.removeEventListener('sujian-passive-test', null, options);
        } catch (error) { /* 不支持 options 对象 */ }
        return supported;
    })();

    function listen(target, type, handler, passive) {
        target.addEventListener(type, handler, supportsPassive ? { passive: !!passive } : false);
    }

    var raf = window.requestAnimationFrame
        ? function (callback) { return window.requestAnimationFrame(callback); }
        : function (callback) { return window.setTimeout(callback, 16); };

    var reducedMotionQuery = media('(prefers-reduced-motion: reduce)');
    function prefersReducedMotion() {
        return !!(reducedMotionQuery && reducedMotionQuery.matches);
    }

    function focusElement(element) {
        if (!element || !element.focus) return;
        try {
            element.focus({ preventScroll: true });
        } catch (error) {
            element.focus();
        }
    }

    function isVisible(element) {
        return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
    }

    function isEditable(element) {
        if (!element || element.nodeType !== 1) return false;
        var tag = element.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || element.isContentEditable) return true;
        return !!closest(element, '[contenteditable]:not([contenteditable="false"]), [role="textbox"]');
    }

    function sameOrigin(url) {
        var link = doc.createElement('a');
        link.href = url;
        return link.protocol === window.location.protocol && link.host === window.location.host;
    }

    function storageGet(storage, key) {
        try { return window[storage].getItem(key); } catch (error) { return null; }
    }

    function storageSet(storage, key, value) {
        try {
            if (value === null) window[storage].removeItem(key);
            else window[storage].setItem(key, value);
        } catch (error) { /* 隐私模式或禁用存储时忽略 */ }
    }

    /* ---------- 外观（浅色 / 深色 / 跟随系统） ---------- */

    var systemDarkQuery = media('(prefers-color-scheme: dark)');
    var themeColorMeta = doc.querySelector('meta[name="theme-color"]');
    var themeSwitch = doc.querySelector('[data-theme-switch]');
    var themeTrigger = themeSwitch ? themeSwitch.querySelector('[data-theme-trigger]') : null;
    var themeMenu = themeSwitch ? themeSwitch.querySelector('[role="menu"]') : null;
    var themeOptions = themeMenu ? toArray(themeMenu.querySelectorAll('[data-theme-value]')) : [];

    function normalizePreference(value) {
        return value === 'light' || value === 'dark' ? value : 'system';
    }

    function readPreference() {
        var stored = storageGet('localStorage', STORAGE_KEY) || storageGet('localStorage', LEGACY_STORAGE_KEY);
        return stored === null ? normalizePreference(root.getAttribute('data-theme-pref')) : normalizePreference(stored);
    }

    var themePreference = readPreference();

    function resolveTheme(preference) {
        if (preference !== 'system') return preference;
        return systemDarkQuery && systemDarkQuery.matches ? 'dark' : 'light';
    }

    function syncThemeControls(preference) {
        themeOptions.forEach(function (option) {
            option.setAttribute('aria-checked', String(option.getAttribute('data-theme-value') === preference));
        });
        if (themeTrigger) {
            themeTrigger.setAttribute('aria-label', '选择外观，当前：' + THEME_LABELS[preference]);
        }
    }

    function applyTheme(preference) {
        var resolved = resolveTheme(preference);
        var changed = root.getAttribute('data-theme') !== resolved;
        if (changed) {
            // 切换瞬间禁用过渡，避免不同元素按不同速度变色产生错色帧。
            root.classList.add('theme-switching');
        }
        root.setAttribute('data-theme', resolved);
        root.setAttribute('data-theme-pref', preference);
        if (themeColorMeta) themeColorMeta.setAttribute('content', THEME_COLORS[resolved]);
        syncThemeControls(preference);
        if (changed) {
            raf(function () {
                raf(function () { root.classList.remove('theme-switching'); });
            });
        }
    }

    function setThemePreference(preference) {
        themePreference = normalizePreference(preference);
        storageSet('localStorage', STORAGE_KEY, themePreference === 'system' ? null : themePreference);
        storageSet('localStorage', LEGACY_STORAGE_KEY, null);
        applyTheme(themePreference);
    }

    applyTheme(themePreference);

    onMediaChange(systemDarkQuery, function () {
        if (themePreference === 'system') applyTheme('system');
    });

    window.addEventListener('storage', function (event) {
        if (!event.key || event.key === STORAGE_KEY || event.key === LEGACY_STORAGE_KEY) {
            themePreference = readPreference();
            applyTheme(themePreference);
        }
    });

    function isThemeMenuOpen() {
        return !!themeMenu && !themeMenu.hasAttribute('hidden');
    }

    function openThemeMenu(focusPosition) {
        if (!themeMenu || !themeTrigger) return;
        themeMenu.removeAttribute('hidden');
        themeTrigger.setAttribute('aria-expanded', 'true');
        var target;
        if (focusPosition === 'first') target = themeOptions[0];
        else if (focusPosition === 'last') target = themeOptions[themeOptions.length - 1];
        else {
            target = themeOptions.filter(function (option) {
                return option.getAttribute('aria-checked') === 'true';
            })[0] || themeOptions[0];
        }
        focusElement(target);
    }

    function closeThemeMenu(restoreFocus) {
        if (!isThemeMenuOpen()) return;
        themeMenu.setAttribute('hidden', '');
        themeTrigger.setAttribute('aria-expanded', 'false');
        if (restoreFocus) focusElement(themeTrigger);
    }

    if (themeTrigger && themeMenu) {
        themeTrigger.addEventListener('click', function () {
            if (isThemeMenuOpen()) closeThemeMenu(false);
            else openThemeMenu();
        });

        themeTrigger.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'Down') {
                event.preventDefault();
                openThemeMenu('first');
            } else if (event.key === 'ArrowUp' || event.key === 'Up') {
                event.preventDefault();
                openThemeMenu('last');
            }
        });

        themeMenu.addEventListener('keydown', function (event) {
            var index = themeOptions.indexOf(doc.activeElement);
            var count = themeOptions.length;
            switch (event.key) {
                case 'ArrowDown':
                case 'Down':
                    event.preventDefault();
                    focusElement(themeOptions[(index + 1 + count) % count]);
                    break;
                case 'ArrowUp':
                case 'Up':
                    event.preventDefault();
                    focusElement(themeOptions[(index - 1 + count) % count]);
                    break;
                case 'Home':
                    event.preventDefault();
                    focusElement(themeOptions[0]);
                    break;
                case 'End':
                    event.preventDefault();
                    focusElement(themeOptions[count - 1]);
                    break;
                case 'Tab':
                    closeThemeMenu(false);
                    break;
                default:
                    break;
            }
        });

        themeOptions.forEach(function (option) {
            option.addEventListener('click', function () {
                setThemePreference(option.getAttribute('data-theme-value'));
                closeThemeMenu(true);
            });
        });

        doc.addEventListener('click', function (event) {
            if (isThemeMenuOpen() && !themeSwitch.contains(event.target)) closeThemeMenu(false);
        });
    }

    /* ---------- 弹层（搜索弹窗、移动端抽屉） ---------- */

    var activeModal = null;
    var lastFocusedElement = null;
    var inertedElements = [];
    var supportsInert = typeof HTMLElement !== 'undefined' && 'inert' in HTMLElement.prototype;
    var scrollLockState = null;

    function modalPanel(modal) {
        return modal.querySelector('[role="dialog"]') || modal;
    }

    function modalTriggers(modal) {
        return toArray(doc.querySelectorAll('[data-modal-open="' + modal.id + '"]'));
    }

    function lockScroll() {
        if (scrollLockState) return;
        var scrollbarWidth = window.innerWidth - root.clientWidth;
        scrollLockState = { paddingRight: body.style.paddingRight };
        if (scrollbarWidth > 0) {
            var currentPadding = parseFloat(window.getComputedStyle(body).paddingRight) || 0;
            body.style.paddingRight = (currentPadding + scrollbarWidth) + 'px';
        }
        root.classList.add('is-scroll-locked');
    }

    function unlockScroll() {
        if (!scrollLockState) return;
        body.style.paddingRight = scrollLockState.paddingRight;
        root.classList.remove('is-scroll-locked');
        scrollLockState = null;
    }

    function setBackgroundInert(modal, inert) {
        if (!supportsInert) return;
        if (inert) {
            inertedElements = toArray(body.children).filter(function (element) {
                return element !== modal && element.tagName !== 'SCRIPT' && !element.inert;
            });
            inertedElements.forEach(function (element) { element.inert = true; });
        } else {
            inertedElements.forEach(function (element) { element.inert = false; });
            inertedElements = [];
        }
    }

    function focusableElements(container) {
        return toArray(container.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )).filter(isVisible);
    }

    function openModal(modal, trigger, deferFocus) {
        if (!modal || activeModal === modal) return;
        if (activeModal) closeModal(activeModal, false, true);
        closeThemeMenu(false);

        lastFocusedElement = trigger || doc.activeElement;
        var openToken = modal.sujianToken = (modal.sujianToken || 0) + 1;
        modal.classList.remove('is-closing', 'is-settled');
        modal.removeAttribute('hidden');
        modal.classList.add('is-open');
        activeModal = modal;
        // 入场动画结束后锁定最终状态；即使动画未推进（如节能模式、后台标签页），面板也会落位可见。
        window.setTimeout(function () {
            if (modal.sujianToken === openToken && activeModal === modal) modal.classList.add('is-settled');
        }, OPEN_SETTLE_MS);

        lockScroll();
        setBackgroundInert(modal, true);
        modalTriggers(modal).forEach(function (button) { button.setAttribute('aria-expanded', 'true'); });

        // 点击/轻触时同步聚焦以呼出 iOS 软键盘；快捷键打开时延后聚焦，避免触发键字符落入输入框。
        var moveFocus = function () {
            if (activeModal !== modal) return;
            var target = modal.querySelector('[data-autofocus]') || focusableElements(modalPanel(modal))[0] || modalPanel(modal);
            focusElement(target);
            if (target && target.select && target.value) target.select();
        };
        if (deferFocus) window.setTimeout(moveFocus, 0);
        else moveFocus();
    }

    function closeModal(modal, restoreFocus, immediate) {
        if (!modal || modal.hasAttribute('hidden')) return;
        var token = modal.sujianToken = (modal.sujianToken || 0) + 1;

        modal.classList.remove('is-open', 'is-settled');
        if (activeModal === modal) activeModal = null;
        setBackgroundInert(modal, false);
        unlockScroll();
        modalTriggers(modal).forEach(function (button) { button.setAttribute('aria-expanded', 'false'); });

        var finish = function () {
            if (modal.sujianToken !== token) return; // 关闭动画期间又被重新打开
            modal.classList.remove('is-closing');
            modal.setAttribute('hidden', '');
        };

        if (immediate || prefersReducedMotion()) {
            finish();
        } else {
            modal.classList.add('is-closing');
            window.setTimeout(finish, CLOSE_ANIMATION_MS);
        }

        if (restoreFocus && lastFocusedElement && doc.contains(lastFocusedElement)) {
            focusElement(lastFocusedElement);
        }
        lastFocusedElement = null;
    }

    toArray(doc.querySelectorAll('[data-modal-open]')).forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            var modal = doc.getElementById(trigger.getAttribute('data-modal-open'));
            if (!modal) return;
            if (activeModal === modal) closeModal(modal, true);
            else openModal(modal, trigger);
        });
    });

    toArray(doc.querySelectorAll('.sj-modal')).forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (closest(event.target, '[data-modal-close]')) {
                event.preventDefault();
                closeModal(modal, true);
            } else if (closest(event.target, '[data-modal-link]')) {
                // 抽屉内导航链接：先收起抽屉，再交给浏览器跳转。
                closeModal(modal, false, true);
            }
        });

        // 旧版 iOS 会让遮罩上的滑动穿透到页面，这里阻止遮罩本身的滚动。
        var backdrop = modal.querySelector('.sj-backdrop');
        if (backdrop) {
            listen(backdrop, 'touchmove', function (event) { event.preventDefault(); }, false);
        }
    });

    var searchModal = doc.getElementById('sujian-search');
    var drawerModal = doc.getElementById('sujian-drawer');
    var searchTrigger = doc.querySelector('[data-modal-open="sujian-search"]');

    // macOS / iOS 上把快捷键提示显示为 ⌘K。
    if (/Mac|iPhone|iPad|iPod/.test(navigator.platform || navigator.userAgent || '')) {
        toArray(doc.querySelectorAll('[data-shortcut-mod]')).forEach(function (element) {
            element.textContent = '⌘K';
        });
    }

    var desktopQuery = media('(min-width: 721px)');
    onMediaChange(desktopQuery, function (event) {
        if (event.matches && activeModal === drawerModal) closeModal(drawerModal, false, true);
    });

    /* ---------- 全局键盘：Esc、Tab 焦点锁定、/ 与 Ctrl/⌘+K 打开搜索 ---------- */

    doc.addEventListener('keydown', function (event) {
        var key = event.key;

        if (key === 'Escape' || key === 'Esc') {
            if (activeModal) {
                event.preventDefault();
                closeModal(activeModal, true);
            } else if (isThemeMenuOpen()) {
                event.preventDefault();
                closeThemeMenu(true);
            }
            return;
        }

        if (key === 'Tab' && activeModal) {
            var focusables = focusableElements(modalPanel(activeModal));
            if (!focusables.length) {
                event.preventDefault();
                return;
            }
            var first = focusables[0];
            var last = focusables[focusables.length - 1];
            var current = doc.activeElement;
            if (event.shiftKey && (current === first || !activeModal.contains(current))) {
                event.preventDefault();
                focusElement(last);
            } else if (!event.shiftKey && (current === last || !activeModal.contains(current))) {
                event.preventDefault();
                focusElement(first);
            }
            return;
        }

        if (!searchModal || event.defaultPrevented || event.isComposing || event.keyCode === 229) return;

        var isShortcutK = (event.ctrlKey || event.metaKey) && !event.altKey && !event.shiftKey
            && (key === 'k' || key === 'K');
        var isSlash = key === '/' && !event.ctrlKey && !event.metaKey && !event.altKey;
        if (!isShortcutK && !isSlash) return;
        if (activeModal) return;
        if (isSlash && isEditable(event.target)) return;

        event.preventDefault();
        event.stopPropagation();
        var searchInput = searchModal.querySelector('[data-autofocus]');
        var prevSearchValue = searchInput ? searchInput.value : '';
        openModal(searchModal, searchTrigger, true);
        if (isSlash && searchInput) {
            window.setTimeout(function () {
                if (searchInput.value === prevSearchValue + '/') {
                    searchInput.value = prevSearchValue;
                }
            }, 0);
        }
    });

    /* ---------- 顶栏滚动状态与返回顶部 ---------- */

    var header = doc.getElementById('site-header');
    var backToTop = doc.querySelector('[data-back-to-top]');
    var scrollTicking = false;
    var backToTopVisible = false;

    function updateScrollState() {
        scrollTicking = false;
        var y = window.pageYOffset || root.scrollTop || 0;
        if (header) header.classList.toggle('is-scrolled', y > 8);
        if (backToTop) {
            var visible = y > 400;
            if (visible !== backToTopVisible) {
                backToTopVisible = visible;
                backToTop.classList.toggle('is-visible', visible);
            }
        }
    }

    function scheduleScrollUpdate() {
        if (scrollTicking) return;
        scrollTicking = true;
        raf(updateScrollState);
    }

    listen(window, 'scroll', scheduleScrollUpdate, true);
    listen(window, 'resize', scheduleScrollUpdate, true);
    updateScrollState();

    if (backToTop) {
        backToTop.addEventListener('click', function (event) {
            var smooth = !prefersReducedMotion() && 'scrollBehavior' in root.style;
            if (smooth) window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
            else window.scrollTo(0, 0);
            // 键盘触发时（event.detail 为 0）把焦点移回正文，方便读屏与键盘用户继续阅读；
            // 鼠标/触摸点击时不移动焦点，避免旧版 Safari 因聚焦而打断平滑滚动。
            if (event.detail === 0) focusElement(doc.getElementById('main'));
        });
    }

    /* ---------- 文章页“返回文章列表”：回到进入文章前的列表页 ---------- */

    if (doc.querySelector('[data-list-page]')) {
        storageSet('sessionStorage', LIST_STORAGE_KEY, window.location.href);
    }

    var articleBack = doc.querySelector('[data-article-back]');
    if (articleBack) {
        var lastListUrl = storageGet('sessionStorage', LIST_STORAGE_KEY);
        if (lastListUrl && sameOrigin(lastListUrl)) {
            articleBack.setAttribute('href', lastListUrl);
            articleBack.addEventListener('click', function (event) {
                if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                // 直接从该列表页进入时用浏览器后退，可以恢复原来的滚动位置。
                if (doc.referrer === lastListUrl && window.history.length > 1) {
                    event.preventDefault();
                    window.history.back();
                }
            });
        }
    }

    /* ---------- 外链头像加载失败兜底 ---------- */

    var fallbackImages = toArray(doc.querySelectorAll('img[data-fallback-src]'));
    function applyFallback(img) {
        if (img.getAttribute('data-fallback-applied') === 'true') return;
        var fallback = img.getAttribute('data-fallback-src');
        if (!fallback) return;
        img.setAttribute('data-fallback-applied', 'true');
        img.src = fallback;
    }
    function checkFallbackImages() {
        fallbackImages.forEach(function (img) {
            // SVG 没有固有尺寸时 naturalWidth 可能为 0，不能据此判断加载失败。
            var source = img.currentSrc || img.src || '';
            if (img.complete && img.naturalWidth === 0 && !/\.svg(?:[?#]|$)/i.test(source)) applyFallback(img);
        });
    }
    fallbackImages.forEach(function (img) {
        img.addEventListener('error', function () { applyFallback(img); });
    });
    checkFallbackImages();
    window.addEventListener('load', checkFallbackImages);

    /* ---------- 评论反垃圾令牌：用户产生交互后再写入表单 ---------- */

    var commentForm = doc.querySelector('[data-comment-token]');
    if (commentForm) {
        var commentTokenAdded = false;
        var addCommentToken = function () {
            if (commentTokenAdded) return;
            commentTokenAdded = true;
            var input = doc.createElement('input');
            input.type = 'hidden';
            input.name = '_';
            input.value = commentForm.getAttribute('data-comment-token') || '';
            commentForm.appendChild(input);
            commentForm.removeAttribute('data-comment-token');
        };
        ['scroll', 'mousemove', 'keyup', 'touchstart'].forEach(function (eventName) {
            listen(window, eventName, addCommentToken, eventName !== 'keyup');
        });
        commentForm.addEventListener('submit', addCommentToken);
    }

    /* ---------- 从往返缓存（bfcache）恢复页面时，收起残留的弹层 ---------- */

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            if (activeModal) closeModal(activeModal, false, true);
            closeThemeMenu(false);
            themePreference = readPreference();
            applyTheme(themePreference);
        }
        scheduleScrollUpdate();
    });
})();
