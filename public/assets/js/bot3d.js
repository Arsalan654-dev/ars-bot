(function () {
  'use strict';

  const Bot3D = {
    el: null,
    stage: null,
    tx: 0,
    ty: 0,
    cx: 0,
    cy: 0,
    lastMove: 0,
    raf: null,
    running: false,
    talkBoost: 0,
    previousTime: 0,
    reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)'),

    init() {
      if (this.el) return;

      this.el = document.querySelector('.c-bot');
      this.stage = document.querySelector('.c-bot_stage');
      if (!this.el || !this.stage) return;

      window.addEventListener('pointermove', (event) => {
        if (event.pointerType === 'touch') return;

        this.tx = (event.clientX / window.innerWidth) * 2 - 1;
        this.ty = (event.clientY / window.innerHeight) * 2 - 1;
        this.lastMove = performance.now();
      }, { passive: true });

      document.addEventListener('lisa:speaking', () => {
        this.el.classList.add('is-speaking');
        this.talkBoost = 1;
      });
      document.addEventListener('lisa:idle', () => {
        this.el.classList.remove('is-speaking');
      });
      document.addEventListener('lisa:compact', (event) => {
        this.el.classList.toggle('is-compact', Boolean(event.detail && event.detail.compact));
      });
      document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
          this.stop();
        } else {
          this.start();
        }
      });

      this.lastMove = performance.now() - 4000;
      this.start();
    },

    start() {
      if (this.running || this.reducedMotion.matches) return;

      this.running = true;
      this.previousTime = performance.now();
      this.raf = requestAnimationFrame(() => this.loop());
    },

    stop() {
      this.running = false;
      if (this.raf !== null) cancelAnimationFrame(this.raf);
    },

    loop() {
      if (!this.running) return;

      const now = performance.now();
      const deltaSeconds = Math.min((now - this.previousTime) / 1000, 0.05);
      const seconds = now / 1000;
      this.previousTime = now;

      let targetX = this.tx;
      let targetY = this.ty;
      if (now - this.lastMove > 2800) {
        targetX = Math.sin(seconds * 0.42) * 0.32;
        targetY = Math.sin(seconds * 0.31 + 1.2) * 0.22;
      }

      if (this.talkBoost > 0.001) {
        targetX += Math.sin(seconds * 18) * 0.06 * this.talkBoost;
        targetY += Math.cos(seconds * 21) * 0.05 * this.talkBoost;
        this.talkBoost *= Math.pow(0.02, deltaSeconds);
      }

      this.cx += (targetX - this.cx) * 0.055;
      this.cy += (targetY - this.cy) * 0.055;

      this.stage.style.setProperty('--ry', `${(this.cx * 16).toFixed(2)}deg`);
      this.stage.style.setProperty('--rx', `${(-this.cy * 11).toFixed(2)}deg`);
      this.stage.style.setProperty('--tx', `${(this.cx * 30).toFixed(1)}px`);
      this.stage.style.setProperty('--ty', `${(this.cy * 22).toFixed(1)}px`);

      this.raf = requestAnimationFrame(() => this.loop());
    },
  };

  window.Bot3D = Bot3D;
  document.addEventListener('lisa:boot', () => Bot3D.init(), { once: true });
})();
