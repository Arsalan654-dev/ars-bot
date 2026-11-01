// ============================================================
//  LISA — conversational engine
//  Typewriter dialog (scramble-in), step choreography,
//  forms, history/back, progress bar, speech synthesis voice.
// ============================================================

(function () {
  'use strict';

  const CONTENT = window.LISA_CONTENT;
  const EASE = 'cubic-bezier(0.215, 0.61, 0.355, 1)';

  // ---------------------------------------------------------
  //  Small helpers
  // ---------------------------------------------------------
  const qs = (sel, ctx) => (ctx || document).querySelector(sel);
  const qsa = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));
  const rand = (arr) => arr[Math.floor(Math.random() * arr.length)];
  const stripHtml = (str) => {
    const d = document.createElement('div');
    d.innerHTML = str;
    return d.textContent || '';
  };

  const CHARS = 'abcdefghijklmnopqrstuvwxyz';

  // ---------------------------------------------------------
  //  Typewriter with per-character scramble settle
  // ---------------------------------------------------------
  function typeLine(el, html, opts) {
    opts = opts || {};
    const speed = opts.speed || 22; // ms per character
    const scrambleFrames = opts.scrambleFrames || 4;

    // Build the final DOM: keep inline tags (<em>, <br>) as anchors,
    // wrap every visible char in a <b> we can animate.
    const source = document.createElement('div');
    source.innerHTML = html;

    const frag = document.createDocumentFragment();
    const charSpans = [];

    (function walk(from, to) {
      from.childNodes.forEach((node) => {
        if (node.nodeType === Node.TEXT_NODE) {
          node.nodeValue.split('').forEach((ch) => {
            const b = document.createElement('b');
            b.style.fontWeight = 'inherit';
            b.style.opacity = '0';
            b.textContent = ch;
            charSpans.push(b);
            to.appendChild(b);
          });
        } else if (node.nodeName === 'BR') {
          to.appendChild(document.createElement('br'));
        } else {
          const clone = node.cloneNode(false);
          to.appendChild(clone);
          walk(node, clone);
        }
      });
    })(source, frag);

    el.innerHTML = '';
    el.appendChild(frag);
    el.classList.add('-show-cursor');

    let i = 0;
    let cancelled = false;

    function step() {
      if (cancelled) return;
      if (i >= charSpans.length) {
        el.classList.add('-done');
        if (opts.onDone) opts.onDone();
        return;
      }
      const span = charSpans[i];
      const finalChar = span.textContent;
      let frame = 0;

      span.style.opacity = '1';

      // scramble settle for non-space characters
      if (finalChar.trim()) {
        const tick = setInterval(() => {
          if (cancelled) return clearInterval(tick);
          frame++;
          if (frame >= scrambleFrames) {
            span.textContent = finalChar;
            clearInterval(tick);
          } else {
            span.textContent = rand(CHARS.split(''));
          }
        }, speed / 2);
      }

      i++;
      setTimeout(step, speed);
    }

    step();

    return {
      skip() {
        cancelled = true;
        charSpans.forEach((s) => {
          s.style.opacity = '1';
        });
        el.innerHTML = html;
        el.classList.add('-done');
        if (opts.onDone) opts.onDone();
      },
    };
  }

  // ---------------------------------------------------------
  //  Voice — Web Speech API with an expressive female voice
  // ---------------------------------------------------------
  const Voice = {
    enabled: false, // browsers block audio until first interaction
    ready: false,
    voice: null,
    synth: window.speechSynthesis || null,

    init() {
      if (!this.synth) return;
      const pick = () => {
        const voices = this.synth.getVoices();
        if (!voices.length) return;
        this.voice =
          voices.find((v) => /samantha|zira|aria|google uk english female|google us english/i.test(v.name) && /en/i.test(v.lang)) ||
          voices.find((v) => /female/i.test(v.name)) ||
          voices.find((v) => /^en/i.test(v.lang)) ||
          voices[0];
        this.ready = true;
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
      const u = new SpeechSynthesisUtterance(stripHtml(text));
      u.voice = this.voice;
      u.rate = 1.02;
      u.pitch = 1.05;
      u.volume = 0.9;
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
  //  Visualizer — reactive orb + waveform on <canvas>
  //  (Reacts to speech state + pointer; audio-simulated.)
  // ---------------------------------------------------------
  const Visualizer = {
    canvas: null,
    ctx: null,
    w: 0,
    h: 0,
    dpr: 1,
    t: 0,
    energy: 0, // 0 idle .. 1 speaking
    targetEnergy: 0,
    rings: [],
    mx: 0,
    my: 0,
    pmx: 0,
    pmy: 0,

    init(canvas) {
      this.canvas = canvas;
      this.ctx = canvas.getContext('2d');
      this.resize();
      window.addEventListener('resize', () => this.resize());

      // Mouse parallax — visualizer bhi cursor ke saath hilta hai
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

      // Lerped parallax center — rings/orb cursor ke peeche chalte hain
      this.pmx += (this.mx - this.pmx) * 0.04;
      this.pmy += (this.my - this.pmy) * 0.04;

      const cx = w / 2 + this.pmx * 34;
      const cy = h / 2 + this.pmy * 24;
      const baseR = Math.min(w, h) * 0.16;
      const e = this.energy;

      // Orbiting rings
      this.rings.forEach((ring, idx) => {
        ring.phase += 0.005 * ring.speed;
        const radius = baseR * (1.35 + idx * 0.28) + Math.sin(this.t * ring.speed + ring.phase) * 10 + e * 26 * (idx + 1) * 0.3;
        ctx.beginPath();
        ctx.strokeStyle = 'rgba(0,0,0,' + (0.06 + e * 0.06) + ')';
        ctx.lineWidth = 1;
        ctx.arc(cx, cy, radius, 0, Math.PI * 2);
        ctx.stroke();
      });

      // Main orb — noise-displaced circle
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
      ctx.fillStyle = 'rgba(0,0,0,' + (0.03 + e * 0.05) + ')';
      ctx.fill();
      ctx.strokeStyle = 'rgba(0,0,0,' + (0.18 + e * 0.3) + ')';
      ctx.lineWidth = 1.2;
      ctx.stroke();

      // Waveform strip across the middle while "speaking"
      if (e > 0.02) {
        ctx.beginPath();
        const y0 = cy;
        const span = Math.min(w * 0.42, 480);
        for (let x = -span; x <= span; x += 4) {
          const decay = 1 - Math.abs(x) / span;
          const y =
            y0 +
            Math.sin(x * 0.03 + this.t * 9) * 14 * e * decay +
            Math.sin(x * 0.011 - this.t * 5) * 8 * e * decay;
          if (x === -span) ctx.moveTo(cx + x, y);
          else ctx.lineTo(cx + x, y);
        }
        ctx.strokeStyle = 'rgba(0,0,0,' + 0.5 * e + ')';
        ctx.lineWidth = 1.5;
        ctx.stroke();
      }

      requestAnimationFrame(() => this.loop());
    },
  };

  // ---------------------------------------------------------
  //  LISA engine
  // ---------------------------------------------------------
  const Lisa = {
    el: null,
    main: null,
    progressEl: null,
    backBtn: null,
    soundBtn: null,

    currentSlug: null,
    currentStep: null,
    history: [],
    model: {},
    muted: false,
    typeController: null,
    autoTimer: null,
    stepEl: null,

    init() {
      this.el = qs('.c-lisa');
      this.main = qs('.c-lisa_main');
      this.progressEl = qs('.c-lisa_progress');
      this.backBtn = qs('.c-lisa_back');
      this.soundBtn = qs('.c-lisa_sound');

      Voice.init();
      Visualizer.init(qs('#visualizer-canvas'));

      this.soundBtn.addEventListener('click', () => this.toggleSound());
      this.backBtn.addEventListener('click', () => this.back());

      // Restore progression if present
      const saved = sessionStorage.getItem('lisa-slug');
      this.go(saved && CONTENT[saved] ? saved : 'intro', { replace: true });
    },

    toggleSound() {
      const on = Voice.toggle();
      this.muted = !on;
      this.soundBtn.classList.toggle('-muted', this.muted);
      if (on && !Ambient.started) Ambient.start();
      Ambient.setMuted(this.muted);
    },

    setSpeaking(on) {
      document.dispatchEvent(new CustomEvent(on ? 'lisa:speaking' : 'lisa:idle'));
    },

    setProgress(value) {
      this.progressEl.style.setProperty('--progress', value);
    },

    setCompact(compact) {
      this.el.classList.toggle('is-compact', compact);
      document.dispatchEvent(new CustomEvent('lisa:compact', { detail: { compact } }));
    },

    resolveVars(str) {
      return str.replace(/\{(\w+)\}/g, (m, key) => this.model[key] || m);
    },

    // -------------------------------------------------------
    //  Go to a step
    // -------------------------------------------------------
    go(slug, opts) {
      opts = opts || {};
      const step = CONTENT[slug];
      if (!step) return;

      clearTimeout(this.autoTimer);
      if (this.typeController) this.typeController.skip && this.typeController.skip();

      const previousSlug = this.currentSlug;
      const backwards = opts.backwards === true;

      if (!opts.replace && !backwards && previousSlug) {
        this.history.push(previousSlug);
      }
      if (backwards) this.history.pop();

      const previousSentence = backwards
        ? null
        : this.currentStep && this._lastSentence
          ? this._lastSentence
          : null;

      this.currentSlug = slug;
      this.currentStep = step;
      sessionStorage.setItem('lisa-slug', slug);

      this.setProgress(step.progress || 0);
      this.setCompact(!!step.compact);
      this.backBtn.classList.toggle('v-enter-from', this.history.length === 0);
      this.backBtn.style.opacity = this.history.length ? '1' : '0';
      this.backBtn.style.pointerEvents = this.history.length ? 'all' : 'none';

      this.render(step, { backwards, previousSentence });
    },

    back() {
      if (!this.history.length) return;
      const prev = this.history[this.history.length - 1];
      this.go(prev, { backwards: true });
    },

    // -------------------------------------------------------
    //  Render a step into the conversation column
    // -------------------------------------------------------
    render(step, opts) {
      const self = this;
      const old = this.stepEl;

      const wrap = document.createElement('div');
      wrap.className = 'c-lisa-step';
      wrap.innerHTML = `
        ${opts.previousSentence ? `<span class="c-lisa-step_previous" aria-hidden="true">${opts.previousSentence}</span>` : ''}
        <span class="c-lisa-step_dialog" role="status" aria-live="polite"><span></span></span>
        <div class="c-lisa-step_content"></div>
      `;

      // Ghost previous sentence -> clickable to go back
      const ghost = qs('.c-lisa-step_previous', wrap);
      if (ghost) {
        ghost.addEventListener('click', () => self.back());
      }

      this.main.appendChild(wrap);
      this.stepEl = wrap;

      // Choreograph transition
      requestAnimationFrame(() => {
        if (old) {
          old.classList.add(opts.backwards ? 'backwards-leave-active' : 'default-leave-active');
          old.classList.add(opts.backwards ? 'backwards-leave-to' : 'default-leave-to');
          setTimeout(() => old.remove(), 340);
        }
        if (opts.backwards) {
          wrap.classList.add('backwards-enter-active');
        }
      });

      // Pick a sentence (avoid immediate repeats)
      let line = rand(step.dialog);
      if (step.dialog.length > 1 && line === self._lastSentenceRaw) {
        line = rand(step.dialog);
      }
      self._lastSentenceRaw = line;
      line = self.resolveVars(line);
      self._lastSentence = line;

      const dialogEl = qs('.c-lisa-step_dialog > span', wrap);

      // Ambient + visualizer react
      Ambient.kick();
      Visualizer.pulse();

      const controller = typeLine(dialogEl, line, {
        speed: 20,
        onDone() {
          dialogEl.classList.remove('-show-cursor');
          // Voice reads the finished line; bot reacts while speaking
          self.setSpeaking(true);
          let ended = false;
          const end = () => {
            if (ended) return;
            ended = true;
            self.setSpeaking(false);
          };
          Voice.speak(line, end);
          // Fallback: voice off ho toh bhi bot ek "talk beat" dikhaye
          setTimeout(end, Math.min(1200 + stripHtml(line).length * 45, 6000));
          // Reveal content
          self.revealContent(wrap, step);
          // Auto advance for intro/loading steps
          if (step.autoAdvance) {
            self.autoTimer = setTimeout(() => self.go(step.next), step.autoAdvance);
          }
        },
      });
      self.typeController = controller;

      // Click anywhere on dialog to skip typing
      qs('.c-lisa-step_dialog', wrap).addEventListener('click', () => controller.skip());
    },

    // -------------------------------------------------------
    //  Content: choices or form
    // -------------------------------------------------------
    revealContent(wrap, step) {
      const self = this;
      const content = qs('.c-lisa-step_content', wrap);
      if (!content) return;

      if (step.loading) {
        content.innerHTML = `<div class="c-lisa_loading" aria-label="Loading"></div>`;
        wrap.classList.add('-expanded');
        return;
      }

      if (step.choices) {
        const list = document.createElement('div');
        list.className = 'c-lisa-step_choices';
        step.choices.forEach((choice) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'c-lisa_button c-lisa-step_choice' + (choice.href ? ' -secondary' : '');
          btn.innerHTML = `<span>${choice.label}</span>${choice.href ? '<span>\u2197</span>' : ''}`;
          btn.setAttribute('data-cursor', 'hover');

          if (choice.href) {
            btn.addEventListener('click', () => window.open(choice.href, '_blank', 'noopener'));
          } else {
            btn.addEventListener('click', () => {
              if (choice.model) Object.assign(self.model, choice.model);
              self.go(choice.target);
            });
          }
          list.appendChild(btn);
        });
        content.appendChild(list);
      }

      if (step.inputs) {
        content.appendChild(this.buildForm(step));
      }

      // Expand after a beat so the transition reads
      setTimeout(() => wrap.classList.add('-expanded'), 120);
    },

    buildForm(step) {
      const self = this;
      const form = document.createElement('form');
      form.className = 'c-lisa-step_form';
      form.noValidate = true;

      step.inputs.forEach((input) => {
        const item = document.createElement('div');
        item.className = 'c-form_item';

        if (input.type === 'textarea') {
          item.innerHTML = `
            <div class="c-form_floating">
              <textarea class="c-form_textarea" id="${input.name}" name="${input.name}" placeholder="${input.label}"></textarea>
              <label class="c-form_label" for="${input.name}">${input.label}</label>
            </div>
            <ul class="c-form_input-errors is-hidden"></ul>
          `;
        } else {
          item.innerHTML = `
            <div class="c-form_floating">
              <input class="c-form_input" id="${input.name}" name="${input.name}" type="${input.type}" placeholder="${input.label}" ${input.required ? 'required' : ''} />
              <label class="c-form_label" for="${input.name}">${input.label}</label>
            </div>
            <ul class="c-form_input-errors is-hidden"></ul>
          `;
        }
        form.appendChild(item);
      });

      const next = document.createElement('button');
      next.type = 'submit';
      next.className = 'c-lisa_button c-lisa-step_next';
      next.setAttribute('aria-label', 'Next');
      next.innerHTML = '\u2192';
      form.appendChild(next);

      form.addEventListener('submit', (e) => {
        e.preventDefault();
        let valid = true;

        step.inputs.forEach((input) => {
          const field = form.elements[input.name];
          const errors = field.closest('.c-form_item').querySelector('.c-form_input-errors');
          const value = (field.value || '').trim();
          errors.innerHTML = '';
          errors.classList.add('is-hidden');
          field.classList.remove('-invalid');

          if (input.required && !value) {
            valid = false;
            field.classList.add('-invalid');
            errors.innerHTML = '<li>This field is required</li>';
            errors.classList.remove('is-hidden');
          } else if (input.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            valid = false;
            field.classList.add('-invalid');
            errors.innerHTML = '<li>Please enter a valid email</li>';
            errors.classList.remove('is-hidden');
          }
        });

        if (!valid) {
          Visualizer.pulse();
          return;
        }

        // Store answers for {variable} interpolation
        step.inputs.forEach((input) => {
          const field = form.elements[input.name];
          if (field && field.value) self.model[input.name] = field.value.trim();
        });

        if (step.next) self.go(step.next);
      });

      // Enter to submit from inputs
      form.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.target.nodeName !== 'TEXTAREA') {
          e.preventDefault();
          form.requestSubmit();
        }
      });

      return form;
    },
  };

  // ---------------------------------------------------------
  //  Ambient audio — soft generated hum (no external asset)
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

      // Two detuned oscillators + slow LFO = airy pad
      [55, 82.5, 110].forEach((freq, i) => {
        const osc = this.ctx.createOscillator();
        osc.type = 'sine';
        osc.frequency.value = freq;
        const g = this.ctx.createGain();
        g.gain.value = 0.06 / (i + 1);
        osc.connect(g).connect(this.gain);
        osc.start();
      });

      const lfo = this.ctx.createOscillator();
      lfo.frequency.value = 0.08;
      const lfoGain = this.ctx.createGain();
      lfoGain.gain.value = 0.02;
      lfo.connect(lfoGain).connect(this.gain.gain);
      lfo.start();
    },

    start() {
      this.ensure();
      if (!this.ctx) return;
      if (this.ctx.state === 'suspended') this.ctx.resume();
      if (!this.muted) this.gain.gain.linearRampToValueAtTime(0.5, this.ctx.currentTime + 2);
      this.started = true;
    },

    kick() {
      // Duck briefly while the bot "talks"
      if (!this.ctx || this.muted) return;
      const t = this.ctx.currentTime;
      this.gain.gain.cancelScheduledValues(t);
      this.gain.gain.linearRampToValueAtTime(0.1, t + 0.1);
      this.gain.gain.linearRampToValueAtTime(0.5, t + 2.4);
    },

    setMuted(muted) {
      this.muted = muted;
      if (!this.ctx) return;
      const t = this.ctx.currentTime;
      this.gain.gain.cancelScheduledValues(t);
      this.gain.gain.linearRampToValueAtTime(muted ? 0 : 0.5, t + 0.4);
    },
  };

  // Expose for the boot layer
  window.LISA = { Lisa, Voice, Ambient, Visualizer, typeLine };

  // Self reference used inside choices
  Lisa.model = Lisa.model || {};

  // ---------------------------------------------------------
  //  Auto-init once the preloader is done
  // ---------------------------------------------------------
  document.addEventListener('lisa:boot', () => Lisa.init());
})();
