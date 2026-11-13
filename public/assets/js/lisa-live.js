// ============================================================
//  ARS Bot Live - API-driven chat engine for the public bot page
//  Reuses: typewriter dialog, voice (Web Speech), ambient pad,
//  canvas visualizer, bot sprite (bot3d.js).
// ============================================================

(function () {
  'use strict';

  const CONFIG = window.ARS_BOT_CONFIG || window.LISA_CONFIG || {};
  const qs = (sel, ctx) => (ctx || document).querySelector(sel);
  const qsa = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));

  const CHARS = 'abcdefghijklmnopqrstuvwxyz';

  // ---------------------------------------------------------
  //  Session id (persist per visitor)
  // ---------------------------------------------------------
  function sessionId() {
    let id = sessionStorage.getItem('lisa-session');
    if (!id) {
      id = 's_' + Math.random().toString(36).slice(2) + Date.now().toString(36);
      sessionStorage.setItem('lisa-session', id);
    }
    return id;
  }

  // ---------------------------------------------------------
  //  Voice
  // ---------------------------------------------------------
  const Voice = {
    enabled: false,
    voice: null,
    synth: window.speechSynthesis || null,

    init() {
      if (!this.synth) return;
      const pick = () => {
        const voices = this.synth.getVoices();
        if (!voices.length) return;
        this.voice =
          voices.find((v) => /samantha|zira|aria|google uk english female|google us english/i.test(v.name) && /en/i.test(v.lang)) ||
          voices.find((v) => /^en/i.test(v.lang)) ||
          voices[0];
      };
      pick();
      this.synth.onvoiceschanged = pick;
    },

    speak(text, onEnd) {
      if (!this.synth || !this.enabled || !this.voice) {
        if (onEnd) onEnd();
        return;
      }
      this.stop();
      const u = new SpeechSynthesisUtterance(text);
      u.voice = this.voice;
      u.rate = 1.02;
      u.pitch = 1.05;
      u.onend = () => onEnd && onEnd();
      u.onerror = () => onEnd && onEnd();
      this.synth.speak(u);
    },

    stop() {
      if (this.synth) this.synth.cancel();
    },

    toggle() {
      this.enabled = !this.enabled;
      if (!this.enabled) this.stop();
      return this.enabled;
    },
  };

  // ---------------------------------------------------------
  //  Ambient pad
  // ---------------------------------------------------------
  const Ambient = {
    ctx: null,
    gain: null,
    started: false,
    muted: true,

    ensure() {
      if (this.ctx) return;
      const AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) return;
      this.ctx = new AC();
      this.gain = this.ctx.createGain();
      this.gain.gain.value = 0;
      this.gain.connect(this.ctx.destination);

      [55, 82.5, 110].forEach((freq, i) => {
        const osc = this.ctx.createOscillator();
        osc.type = 'sine';
        osc.frequency.value = freq;
        const g = this.ctx.createGain();
        g.gain.value = 0.05 / (i + 1);
        osc.connect(g).connect(this.gain);
        osc.start();
      });
    },

    start() {
      this.ensure();
      if (!this.ctx) return;
      if (this.ctx.state === 'suspended') this.ctx.resume();
      if (!this.muted) this.gain.gain.linearRampToValueAtTime(0.4, this.ctx.currentTime + 2);
      this.started = true;
    },

    kick() {
      if (!this.ctx || this.muted) return;
      const t = this.ctx.currentTime;
      this.gain.gain.cancelScheduledValues(t);
      this.gain.gain.linearRampToValueAtTime(0.08, t + 0.1);
      this.gain.gain.linearRampToValueAtTime(0.4, t + 2.4);
    },

    setMuted(muted) {
      this.muted = muted;
      if (!this.ctx) return;
      const t = this.ctx.currentTime;
      this.gain.gain.cancelScheduledValues(t);
      this.gain.gain.linearRampToValueAtTime(muted ? 0 : 0.4, t + 0.4);
    },
  };

  // ---------------------------------------------------------
  //  Visualizer (reactive orb)
  // ---------------------------------------------------------
  const Visualizer = {
    canvas: null,
    ctx: null,
    w: 0,
    h: 0,
    dpr: 1,
    t: 0,
    energy: 0,
    targetEnergy: 0,
    rings: [],
    mx: 0,
    my: 0,
    pmx: 0,
    pmy: 0,

    init(canvas) {
      if (!canvas) return;
      this.canvas = canvas;
      this.ctx = canvas.getContext('2d');
      this.resize();
      window.addEventListener('resize', () => this.resize());
      window.addEventListener(
        'pointermove',
        (e) => {
          this.mx = (e.clientX / window.innerWidth) * 2 - 1;
          this.my = (e.clientY / window.innerHeight) * 2 - 1;
        },
        { passive: true }
      );
      for (let i = 0; i < 5; i++) {
        this.rings.push({ phase: Math.random() * Math.PI * 2, speed: 0.4 + i * 0.12 });
      }
      this.loop();
    },

    resize() {
      this.dpr = Math.min(window.devicePixelRatio || 1, 2);
      this.w = window.innerWidth;
      this.h = window.innerHeight;
      this.canvas.width = this.w * this.dpr;
      this.canvas.height = this.h * this.dpr;
      this.canvas.style.width = this.w + 'px';
      this.canvas.style.height = this.h + 'px';
      this.ctx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);
    },

    pulse() {
      this.targetEnergy = 1;
      setTimeout(() => (this.targetEnergy = 0.12), 260);
    },

    loop() {
      const { ctx, w, h } = this;
      this.t += 0.016;
      this.energy += (this.targetEnergy - this.energy) * 0.06;
      if (this.targetEnergy > 0.12) this.targetEnergy *= 0.985;

      ctx.clearRect(0, 0, w, h);

      this.pmx += (this.mx - this.pmx) * 0.04;
      this.pmy += (this.my - this.pmy) * 0.04;

      const cx = w / 2 + this.pmx * 34;
      const cy = h / 2 + this.pmy * 24;
      const baseR = Math.min(w, h) * 0.16;
      const e = this.energy;

      this.rings.forEach((ring, idx) => {
        ring.phase += 0.005 * ring.speed;
        const radius = baseR * (1.35 + idx * 0.28) + Math.sin(this.t * ring.speed + ring.phase) * 10 + e * 26 * (idx + 1) * 0.3;
        ctx.beginPath();
        ctx.strokeStyle = 'rgba(0,0,0,' + (0.05 + e * 0.05) + ')';
        ctx.lineWidth = 1;
        ctx.arc(cx, cy, radius, 0, Math.PI * 2);
        ctx.stroke();
      });

      ctx.beginPath();
      const points = 90;
      for (let i = 0; i <= points; i++) {
        const a = (i / points) * Math.PI * 2;
        const n =
          Math.sin(a * 3 + this.t * 0.9) * 6 +
          Math.sin(a * 7 - this.t * 1.4) * 4 +
          Math.sin(a * 11 + this.t * 2.2) * (2 + e * 10);
        const r = baseR + n + e * 18;
        const x = cx + Math.cos(a) * r;
        const y = cy + Math.sin(a) * r;
        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
      }
      ctx.closePath();
      ctx.fillStyle = 'rgba(0,0,0,' + (0.02 + e * 0.04) + ')';
      ctx.fill();
      ctx.strokeStyle = 'rgba(0,0,0,' + (0.14 + e * 0.24) + ')';
      ctx.lineWidth = 1.2;
      ctx.stroke();

      requestAnimationFrame(() => this.loop());
    },
  };

  // ---------------------------------------------------------
  //  Typewriter (scramble settle)
  // ---------------------------------------------------------
  function typeLine(el, text, opts) {
    opts = opts || {};
    const speed = opts.speed || 16;
    el.textContent = '';
    el.classList.add('-show-cursor');

    let i = 0;
    let cancelled = false;
    const span = document.createElement('span');
    el.appendChild(span);

    function step() {
      if (cancelled) return;
      if (i >= text.length) {
        span.textContent = text;
        el.classList.remove('-show-cursor');
        if (opts.onDone) opts.onDone();
        return;
      }
      const finalChar = text[i];
      span.textContent = text.slice(0, i) + (finalChar.trim() ? CHARS[Math.floor(Math.random() * CHARS.length)] : '');
      i++;
      setTimeout(step, speed);
    }
    step();

    return {
      skip() {
        cancelled = true;
        span.textContent = text;
        el.classList.remove('-show-cursor');
        if (opts.onDone) opts.onDone();
      },
    };
  }

  // ---------------------------------------------------------
  //  Chat controller
  // ---------------------------------------------------------
  const Chat = {
    feed: null,
    input: null,
    sendBtn: null,
    chips: null,
    busy: false,
    typeController: null,

    init() {
      this.feed = qs('.c-live_feed');
      this.input = qs('.c-live_input input');
      this.sendBtn = qs('.c-live_send');
      this.chips = qs('.c-live_chips');

      Voice.init();
      Visualizer.init(qs('#visualizer-canvas'));

      if (!this.feed || !this.input) return;

      qs('.c-live_input').addEventListener('submit', (e) => {
        e.preventDefault();
        this.send(this.input.value);
      });

      qsa('.c-live_chip').forEach((chip) => {
        chip.addEventListener('click', () => {
          this.send(chip.textContent.trim());
          if (this.chips) this.chips.style.display = 'none';
        });
      });

      // Welcome line from bot
      if (CONFIG.welcome) {
        this.botLine(CONFIG.welcome, { speak: false });
      }
    },

    userLine(text) {
      const el = document.createElement('div');
      el.className = 'c-live_msg -user';
      el.innerHTML = '<div class="c-live_msg_bubble"></div>';
      el.querySelector('.c-live_msg_bubble').textContent = text;
      this.feed.appendChild(el);
      this.scroll();
    },

    botLine(text, opts) {
      opts = opts || {};
      const el = document.createElement('div');
      el.className = 'c-live_msg -bot';
      el.innerHTML = '<div class="c-live_msg_bubble"><span class="c-live-typed"></span></div>';
      this.feed.appendChild(el);
      this.scroll();

      const target = el.querySelector('.c-live-typed');
      const self = this;

      this.typeController = typeLine(target, text, {
        onDone() {
          if (opts.speak !== false) {
            document.dispatchEvent(new CustomEvent('lisa:speaking'));
            let ended = false;
            const end = () => {
              if (ended) return;
              ended = true;
              document.dispatchEvent(new CustomEvent('lisa:idle'));
            };
            Voice.speak(text, end);
            setTimeout(end, Math.min(1200 + text.length * 45, 6000));
          }
          self.scroll();
          self.setBusy(false);
        },
      });
    },

    typingIndicator() {
      const el = document.createElement('div');
      el.className = 'c-live_msg -bot -typing';
      el.innerHTML = '<div class="c-live_msg_bubble"><i></i><i></i><i></i></div>';
      this.feed.appendChild(el);
      this.scroll();
      return el;
    },

    orderCard(orderNumber, invoiceSent) {
      const el = document.createElement('div');
      el.className = 'c-live_order';
      const title = document.createElement('strong');
      title.textContent = 'Order confirmed ✓';
      el.appendChild(title);

      const reference = document.createElement('div');
      reference.textContent = 'Reference: ' + orderNumber;
      el.appendChild(reference);

      const status = document.createElement('div');
      status.textContent = invoiceSent
        ? 'Your illustrated invoice has been emailed to you.'
        : 'Share your email address in the chat to receive your invoice.';
      el.appendChild(status);

      this.feed.appendChild(el);
      this.scroll();
    },

    setBusy(busy) {
      this.busy = busy;
      if (this.sendBtn) this.sendBtn.disabled = busy;
    },

    scroll() {
      requestAnimationFrame(() => {
        this.feed.scrollTop = this.feed.scrollHeight;
      });
    },

    async send(text) {
      text = (text || '').trim();
      if (!text || this.busy) return;

      this.input.value = '';
      this.userLine(text);
      this.setBusy(true);

      const indicator = this.typingIndicator();
      Ambient.kick();
      Visualizer.pulse();

      try {
        const response = await fetch(CONFIG.chatUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Api-Key': CONFIG.apiKey,
          },
          body: JSON.stringify({
            session_id: sessionId(),
            message: text,
            page_url: window.location.href,
          }),
        });

        const data = await response.json();
        indicator.remove();

        if (!response.ok) {
          this.botLine(data.message || 'Sorry, something went wrong. Please try again.', { speak: false });
          this.setBusy(false);
          return;
        }

        this.botLine(data.reply || '…');

        if (data.order_confirmed || data.invoice_sent) {
          setTimeout(() => this.orderCard(data.order_number, data.invoice_sent), 600);
        }
      } catch (err) {
        indicator.remove();
        this.botLine('I could not reach the server. Please check your connection.', { speak: false });
        this.setBusy(false);
      }
    },
  };

  // ---------------------------------------------------------
  //  Sound toggle + about panel
  // ---------------------------------------------------------
  function initChrome() {
    const soundBtn = qs('.c-lisa_sound');
    if (soundBtn) {
      soundBtn.addEventListener('click', () => {
        const on = Voice.toggle();
        soundBtn.classList.toggle('-muted', !on);
        if (on && !Ambient.started) Ambient.start();
        Ambient.setMuted(!on);
      });
    }

    const aboutToggle = qs('.c-live_about-toggle');
    const aboutPanel = qs('.c-live_about');
    if (aboutToggle && aboutPanel) {
      aboutToggle.addEventListener('click', () => aboutPanel.classList.add('is-open'));
      qs('.c-live_about_close', aboutPanel).addEventListener('click', () => aboutPanel.classList.remove('is-open'));
      aboutPanel.addEventListener('click', (e) => {
        if (e.target === aboutPanel) aboutPanel.classList.remove('is-open');
      });
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') aboutPanel.classList.remove('is-open');
      });
    }
  }

  // ---------------------------------------------------------
  //  Boot after preloader
  // ---------------------------------------------------------
  document.addEventListener('lisa:boot', () => {
    Chat.init();
    initChrome();
  });
})();
