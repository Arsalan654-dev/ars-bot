# ARS Bot — Conversational Frontend Mirror

ARS Bot ke ARS Bot (Interactive Super Assistant) experience ka **original recreation** —
poora frontend (HTML + SCSS + vanilla JS), apna likha hua code, koi build-bundler nahi.

## Pages

| Page | File | Kya hai |
|---|---|---|
| **ARS Bot** | `index.html` | Conversational experience + 3D bot avatar |
| **Work** | `work.html` | Project grid + marquee + reveal animations |
| **Agency** | `agency.html` | Capability cards + story sections |
| **Careers** | `careers.html` | Job list + perks + apply CTAs |

## Chalane ka tareeqa

```bash
# SCSS compile (already compiled hai, sirf edit ke baad chalayein)
npm run build

# Local server
npm run serve
# ya VS Code mein "Live Server" se index.html khol dein
```

## Structure

```
index.html / work.html / agency.html / careers.html
assets/scss/
  abstracts/_variables.scss — design tokens (colors, grid, spacing, easing)
  abstracts/_mixins.scss    — breakpoints, screen-reader, center helpers
  base/_reset.scss          — minimal reset
  base/_root.scss           — root vars, themes
  components/_preloader.scss— black preloader, rolling word counters
  components/_header.scss   — fixed grid header + fullscreen mobile menu
  components/_lisa.scss     — dialog, choices, progress bar, sound/back buttons
  components/_forms.scss    — floating labels, pills, select, errors
  components/_bot.scss      — 3D sprite bot (frames from video)
  components/_effects.scss  — 3D entrances, button lift, depth utilities
  components/_cursor.scss   — blend-difference custom cursor
  components/_pages.scss    — hero, marquee, cards, job list, footer
assets/css/main.css         — compiled output
assets/js/
  lisa-content.js           — conversation step data (branches + forms)
  lisa.js                   — engine: typewriter, transitions, voice, visualizer
  bot3d.js                  — video -> sprite frames, cursor tilt/parallax
  pages.js                  — scroll reveal, hero parallax, back-to-top
  app.js                    — boot: preloader, cursor, header, menu, shuffle
assets/video/               — source video for the bot sprite frames
```

## Features

### ARS Bot (index.html)
- **3D Bot avatar** — video se JS canvas ke zariye frames extract hote hain (16–40),
  sprite-sheet ban ke CSS `steps()` animation se loop karta hai; video tag DOM mein nahi.
- **Cursor-follow motion** — bot mouse ke saath `rotateX/rotateY` tilt + parallax slide
  (lerped rAF), idle par sine-sway, bolne par energetic boost + glow.
- **Typewriter dialog** — per-character scramble settle + blinking caret, click to skip
- **Voice** — Web Speech API (bot bolta hai), sound toggle se mute + WebAudio ambient pad
- **Visualizer** — canvas orb + waveform, mouse parallax + speech energy reactive
- **Branching conversation** — project / careers / quick word / culture flows, forms,
  validation, progress bar, back-navigation, session persistence

### Inner pages (work / agency / careers)
- **Scroll reveal** — IntersectionObserver se sections stagger ke saath upar aate hain
- **Hero parallax** — background cursor + scroll ke saath move karta hai
- **Marquee band** — infinite horizontal scroll
- **3D hover cards** — project/capability cards tilt + lift karte hain
- **Back-to-top pill** — scroll ke baad appear
- **Same chrome** — preloader, custom cursor, shuffle hover, header hide/show, mobile menu

## Notes

- Fonts: licensed faces ki jagah free **Archivo** use ki hai.
- Sab code original hai — kisi site ka source copy nahi kiya gaya.
- Bot frames browser mein runtime par extract hote hain (koi pre-processing nahi chahiye).
