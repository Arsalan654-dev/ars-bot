/**
 * ARS Bot widget — injected by /widget.js
 * Adds:
 *   1. floating chat launcher + iframe panel
 *   2. optional nav link to the standalone bot page (data-nav-link="true")
 */
(function () {
    'use strict';

    var CONFIG = {!! json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};

    if (!CONFIG.valid || window.__lisaWidgetLoaded) { return; }
    window.__lisaWidgetLoaded = true;

    var script = document.currentScript || (function () {
        var scripts = document.getElementsByTagName('script');
        return scripts[scripts.length - 1];
    })();

    var navLinkEnabled = script.getAttribute('data-nav-link') === 'true';
    var navLabel = script.getAttribute('data-nav-label') || CONFIG.name;
    var accent = script.getAttribute('data-color') || CONFIG.color || '#000000';

    // ---------------- Nav link ----------------
    if (navLinkEnabled && CONFIG.pageUrl) {
        var injectNav = function () {
            var nav = document.querySelector('nav ul, nav, header ul, header');
            if (!nav) { return; }
            var link = document.createElement('a');
            link.href = CONFIG.pageUrl;
            link.textContent = navLabel;
            link.setAttribute('data-lisa-nav-link', '');
            link.style.cssText = 'display:inline-block;margin-left:16px;font:inherit;color:inherit;text-decoration:none;';
            link.addEventListener('mouseenter', function () { link.style.textDecoration = 'underline'; });
            link.addEventListener('mouseleave', function () { link.style.textDecoration = 'none'; });
            nav.appendChild(link);
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', injectNav);
        } else {
            injectNav();
        }
    }

    // ---------------- Styles ----------------
    var css = ''
        + '#lisa-widget-launcher{position:fixed;right:22px;bottom:22px;z-index:2147483000;width:58px;height:58px;border-radius:50%;border:0;cursor:pointer;background:' + accent + ';color:#fff;box-shadow:0 12px 30px -8px rgba(0,0,0,.45);transition:transform .25s cubic-bezier(.215,.61,.355,1);display:flex;align-items:center;justify-content:center}'
        + '#lisa-widget-launcher:hover{transform:translateY(-3px) scale(1.05)}'
        + '#lisa-widget-launcher svg{width:26px;height:26px;fill:currentColor}'
        + '#lisa-widget-panel{position:fixed;right:22px;bottom:94px;z-index:2147483000;width:min(400px,calc(100vw - 44px));height:min(620px,calc(100vh - 130px));border:0;border-radius:18px;box-shadow:0 30px 70px -20px rgba(0,0,0,.5);overflow:hidden;opacity:0;pointer-events:none;transform:translateY(14px) scale(.98);transition:opacity .28s cubic-bezier(.215,.61,.355,1),transform .28s cubic-bezier(.215,.61,.355,1);background:#fff}'
        + '#lisa-widget-panel.is-open{opacity:1;pointer-events:auto;transform:none}'
        + '#lisa-widget-panel iframe{width:100%;height:100%;border:0;display:block}'
        + '#lisa-widget-badge{position:absolute;top:-4px;right:-4px;width:14px;height:14px;border-radius:50%;background:#22c55e;border:2px solid #fff}';

    var style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);

    // ---------------- Launcher ----------------
    var launcher = document.createElement('button');
    launcher.id = 'lisa-widget-launcher';
    launcher.setAttribute('aria-label', 'Chat with ' + CONFIG.name);
    launcher.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 3C6.5 3 2 6.9 2 11.7c0 2.7 1.5 5.1 3.8 6.7-.2 1-.8 2.2-1.8 3.1 1.8-.2 3.4-.9 4.6-1.7 1 .3 2.2.5 3.4.5 5.5 0 10-3.9 10-8.6S17.5 3 12 3z"/></svg>';
    document.body.appendChild(launcher);

    // ---------------- Panel ----------------
    var panel = document.createElement('div');
    panel.id = 'lisa-widget-panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', CONFIG.name + ' chat');
    document.body.appendChild(panel);

    var iframeLoaded = false;

    var toggle = function () {
        var open = panel.classList.toggle('is-open');
        if (open && !iframeLoaded && CONFIG.embedUrl) {
            var iframe = document.createElement('iframe');
            iframe.src = CONFIG.embedUrl + '?widget=1';
            iframe.setAttribute('title', CONFIG.name);
            iframe.setAttribute('allow', 'microphone; autoplay');
            panel.appendChild(iframe);
            iframeLoaded = true;
        }
        launcher.innerHTML = open
            ? '<svg viewBox="0 0 24 24"><path d="M18.3 5.7 12 12l6.3 6.3-1.4 1.4L10.6 13.4 4.3 19.7 2.9 18.3 9.2 12 2.9 5.7 4.3 4.3l6.3 6.3 6.3-6.3z"/></svg>'
            : '<svg viewBox="0 0 24 24"><path d="M12 3C6.5 3 2 6.9 2 11.7c0 2.7 1.5 5.1 3.8 6.7-.2 1-.8 2.2-1.8 3.1 1.8-.2 3.4-.9 4.6-1.7 1 .3 2.2.5 3.4.5 5.5 0 10-3.9 10-8.6S17.5 3 12 3z"/></svg>';
    };

    launcher.addEventListener('click', toggle);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && panel.classList.contains('is-open')) {
            toggle();
        }
    });
})();
