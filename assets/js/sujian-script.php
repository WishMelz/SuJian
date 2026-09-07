<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
(function () {
    'use strict';

    var root = document.documentElement;
    var themeButton = document.querySelector('[data-theme-toggle]');
    var searchButtons = document.querySelectorAll('[data-search-toggle]');
    var searchPanel = document.querySelector('[data-search-panel]');
    var menuButton = document.querySelector('[data-menu-toggle]');
    var mobileNav = document.querySelector('[data-mobile-nav]');
    var commentForm = document.querySelector('[data-comment-token]');
    var themeColor = document.querySelector('meta[name="theme-color"]');
    var systemTheme = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var mobileBreakpoint = window.matchMedia ? window.matchMedia('(max-width: 720px)') : null;
    var lastSearchButton = null;

    function getStoredTheme() {
        try {
            return localStorage.getItem('sujian-color-scheme') || localStorage.getItem('wish-color-scheme');
        } catch (error) {
            return null;
        }
    }

    function saveTheme(theme) {
        try {
            localStorage.setItem('sujian-color-scheme', theme);
            localStorage.removeItem('wish-color-scheme');
        } catch (error) {
            // 存储不可用时仍保留本次页面的主题切换结果。
        }
    }

    function setTheme(theme) {
        root.dataset.theme = theme;
        if (themeColor) {
            themeColor.setAttribute('content', theme === 'dark' ? '#09090b' : '#ffffff');
        }
        if (themeButton) {
            themeButton.setAttribute('aria-label', theme === 'dark' ? '切换浅色模式' : '切换深色模式');
            themeButton.setAttribute('aria-pressed', String(theme === 'dark'));
        }
    }

    function setSearch(open, restoreFocus) {
        if (!searchPanel || root.dataset.uiReady === 'true') return;

        searchPanel.toggleAttribute('hidden', !open);
        searchButtons.forEach(function (button) {
            button.setAttribute('aria-expanded', String(open));
            button.setAttribute('aria-label', open ? '关闭搜索' : '打开搜索');
        });

        if (open) {
            setMenu(false, false);
            var input = searchPanel.querySelector('input');
            if (input) input.focus();
        } else if (restoreFocus && lastSearchButton) {
            lastSearchButton.focus();
        }
    }

    function setMenu(open, restoreFocus) {
        if (!mobileNav || !menuButton || root.dataset.uiReady === 'true') return;

        mobileNav.toggleAttribute('hidden', !open);
        menuButton.setAttribute('aria-expanded', String(open));
        menuButton.setAttribute('aria-label', open ? '关闭菜单' : '打开菜单');

        if (open) {
            setSearch(false, false);
            var firstLink = mobileNav.querySelector('a');
            if (firstLink) firstLink.focus();
        } else if (restoreFocus) {
            menuButton.focus();
        }
    }

    var storedTheme = getStoredTheme();
    var preferredTheme = systemTheme && systemTheme.matches ? 'dark' : 'light';
    setTheme(storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : preferredTheme);

    if (systemTheme) {
        var followSystemTheme = function (event) {
            if (root.dataset.uiReady === 'true') return;
            var savedTheme = getStoredTheme();
            if (savedTheme !== 'light' && savedTheme !== 'dark') {
                setTheme(event.matches ? 'dark' : 'light');
            }
        };
        if (systemTheme.addEventListener) {
            systemTheme.addEventListener('change', followSystemTheme);
        } else if (systemTheme.addListener) {
            systemTheme.addListener(followSystemTheme);
        }
    }

    if (themeButton) {
        themeButton.addEventListener('click', function () {
            var next = root.dataset.theme === 'dark' ? 'light' : 'dark';
            saveTheme(next);
            setTheme(next);
        });
    }

    searchButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            lastSearchButton = button;
            setSearch(searchPanel ? searchPanel.hasAttribute('hidden') : false, true);
        });
    });

    document.addEventListener('pointerdown', function (event) {
        if (!searchPanel || searchPanel.hasAttribute('hidden')) return;

        var target = event.target;
        if (searchPanel.contains(target)) return;
        if (target.closest && target.closest('[data-search-toggle]')) return;
        setSearch(false, false);
    });

    if (menuButton && mobileNav) {
        menuButton.addEventListener('click', function () {
            setMenu(mobileNav.hasAttribute('hidden'), true);
        });

        if (mobileBreakpoint) {
            var closeDesktopMenu = function (event) {
                if (!event.matches) setMenu(false, false);
            };
            if (mobileBreakpoint.addEventListener) {
                mobileBreakpoint.addEventListener('change', closeDesktopMenu);
            } else if (mobileBreakpoint.addListener) {
                mobileBreakpoint.addListener(closeDesktopMenu);
            }
            window.addEventListener('resize', function () {
                if (window.innerWidth > 720 && !mobileNav.hasAttribute('hidden')) {
                    setMenu(false, false);
                }
            }, { passive: true });
            closeDesktopMenu(mobileBreakpoint);
        }
    }

    if (commentForm) {
        var commentTokenAdded = false;
        var addCommentToken = function () {
            if (commentTokenAdded) return;

            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_';
            input.value = commentForm.getAttribute('data-comment-token') || '';
            commentForm.appendChild(input);
            commentForm.removeAttribute('data-comment-token');
            commentTokenAdded = true;
        };

        ['scroll', 'mousemove', 'keyup', 'touchstart'].forEach(function (eventName) {
            window.addEventListener(eventName, addCommentToken, { once: true, passive: eventName !== 'keyup' });
        });
        commentForm.addEventListener('submit', addCommentToken);
    }

    document.addEventListener('keydown', function (event) {
        if (root.dataset.uiReady === 'true') return;
        var tagName = document.activeElement ? document.activeElement.tagName : '';
        if (event.key === '/' && !/input|textarea/i.test(tagName)) {
            event.preventDefault();
            lastSearchButton = searchButtons.length ? searchButtons[0] : null;
            setSearch(true, false);
        }

        if (event.key === 'Escape') {
            var searchOpen = searchPanel && !searchPanel.hasAttribute('hidden');
            var menuOpen = mobileNav && !mobileNav.hasAttribute('hidden');
            if (searchOpen) setSearch(false, true);
            if (menuOpen) setMenu(false, true);
        }
    });

    var header = document.getElementById('site-header');
    if (header) {
        var headerUpdatePending = false;
        var updateHeader = function () {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
            headerUpdatePending = false;
        };
        var scheduleHeaderUpdate = function () {
            if (headerUpdatePending) return;
            headerUpdatePending = true;
            window.requestAnimationFrame(updateHeader);
        };
        scheduleHeaderUpdate();
        window.addEventListener('pageshow', scheduleHeaderUpdate);
        window.addEventListener('scroll', scheduleHeaderUpdate, { passive: true });
    }
})();
