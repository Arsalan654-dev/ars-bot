// ============================================================
//  Pages JS — inner pages (Work / Agency / Careers)
//  • Scroll reveal (IntersectionObserver)
//  • Hero background parallax (scroll + cursor)
//  • Back-to-top pill
//  • Newsletter fake submit (front-end only)
// ============================================================

(function () {
  'use strict';

  const qs = (sel, ctx) => (ctx || document).querySelector(sel);
  const qsa = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));

  // ---------------------------------------------------------
  //  Scroll reveal
  // ---------------------------------------------------------
  const Reveal = {
    init() {
      const targets = qsa('.js-reveal');
      if (!targets.length) return;

      if (!('IntersectionObserver' in window)) {
        targets.forEach((el) => el.classList.add('is-visible'));
        return;
      }

      const io = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-visible');
              io.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.12, rootMargin: '0px 0px -6% 0px' }
      );

      targets.forEach((el, i) => {
        el.style.transitionDelay = (i % 4) * 0.06 + 's';
        io.observe(el);
      });
    },
  };

  // ---------------------------------------------------------
  //  Hero parallax — scroll + cursor depth
  // ---------------------------------------------------------
  const HeroParallax = {
    init() {
      const bg = qs('.c-page-hero__bg');
      if (!bg) return;

      let mx = 0, my = 0, cx = 0, cy = 0;

      window.addEventListener(
        'pointermove',
        (e) => {
          mx = (e.clientX / window.innerWidth) * 2 - 1;
          my = (e.clientY / window.innerHeight) * 2 - 1;
        },
        { passive: true }
      );

      const loop = () => {
        const scrollShift = Math.min(window.scrollY * 0.12, 120);
        cx += (mx - cx) * 0.05;
        cy += (my - cy) * 0.05;
        bg.style.transform =
          'translate3d(' + (cx * 18).toFixed(1) + 'px,' + (cy * 12 - scrollShift).toFixed(1) + 'px,0)';
        requestAnimationFrame(loop);
      };
      loop();
    },
  };

  // ---------------------------------------------------------
  //  Back to top
  // ---------------------------------------------------------
  const BackTop = {
    init() {
      const btn = qs('.c-backtop');
      if (!btn) return;

      const onScroll = () => {
        btn.classList.toggle('is-visible', (window.scrollY || 0) > 700);
      };
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();

      btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    },
  };

  // ---------------------------------------------------------
  //  Newsletter (front-end only)
  // ---------------------------------------------------------
  const Newsletter = {
    init() {
      const form = qs('.c-page-footer__newsletter');
      if (!form) return;

      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const input = form.querySelector('input');
        if (!input || !input.value.trim()) return;
        const btn = form.querySelector('button');
        if (btn) {
          btn.textContent = 'Thanks!';
          btn.disabled = true;
        }
        input.value = '';
        input.placeholder = 'You\u2019re on the list \u2713';
      });
    },
  };

  // ---------------------------------------------------------
  //  Boot
  // ---------------------------------------------------------
  function boot() {
    Reveal.init();
    HeroParallax.init();
    BackTop.init();
    Newsletter.init();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
