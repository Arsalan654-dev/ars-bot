// ============================================================
//  Boot layer — preloader, custom cursor, header scroll
//  behaviour, mobile menu, hover shuffle, --vh fix.
// ============================================================

(function () {
  'use strict';

  const qs = (sel, ctx) => (ctx || document).querySelector(sel);
  const qsa = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));
  const html = document.documentElement;

  // ---------------------------------------------------------
  //  Mobile viewport height fix (100svh fallback)
  // ---------------------------------------------------------
  function setVH() {
    html.style.setProperty('--vh', window.innerHeight * 0.01 + 'px');
  }
  setVH();
  window.addEventListener('resize', setVH);

  // ---------------------------------------------------------
  //  Custom cursor — lerped follow, grows over targets
  // ---------------------------------------------------------
  const Cursor = {
    el: null,
    x: -100,
    y: -100,
    tx: -100,
    ty: -100,

    init() {
      if (window.matchMedia('(hover: none), (pointer: coarse)').matches) return;
      this.el = document.createElement('div');
      this.el.className = 'c-cursor -hidden';
      document.body.appendChild(this.el);

      window.addEventListener('mousemove', (e) => {
        this.tx = e.clientX;
        this.ty = e.clientY;
        this.el.classList.remove('-hidden');
      });

      document.addEventListener('mouseleave', () => this.el.classList.add('-hidden'));

      // Grow over interactive targets
      document.addEventListener('mouseover', (e) => {
        const target = e.target.closest('a, button, [data-cursor="hover"], label, select');
        this.el.classList.toggle('-active', !!target);
      });

      this.render();
    },

    render() {
      this.x += (this.tx - this.x) * 0.18;
      this.y += (this.ty - this.y) * 0.18;
      const size = this.el.offsetWidth;
      this.el.style.transform =
        'translate3d(' + (this.x - size / 2) + 'px,' + (this.y - size / 2) + 'px,0)';
      requestAnimationFrame(() => this.render());
    },
  };

  // ---------------------------------------------------------
  //  Header — hide on scroll down, show on scroll up,
  //  blend-mode difference while over the hero
  // ---------------------------------------------------------
  const Header = {
    lastY: 0,

    init() {
      window.addEventListener('scroll', () => this.onScroll(), { passive: true });
      this.onScroll();
    },

    onScroll() {
      const y = window.scrollY || 0;
      if (y > 60 && y > this.lastY) {
        html.classList.add('is-scrolling-down');
        html.classList.remove('is-scrolling-up');
      } else if (y < this.lastY) {
        html.classList.add('is-scrolling-up');
        html.classList.remove('is-scrolling-down');
      }
      if (y <= 60) {
        html.classList.remove('is-scrolling-down', 'is-scrolling-up');
      }
      this.lastY = y;
    },
  };

  // ---------------------------------------------------------
  //  Mobile menu
  // ---------------------------------------------------------
  const Menu = {
    toggler: null,

    init() {
      this.toggler = qs('.c-header_menu-toggler');
      if (!this.toggler) return;

      // Stagger delays for menu links
      qsa('.c-menu_nav_link').forEach((link, i) => {
        link.classList.add('is-delay-' + (i + 1));
      });

      this.toggler.addEventListener('click', () => this.toggle());

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && html.classList.contains('has-menu-opened')) this.close();
      });
    },

    toggle() {
      const opened = html.classList.toggle('has-menu-opened');
      this.toggler.setAttribute('aria-expanded', String(opened));
      this.toggler.textContent = opened
        ? this.toggler.dataset.headerCloseLabel || 'Close'
        : this.toggler.dataset.headerOpenLabel || 'Menu';
      document.body.style.overflow = opened ? 'hidden' : '';
    },

    close() {
      if (!html.classList.contains('has-menu-opened')) return;
      this.toggle();
    },
  };

  // ---------------------------------------------------------
  //  Hover shuffle — letters cascade through random glyphs
  // ---------------------------------------------------------
  const CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

  function shuffleHover(el) {
    const original = el.textContent;
    let timer = null;

    el.addEventListener('mouseenter', () => {
      let frame = 0;
      const total = 8;
      clearInterval(timer);
      timer = setInterval(() => {
        frame++;
        el.textContent = original
          .split('')
          .map((ch, i) => {
            if (ch === ' ') return ch;
            if (i < (frame / total) * original.length) return original[i];
            return CHARS[Math.floor(Math.random() * CHARS.length)];
          })
          .join('');
        if (frame >= total) {
          clearInterval(timer);
          el.textContent = original;
        }
      }, 28);
    });
  }

  const Shuffle = {
    init() {
      qsa('[data-hover-shuffle]').forEach((el) => shuffleHover(el));
    },
  };

  // ---------------------------------------------------------
  //  Preloader — rolling word counters, then reveal app
  // ---------------------------------------------------------
  const Preloader = {
    COLUMNS: [
      ['AI', 'AI Assistant', 'AI Assistant &', 'AI Assistant & CRM'],
      ['Built', 'Built for', 'Built for modern', 'Built for modern business'],
    ],

    bindTilt() {
      const logo = qs('.c-preloader_logo');
      const wrap = qs('.c-preloader');
      if (!logo || !wrap) return;
      wrap.style.perspective = '900px';

      window.addEventListener(
        'pointermove',
        (e) => {
          const x = (e.clientX / window.innerWidth) * 2 - 1;
          const y = (e.clientY / window.innerHeight) * 2 - 1;
          logo.style.setProperty('--pry', (x * 14).toFixed(2) + 'deg');
          logo.style.setProperty('--prx', (-y * 10).toFixed(2) + 'deg');
        },
        { passive: true }
      );
    },

    run(done) {
      const colEls = qsa('.c-preloader_content > div');
      this.bindTilt();
      if (!colEls.length) return done();

      let round = 0;
      const maxRounds = Math.max(
        this.COLUMNS[0].length,
        this.COLUMNS[1].length
      );

      const tick = () => {
        colEls.forEach((col, ci) => {
          const words = this.COLUMNS[ci];
          const idx = Math.min(round, words.length - 1);

          const current = col.querySelector('span.-in');
          const next = document.createElement('span');
          next.textContent = words[idx];
          col.appendChild(next);

          // force reflow then slide in
          void next.offsetHeight;
          next.classList.add('-in');

          if (current) {
            current.classList.remove('-in');
            current.classList.add('-out');
            setTimeout(() => current.remove(), 700);
          }
        });

        round++;
        if (round <= maxRounds) {
          setTimeout(tick, round === maxRounds ? 900 : 420);
        } else {
          setTimeout(done, 700);
        }
      };

      setTimeout(tick, 400);
    },
  };

  // ---------------------------------------------------------
  //  Scroll hint — pehli interaction par fade out
  // ---------------------------------------------------------
  const ScrollHint = {
    init() {
      const hint = qs('.c-scroll-hint');
      if (!hint) return;
      const hide = () => {
        hint.style.transition = 'opacity .5s ' + 'cubic-bezier(0.215, 0.61, 0.355, 1)';
        hint.style.opacity = '0';
        window.removeEventListener('wheel', hide);
        window.removeEventListener('touchmove', hide);
        window.removeEventListener('pointerdown', hide);
      };
      window.addEventListener('wheel', hide, { passive: true, once: true });
      window.addEventListener('touchmove', hide, { passive: true, once: true });
      window.addEventListener('pointerdown', hide, { once: true });
    },
  };

  // ---------------------------------------------------------
  //  Boot
  // ---------------------------------------------------------
  function boot() {
    Cursor.init();
    Header.init();
    Menu.init();
    Shuffle.init();
    ScrollHint.init();

    const reveal = () => {
      html.classList.remove('is-first-loading');
      html.classList.add('is-ready');
      // Let the fade start before the app mounts
      setTimeout(() => {
        document.dispatchEvent(new CustomEvent('lisa:boot'));
      }, 150);
    };

    if (html.classList.contains('is-first-loading')) {
      Preloader.run(reveal);
    } else {
      reveal();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
