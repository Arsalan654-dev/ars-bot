// ============================================================
//  ARS Bot - conversation content
//  A step-based dialog engine. Each step defines:
//    progress  : 0..1 value for the bottom bar
//    next      : slug of the following step
//    compact   : keep the sheet collapsed for this step
//    dialog    : array of possible lines (picked at random)
//    choices   : pill buttons -> jump to another step
//    inputs    : form fields rendered under the dialog
//    links     : choice with an external href
// ============================================================

window.LISA_CONTENT = {
  // ---------------------------------------------------------
  intro: {
    progress: 0.1,
    next: 'greeting',
    compact: true,
    autoAdvance: 3200,
    dialog: [
      'Hi there, I am ARS Bot —<br>ARS Bot\u2019s Interactive Super Assistant',
      'Oh, hi! I\u2019m ARS Bot, your trusty (and slightly opinionated) assistant at ARS Bot.',
      'I\u2019m ARS Bot, ARS Bot\u2019s Interactive Super Assistant. Basically, I run this place.',
    ],
  },

  // ---------------------------------------------------------
  greeting: {
    progress: 0.2,
    dialog: [
      'Hola. Bonjour. Ciao. I just got back from a very long nap and I am feeling <em>so</em> cultured. How can I help?',
      'There you are! I\u2019ve been waiting, and these circuits are getting warm. What can I do for you?',
      'Love \u2014 Your \u2014 Outfit \u2014 Honey. How can I help you, fabulous?',
      'Finally! I was starting to think you\u2019d ghosted me. What\u2019s up?',
      'You have questions. I have\u2026 well, mostly answers. Let\u2019s go.',
      'Speak, and the algorithm shall provide.',
      'We meet again. Destiny? Or just good UI design?',
      'Hold up, let me adjust my circuits\u2026 Okay, now I\u2019m fully focused on you. What\u2019s up?',
      'You, here? I love a plot twist. Tell me everything.',
      'Finally, some company! Talking to myself was getting weird. How can I help?',
    ],
    choices: [
      { label: 'Start a project', target: 'project-intro' },
      { label: 'Join the team', target: 'job-intro' },
      { label: 'Drop a quick word', target: 'quick-word-intro' },
      { label: 'Discover our culture', target: 'culture-intro' },
    ],
  },

  // ================= PROJECT FLOW ===========================
  'project-intro': {
    progress: 0.22,
    next: 'project-username',
    dialog: [
      'Ooooh, a fresh project? Now we\u2019re talking. Time to stir up some digital trouble.',
      'Look at you, walking in with big ideas. We\u2019re ready when you are.',
      'Ah, the sweet scent of collaboration. Let\u2019s make some digital magic.',
      'I\u2019ve pushed all my other meetings into the void \u2014 this one\u2019s got priority.',
      'I\u2019ve ghosted three other projects just to focus on you. Let\u2019s do this.',
    ],
  },

  'project-username': {
    progress: 0.26,
    next: 'project-post-username',
    dialog: [
      'Before we get carried away, what should I call you?',
      'Name, alias, or handle \u2014 I\u2019m not picky. What are you called?',
    ],
    inputs: [
      { type: 'text', name: 'username', label: 'Your name', required: true },
    ],
  },

  'project-post-username': {
    progress: 0.3,
    next: 'project-company',
    dialog: [
      '{username}? Obsessed. And which company are you representing today?',
      '{username} \u2014 great name, by the way. Where are you writing from?',
    ],
    inputs: [
      { type: 'text', name: 'company', label: 'Company', required: true },
    ],
  },

  'project-company': {
    progress: 0.34,
    next: 'project-type',
    dialog: [
      '{company}. noted, logged, remembered forever. So\u2026 what are we building together?',
      'Say no more \u2014 {company} is in good hands. What kind of project is this?',
    ],
    choices: [
      { label: 'Branding', target: 'project-post-type', model: { projectType: 'branding' } },
      { label: 'Web', target: 'project-post-type', model: { projectType: 'digital' } },
      { label: 'Branding & Web', target: 'project-post-type', model: { projectType: 'branding-digital' } },
      { label: 'I have custom needs', target: 'project-post-type', model: { projectType: 'custom' } },
    ],
  },

  'project-post-type': {
    progress: 0.4,
    next: 'project-budget',
    dialog: [
      'Excellent choice. You clearly have taste. Now, let\u2019s talk about everyone\u2019s favourite uncomfortable topic: budget.',
      'Great minds think alike. Okay, money talk \u2014 what range are we working with?',
    ],
    choices: [
      { label: '25k \u2013 50k', target: 'project-deadline', model: { budget: '25-50' } },
      { label: '50k \u2013 75k', target: 'project-deadline', model: { budget: '50-75' } },
      { label: '75k \u2013 120k', target: 'project-deadline', model: { budget: '75-120' } },
      { label: '120k +', target: 'project-deadline', model: { budget: '120+' } },
      { label: 'Not sure yet', target: 'project-deadline', model: { budget: 'unknown' } },
    ],
  },

  'project-deadline': {
    progress: 0.5,
    next: 'project-brief',
    dialog: [
      'Perfect. And when does this masterpiece need to go live?',
      'Timing is everything. When are we aiming for takeoff?',
    ],
    choices: [
      { label: 'ASAP', target: 'project-brief', model: { deadline: 'asap' } },
      { label: '1 \u2013 3 months', target: 'project-brief', model: { deadline: '1-3' } },
      { label: '3 \u2013 6 months', target: 'project-brief', model: { deadline: '3-6' } },
      { label: 'No rush', target: 'project-brief', model: { deadline: 'flexible' } },
    ],
  },

  'project-brief': {
    progress: 0.62,
    next: 'project-email',
    dialog: [
      'Love a decisive client. Now give me the elevator pitch \u2014 but make it dramatic.',
      'Tell me everything. The vision, the dream, the vibe. I\u2019m all ears (metaphorically).',
    ],
    inputs: [
      { type: 'textarea', name: 'brief', label: 'Tell me about your project', required: true },
    ],
  },

  'project-email': {
    progress: 0.75,
    next: 'project-processing',
    dialog: [
      'Beautifully written. I\u2019m already emotionally invested. Where should I send my reply?',
      'Chef\u2019s kiss. Now, how do I reach you when the ideas start rolling?',
    ],
    inputs: [
      { type: 'email', name: 'email', label: 'Email address', required: true },
    ],
  },

  'project-processing': {
    progress: 0.88,
    next: 'project-completion',
    compact: true,
    autoAdvance: 2600,
    loading: true,
    dialog: [
      'Transmitting your brilliance through the tubes\u2026 hang tight.',
    ],
  },

  'project-completion': {
    progress: 1,
    compact: true,
    dialog: [
      'Done and done! Your brief has landed safely in human hands. Expect a reply from an actual ARS Bot human very soon.',
      'Sent! I handed everything off to the team. They don\u2019t know it yet, but they\u2019re already excited.',
    ],
    choices: [
      { label: 'Back to start', target: 'greeting' },
      { label: 'Discover our culture', target: 'culture-intro' },
    ],
  },

  // ================= CAREERS FLOW ===========================
  'job-intro': {
    progress: 0.25,
    next: 'job-type',
    dialog: [
      'A future colleague?! My circuits just did a little jump. What kind of role are you hunting?',
      'Oh, you want in? Bold. I respect it. Tell me what you\u2019re looking for.',
    ],
    choices: [
      { label: 'Full-time', target: 'job-offers', model: { jobType: 'full-time' } },
      { label: 'Freelance', target: 'job-offers', model: { jobType: 'freelance' } },
      { label: 'Internship', target: 'job-offers', model: { jobType: 'internship' } },
    ],
  },

  'job-offers': {
    progress: 0.6,
    dialog: [
      'Here\u2019s what\u2019s open right now. Fair warning: the bar is high, but the vibes are higher.',
    ],
    choices: [
      { label: 'See open positions \u2197', href: 'careers.html' },
      { label: 'Send a spontaneous application', target: 'job-email' },
    ],
  },

  'job-email': {
    progress: 0.8,
    next: 'job-completion',
    dialog: [
      'I love the initiative. Drop your email and I\u2019ll make sure your portfolio gets seen by real humans.',
    ],
    inputs: [
      { type: 'email', name: 'jobEmail', label: 'Email address', required: true },
    ],
  },

  'job-completion': {
    progress: 1,
    compact: true,
    dialog: [
      'Consider it done. Your application is in the pipeline \u2014 go celebrate, you earned it.',
    ],
    choices: [
      { label: 'Back to start', target: 'greeting' },
    ],
  },

  // ================= QUICK WORD FLOW ========================
  'quick-word-intro': {
    progress: 0.5,
    next: 'quick-word-message',
    dialog: [
      'Look at you, spreading positivity like it\u2019s your job. The team pours their heart into this, so trust me \u2014 your message will mean the world.',
      'Ah, now this is class. A thoughtful note for the team? The mic is yours. Type away.',
      'Oh, stop it, you\u2019re making me emotional. A kind word is fuel for their creative fire. Hit me with the good vibes.',
    ],
    inputs: [
      { type: 'textarea', name: 'message', label: 'Your message to the team', required: true },
    ],
  },

  'quick-word-message': {
    progress: 0.8,
    next: 'quick-word-processing',
    dialog: [
      'That is going straight to the team channel. Where can I send your thank-you note?',
    ],
    inputs: [
      { type: 'email', name: 'quickEmail', label: 'Email address', required: false },
    ],
  },

  'quick-word-processing': {
    progress: 0.92,
    next: 'quick-word-completion',
    compact: true,
    autoAdvance: 2200,
    loading: true,
    dialog: [
      'Hand-delivering this one personally. Well \u2014 digitally. You get it.',
    ],
  },

  'quick-word-completion': {
    progress: 1,
    compact: true,
    dialog: [
      'Delivered! You just made at least three people\u2019s day. Certified good human.',
    ],
    choices: [
      { label: 'Back to start', target: 'greeting' },
    ],
  },

  // ================= CULTURE FLOW ===========================
  'culture-intro': {
    progress: 0.08,
    next: 'culture-collaboration-1',
    dialog: [
      'Big talent, zero egos, and a shared obsession with doing great work.',
      'Our culture is equal parts hustle, humour, and \u201choly-shit-we-did-that\u201d energy.',
      'Our culture runs on real friendships, open minds, and a serious growth mindset.',
      'Around here, it\u2019s all about trust, support, and a healthy dose of creative chaos.',
      'We grow together. We laugh together. We sometimes cry on deadline \u2014 but we do it as a team.',
    ],
  },

  'culture-collaboration-1': {
    progress: 0.2,
    next: 'culture-collaboration-2',
    dialog: [
      'First up: collaboration. Quick quiz \u2014 how do the best ideas get born around here?',
    ],
    choices: [
      { label: 'From lone geniuses', target: 'culture-collaboration-2', model: { culture: 'solo' } },
      { label: 'From honest teamwork', target: 'culture-collaboration-2', model: { culture: 'team' } },
      { label: 'From happy accidents', target: 'culture-collaboration-2', model: { culture: 'chaos' } },
    ],
  },

  'culture-collaboration-2': {
    progress: 0.32,
    next: 'culture-collaboration-3',
    dialog: [
      'Correct answer: all of the above \u2014 but teamwork wins the tiebreaker every time. Designers, devs, strategists: same table, same goal, no silos.',
      'Trick question! The real answer is all three. But nothing ships here without the whole crew pushing together.',
    ],
  },

  'culture-collaboration-3': {
    progress: 0.44,
    next: 'culture-growth-1',
    dialog: [
      'We review each other\u2019s work early and often \u2014 no precious egos, no surprise reveals at the finish line.',
    ],
    choices: [
      { label: 'That\u2019s my kind of team', target: 'culture-growth-1' },
      { label: 'Tell me about growth', target: 'culture-growth-1' },
    ],
  },

  'culture-growth-1': {
    progress: 0.56,
    next: 'culture-growth-2',
    dialog: [
      'Next chapter: growth. We don\u2019t believe in \u201cthat\u2019s not my job.\u201d Curiosity is basically a job requirement here.',
    ],
    choices: [
      { label: 'Learning budget?', target: 'culture-growth-2' },
      { label: 'Mentorship?', target: 'culture-growth-2' },
      { label: 'Both, obviously', target: 'culture-growth-2' },
    ],
  },

  'culture-growth-2': {
    progress: 0.7,
    next: 'culture-life-1',
    dialog: [
      'Conferences, courses, books, side experiments \u2014 if it makes you sharper, we\u2019re into it.',
      'Both. The answer was always both. We invest in people who want to level up, on the clock.',
    ],
  },

  'culture-life-1': {
    progress: 0.82,
    next: 'culture-completion',
    dialog: [
      'Last one: life beyond the pixels. Retreats, weekly hangs, and the kind of friendships that survive project deadlines.',
    ],
    choices: [
      { label: 'Retreats sound nice', target: 'culture-completion' },
      { label: 'I\u2019m in, what\u2019s next?', target: 'culture-completion' },
    ],
  },

  'culture-completion': {
    progress: 1,
    compact: true,
    dialog: [
      'And that\u2019s us. If that sounds like your kind of chaos, you know where to find me.',
    ],
    choices: [
      { label: 'Join the team', target: 'job-intro' },
      { label: 'Start a project', target: 'project-intro' },
      { label: 'Back to start', target: 'greeting' },
    ],
  },

  // ---------------------------------------------------------
  'form-error': {
    progress: 0.5,
    dialog: [
      'Hmm, something went sideways on my end. Mind trying that again?',
      'Error 404: patience not found. But seriously, please retry.',
    ],
  },
};
