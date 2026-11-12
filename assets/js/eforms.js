/*!
 * School e-Forms — εφαρμογή σελίδας
 * Καμία αποστολή ή αποθήκευση στοιχείων εντύπου: όλα γίνονται στη συσκευή.
 * Μόνο η προαιρετική «αποστολή από την ιστοσελίδα» στέλνει το βεβαιωμένο PDF.
 */
(function () {
  'use strict';

  /* ---------------- Βοηθητικά ---------------- */
  var WORDS = ['', 'μία', 'δύο', 'τρεις', 'τέσσερις', 'πέντε', 'έξι', 'επτά', 'οκτώ', 'εννέα', 'δέκα', 'έντεκα', 'δώδεκα',
    'δεκατρείς', 'δεκατέσσερις', 'δεκαπέντε', 'δεκαέξι', 'δεκαεπτά', 'δεκαοκτώ', 'δεκαεννέα', 'είκοσι'];
  function words(n) {
    if (n <= 20) { return WORDS[n] || ''; }
    if (n < 30) { return 'είκοσι ' + WORDS[n - 20]; }
    if (n === 30) { return 'τριάντα'; }
    return '';
  }
  // Γενική πτώση για «… ημερών» (μίας, τριών, τεσσάρων, δεκατριών…)
  function wordsGen(n) {
    return words(n).replace(/μία$/, 'μίας').replace(/τρεις$/, 'τριών').replace(/τρείς$/, 'τριών').replace(/τέσσερις$/, 'τεσσάρων');
  }
  var WEEKDAYS = ['την Κυριακή', 'τη Δευτέρα', 'την Τρίτη', 'την Τετάρτη', 'την Πέμπτη', 'την Παρασκευή', 'το Σάββατο'];
  function iso(d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }
  function fmtDate(v) { var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v || ''); return m ? m[3] + '/' + m[2] + '/' + m[1] : (v || ''); }
  function reduceMotion() { return window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches; }
  /* Ύψος σταθερής (sticky/fixed) κεφαλίδας του θέματος, ώστε η κορυφή του εντύπου
   * να μην κρύβεται από κάτω της. Ρύθμιση από το θέμα: --sef-scroll-offset στο :root. */
  function headerOffset() {
    var forced = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--sef-scroll-offset'));
    if (!isNaN(forced)) { return forced; }
    var h = 0;
    var bar = document.getElementById('wpadminbar');
    if (bar && getComputedStyle(bar).position === 'fixed') { h = bar.offsetHeight; }
    var base = h;
    // Flatsome: η κεφαλίδα γίνεται .stuck (fixed) μόνο μετά την κύλιση — μετράμε από πριν.
    var fs = document.querySelector('#header.has-sticky .header-wrapper');
    if (fs) {
      var fh = fs.offsetHeight;
      Array.prototype.forEach.call(fs.querySelectorAll('.hide-for-sticky'), function (x) { fh -= x.offsetHeight; });
      h = Math.max(h, base + Math.max(0, fh));
    }
    // Γενικά: ό,τι είναι fixed/sticky στην κορυφή αυτή τη στιγμή.
    if (document.elementsFromPoint) {
      [0.1, 0.5, 0.9].forEach(function (fx) {
        document.elementsFromPoint(window.innerWidth * fx, base + 2).forEach(function (n) {
          for (; n && n !== document.body && n !== document.documentElement; n = n.parentElement) {
            if (n.closest && n.closest('.sef-app')) { return; }
            var pos = getComputedStyle(n).position;
            if (pos === 'fixed' || pos === 'sticky') {
              var r = n.getBoundingClientRect();
              if (r.top <= base + 2 && r.height < window.innerHeight / 2) { h = Math.max(h, r.bottom); }
              return;
            }
          }
        });
      });
    }
    return h ? h + 12 : 0;
  }
  function scrollTo(el, block) {
    if (!el) { return; }
    var behavior = reduceMotion() ? 'auto' : 'smooth';
    if ((block || 'start') !== 'start') { el.scrollIntoView({ behavior: behavior, block: block }); return; }
    var y = el.getBoundingClientRect().top + window.pageYOffset - headerOffset();
    window.scrollTo({ top: Math.max(0, y), behavior: behavior });
  }
  function flash(el) { if (!el) { return; } el.classList.remove('sef-flash'); void el.offsetWidth; el.classList.add('sef-flash'); }
  function sizeText(b) { return b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
  function copyText(text, btn) {
    var done = function (ok) {
      if (!btn) { return; }
      var old = btn.getAttribute('data-label') || btn.textContent;
      btn.setAttribute('data-label', old);
      btn.textContent = ok ? '✓ Αντιγράφηκε' : 'Επιλέξτε & αντιγράψτε';
      setTimeout(function () { btn.textContent = old; }, 2200);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(function () { done(true); }, function () { done(false); });
    } else {
      var ta = document.createElement('textarea');
      ta.value = text; ta.setAttribute('readonly', ''); ta.style.cssText = 'position:fixed;left:-9999px;top:0';
      document.body.appendChild(ta); ta.select();
      var ok = false; try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
      ta.remove(); done(ok);
    }
  }

  /* ---------------- Οδηγός gov.gr (εικόνες + επισήμανση) ---------------- */
  // Συντεταγμένες [x0,y0,x1,y1] σε στιγμιότυπα 1366×660.
  var GUIDE = [
    { d: 5.5, t: 'Ανοίξτε στο gov.gr την υπηρεσία «Ψηφιακή βεβαίωση εγγράφου» (πράσινο κουμπί «Άνοιγμα gov.gr»).', box: [176, 285, 642, 334], from: [700, 500], zoom: 1.15 },
    { d: 5, t: 'Κατεβείτε λίγο πιο κάτω και πατήστε «Είσοδος στην υπηρεσία».', box: [179, 284, 482, 348], click: 3.2, from: [700, 480], zoom: 1.3 },
    { d: 4.5, t: 'Πατήστε «Συνέχεια».', box: [188, 401, 309, 451], click: 2.8, from: [600, 300], zoom: 1.3 },
    { d: 5, t: 'Επιλέξτε «ΓΓΠΣΨΔ (Κωδικοί Taxisnet)».', box: [240, 429, 450, 502], click: 3.2, from: [700, 200], zoom: 1.25 },
    { d: 6, t: 'Γράψτε το όνομα χρήστη και τον κωδικό σας στο Taxisnet και πατήστε «Σύνδεση».', box: [552, 513, 823, 546], click: 4.2, from: [688, 400], zoom: 1.3 },
    { d: 5.5, t: 'Αφήστε επιλεγμένο το «Συνέχεια» και πατήστε «Αποστολή».', box: [639, 450, 727, 483], click: 3.8, from: [548, 402], zoom: 1.35 },
    { d: 5.5, t: 'Ελέγξτε ότι τα στοιχεία σας είναι σωστά και πατήστε «Συνέχεια».', box: [188, 580, 309, 631], click: 3.8, from: [470, 470], zoom: 1.25 },
    { d: 5, t: 'Εμφανίζονται τα στοιχεία του υπογράφοντος. Κατεβείτε προς τα κάτω…', box: [150, 290, 830, 600], from: [1000, 300], zoom: 1.1 },
    { d: 4.5, t: '…και πατήστε «Συνέχεια».', box: [188, 472, 309, 521], click: 2.8, from: [600, 250], zoom: 1.3 },
    { d: 6, t: 'Πατήστε «Επιλέξτε αρχείο». Στη «Γλώσσα υπογραφής» διαλέξτε «Ελληνικά».', box: [205, 249, 346, 273], box2: [191, 374, 323, 413], click: 2.4, click2: 4.6, from: [700, 450], zoom: 1.3 },
    { d: 6, t: 'Βρείτε το PDF που κατεβάσατε (συνήθως στον φάκελο «Λήψεις») και πατήστε «Άνοιγμα».', box: [172, 238, 562, 262], box2: [415, 430, 496, 456], click: 2.4, click2: 4.6, from: [800, 500], zoom: 1.3 },
    { d: 6, t: 'Επιλέξτε «Με αποστολή SMS στο κινητό σας» (ή «Μέσω Gov.gr Wallet») και πατήστε «Συνέχεια».', box: [191, 557, 538, 596], click: 3.8, from: [700, 300], zoom: 1.2 },
    { d: 6, t: 'Γράψτε τον κωδικό που ήρθε με SMS και πατήστε «Επιβεβαίωση».', box: [188, 484, 821, 527], box2: [188, 567, 348, 617], click: 2.2, click2: 4.6, from: [800, 300], zoom: 1.25 },
    { d: 5, t: 'Έτοιμο! Το έγγραφο εκδόθηκε.', box: [188, 173, 821, 421], from: [1000, 560], zoom: 1.1 },
    { d: 5.5, t: 'Πατήστε «Αποθήκευση» για να κατεβάσετε το βεβαιωμένο PDF.', box: [870, 402, 1059, 451], click: 3.6, from: [600, 300], zoom: 1.3 },
    { d: 5, t: 'Το βεβαιωμένο PDF περιέχει το έντυπό σας, όπως το συμπληρώσατε…', box: [330, 100, 950, 600], from: [1100, 600], zoom: 1.0 },
    { d: 8, t: '…και στο κάτω μέρος τη σφραγίδα «Ψηφιακή Βεβαίωση Εγγράφου» με QR. Αυτό το αρχείο στέλνετε στο σχολείο.', box: [304, 496, 1047, 602], from: [1100, 300], zoom: 1.25 }
  ];

  function Guide(dialog, base) {
    var q = function (s) { return dialog.querySelector(s); };
    var scr = q('[data-vp-screen]'), stage = q('[data-vp-stage]'), layer = q('[data-vp-layer]'), shot = q('[data-vp-shot]');
    var hl = q('[data-vp-hl]'), cur = q('[data-vp-cur]'), rip = q('[data-vp-rip]'), segs = q('[data-vp-segs]');
    var big = q('[data-vp-big]'), playBtn = q('[data-vp-play]');
    var imgs = [], loaded = false, k = 1, T = 0, playing = false, last = 0, curI = -1, fired = {};
    var starts = [], tot = 0;
    GUIDE.forEach(function (s) { starts.push(tot); tot += s.d; });
    GUIDE.forEach(function (s, i) {
      var b = document.createElement('button');
      b.type = 'button'; b.className = 'sef-vp-seg'; b.setAttribute('aria-label', 'Βήμα ' + (i + 1));
      b.innerHTML = '<i></i>';
      b.addEventListener('click', function (e) { e.stopPropagation(); seek(i); });
      segs.appendChild(b);
    });
    function src(i) { return base + 'step-' + ('0' + (i + 1)).slice(-2) + '.webp'; }
    function load() { if (loaded) { return; } loaded = true; GUIDE.forEach(function (s, i) { var im = new Image(); im.src = src(i); imgs.push(im); }); }
    function fit() { k = scr.clientWidth / 1366; stage.style.transform = 'scale(' + k + ')'; }
    var ease = function (x) { return x < 0.5 ? 2 * x * x : 1 - Math.pow(-2 * x + 2, 2) / 2; };
    var clamp = function (x) { return Math.max(0, Math.min(1, x)); };
    var center = function (b) { return [(b[0] + b[2]) / 2, (b[1] + b[3]) / 2]; };
    var fmtT = function (s) { return Math.floor(s / 60) + ':' + ('0' + Math.floor(s % 60)).slice(-2); };
    function render() {
      var i = GUIDE.length - 1;
      for (var j = 0; j < GUIDE.length; j++) { if (T < starts[j] + GUIDE[j].d) { i = j; break; } }
      var s = GUIDE[i], t = Math.min(T - starts[i], s.d);
      if (i !== curI) { curI = i; shot.src = src(i); q('[data-vp-n]').textContent = (i + 1) + '/' + GUIDE.length; q('[data-vp-t]').textContent = s.t; }
      var second = s.box2 && t >= ((s.click || 0) + s.click2) / 2;
      var target = second ? s.box2 : s.box, c = center(target);
      var z = reduceMotion() ? 1 : 1 + (s.zoom - 1) * ease(clamp((t - 0.3) / 1.6));
      var cx = Math.max(683 / z, Math.min(1366 - 683 / z, c[0])), cy = Math.max(330 / z, Math.min(660 - 330 / z, c[1]));
      layer.style.transformOrigin = '0 0';
      layer.style.transform = z === 1 ? 'none' : 'translate(' + (683 - cx * z) + 'px,' + (330 - cy * z) + 'px) scale(' + z + ')';
      hl.style.left = (target[0] - 8) + 'px'; hl.style.top = (target[1] - 8) + 'px';
      hl.style.width = (target[2] - target[0] + 16) + 'px'; hl.style.height = (target[3] - target[1] + 16) + 'px';
      hl.classList.toggle('on', t > 1.2);
      var b1 = center(s.box), arrive = (s.click || 2.6) - 0.5, p;
      if (t < arrive) { var e = ease(clamp((t - 0.4) / (arrive - 0.4))); p = [s.from[0] + (b1[0] - s.from[0]) * e, s.from[1] + (b1[1] - s.from[1]) * e]; }
      else if (s.box2) { var b2 = center(s.box2), a2 = s.click2 - 0.5, st = (s.click || arrive) + 0.3, e2 = ease(clamp((t - st) / (a2 - st))); p = [b1[0] + (b2[0] - b1[0]) * e2, b1[1] + (b2[1] - b1[1]) * e2]; }
      else { p = b1; }
      cur.style.transform = 'translate(' + p[0] + 'px,' + (p[1] + 4) + 'px) scale(' + (1 / z) + ')';
      cur.style.opacity = (s.click || s.box2) ? 1 : 0;
      [s.click, s.click2].forEach(function (ct, n) {
        if (ct == null) { return; }
        var key = i + ':' + n;
        if (playing && t >= ct && t < ct + 0.2 && !fired[key]) { fired[key] = 1; rip.classList.remove('go'); void rip.offsetWidth; rip.classList.add('go'); }
      });
      Array.prototype.forEach.call(segs.children, function (b, j) {
        var pr = j < i ? 1 : j > i ? 0 : t / GUIDE[j].d;
        b.firstChild.style.width = (pr * 100) + '%';
      });
      q('[data-vp-time]').textContent = fmtT(T) + ' / ' + fmtT(tot);
    }
    function frame(ts) {
      if (!playing) { return; }
      var dt = Math.min(0.1, (ts - last) / 1000); last = ts; T += dt;
      if (T >= tot) { T = tot; render(); pause(); big.hidden = false; big.querySelector('em').textContent = 'Ξανά από την αρχή'; return; }
      render(); requestAnimationFrame(frame);
    }
    function play() { if (T >= tot) { T = 0; fired = {}; } playing = true; big.hidden = true; playBtn.textContent = '⏸'; playBtn.setAttribute('aria-label', 'Παύση'); last = performance.now(); requestAnimationFrame(frame); }
    function pause() { playing = false; playBtn.textContent = '▶'; playBtn.setAttribute('aria-label', 'Αναπαραγωγή'); }
    function seek(i) { i = Math.max(0, Math.min(GUIDE.length - 1, i)); T = starts[i] + 0.001; fired = {}; big.hidden = true; render(); if (!playing) { play(); } }
    function idx() { for (var j = GUIDE.length - 1; j >= 0; j--) { if (T >= starts[j]) { return j; } } return 0; }
    playBtn.addEventListener('click', function () { if (playing) { pause(); } else { play(); } });
    q('[data-vp-prev]').addEventListener('click', function () { var i = idx(); seek(T - starts[i] > 1.5 ? i : i - 1); });
    q('[data-vp-next]').addEventListener('click', function () { seek(idx() + 1); });
    big.addEventListener('click', function (e) { e.stopPropagation(); play(); });
    scr.addEventListener('click', function (e) { if (!e.target.closest('[data-vp-big]')) { if (playing) { pause(); } else { play(); } } });
    q('[data-sef-guide-close]').addEventListener('click', function () { close(); });
    dialog.addEventListener('close', function () { pause(); });
    dialog.addEventListener('click', function (e) { if (e.target === dialog) { close(); } });
    if (window.ResizeObserver) { new ResizeObserver(fit).observe(scr); }
    function open() {
      load();
      if (typeof dialog.showModal === 'function') { dialog.showModal(); } else { dialog.setAttribute('open', ''); }
      fit(); T = 0; fired = {}; curI = -1; render(); play();
    }
    function close() { pause(); if (typeof dialog.close === 'function') { dialog.close(); } else { dialog.removeAttribute('open'); } }
    return { open: open };
  }

  /* ---------------- Δημιουργία τελικού εγγράφου ---------------- */
  function exportHTML(sheet, blank) {
    var clone = sheet.cloneNode(true);
    if (blank) { clone.classList.add('sef-blank'); }
    clone.classList.add('sef-export');
    clone.removeAttribute('data-sef-sheet');
    clone.removeAttribute('onsubmit');
    var src = sheet.querySelectorAll('input,select,textarea');
    var dst = clone.querySelectorAll('input,select,textarea');
    Array.prototype.forEach.call(src, function (el, i) {
      var c = dst[i];
      if (el.type === 'checkbox' || el.type === 'radio') {
        var box = c.nextElementSibling;
        if (!blank && box && box.classList.contains('sef-box') && el.checked) { box.setAttribute('data-pdf-check', '1'); }
        c.remove();
        return;
      }
      if (el.type === 'hidden') { c.remove(); return; }
      if (blank && el.tagName === 'TEXTAREA') {
        // Κενό έντυπο: γραμμές για χειρόγραφη συμπλήρωση.
        var lines = document.createElement('div');
        lines.className = 'sef-blank-lines';
        var n = Math.max(2, Math.min(10, parseInt(el.getAttribute('rows'), 10) || 3));
        for (var k = 0; k < n; k++) { lines.appendChild(document.createElement('div')); }
        c.parentNode.replaceChild(lines, c);
        return;
      }
      var text;
      if (blank) {
        // Κενό έντυπο: μόνο σταθερές επιλογές τύπου «Ο – Η Δηλ.» μένουν (λίστες χωρίς κενή επιλογή).
        text = el.hasAttribute('data-blank-text') ? el.getAttribute('data-blank-text')
          : (el.tagName === 'SELECT' && el.hasAttribute('data-blank-keep') && el.value) ? el.options[el.selectedIndex].text
          : ((el.tagName === 'SELECT' && el.options.length && el.options[0].value !== '') ? el.options[0].text : '');
      } else if (el.tagName === 'SELECT') { text = el.value ? el.options[el.selectedIndex].text : ''; }
      else if (el.type === 'date') { text = fmtDate(el.value); }
      else { text = el.value; }
      var span = document.createElement(el.tagName === 'TEXTAREA' ? 'div' : 'span');
      span.className = ('sef-fv ' + (el.className || '').replace(/\bis-bad\b/g, '') + (el.tagName === 'TEXTAREA' ? ' blk' : '')).trim();
      span.textContent = (text || '').trim() || ' ';
      c.parentNode.replaceChild(span, c);
    });
    // Γραμμές που έμειναν εντελώς κενές (π.χ. 3ο–4ο πρόσωπο) δεν τυπώνονται στο συμπληρωμένο PDF.
    if (!blank) {
      var srcRows = sheet.querySelectorAll('[data-hide-empty]'), dstRows = clone.querySelectorAll('[data-hide-empty]');
      Array.prototype.forEach.call(srcRows, function (r, i) {
        var empty = Array.prototype.every.call(r.querySelectorAll('input,select,textarea'), function (x) { return !String(x.value || '').trim(); });
        if (empty && dstRows[i]) { dstRows[i].hidden = true; }
      });
    }
    // Συνημμένα: στο συμπληρωμένο PDF χωρίς κενές γραμμές· αν δεν δηλώθηκε τίποτα, η ενότητα δεν τυπώνεται.
    if (!blank) {
      var srcBoxes = sheet.querySelectorAll('[data-sef-attach]'), dstBoxes = clone.querySelectorAll('[data-sef-attach]');
      Array.prototype.forEach.call(srcBoxes, function (sb, i) {
        var db = dstBoxes[i]; if (!db) { return; }
        var other = sb.querySelector('.ap-attach-other input');
        var otherEmpty = !other || !String(other.value || '').trim();
        if (otherEmpty) { var o = db.querySelector('.ap-attach-other'); if (o) { o.remove(); } }
        if (!sb.querySelector('input[type="checkbox"]:checked') && otherEmpty) { db.hidden = true; }
      });
    }
    // Στο PDF μια οδηγία οθόνης («Περιγράψτε…») γίνεται ετικέτα («Είδος άδειας:»)
    Array.prototype.forEach.call(clone.querySelectorAll('[data-export-text]'), function (n) { n.textContent = n.getAttribute('data-export-text'); });
    if (blank) {
      Array.prototype.forEach.call(clone.querySelectorAll('[data-mirror]'), function (m) { m.textContent = ''; });
      // Κενό έντυπο: γενικό κείμενο και καμία επιλογή
      // Λίστες με data-blank-keep που έχουν επιλογή (είδος άδειας, περίπτωση απουσίας, είδος δήλωσης):
      // το κενό έντυπο βγαίνει ΓΙΑ ΑΥΤΗ την επιλογή — ό,τι εξαρτάται από αυτές μένει όπως στην οθόνη.
      var kept = {};
      Array.prototype.forEach.call(sheet.querySelectorAll('select[data-blank-keep]'), function (s) { if (s.value) { kept[s.name] = true; } });
      var depOf = function (n) {
        var e = n.getAttribute('data-show-if') || n.getAttribute('data-hide-if');
        if (e) { return e.split('=')[0].split('@')[0]; }
        return n.getAttribute('data-mirror-opt') || n.getAttribute('data-sef-attach') || n.getAttribute('data-blank-field') || '';
      };
      var free = function (n) { return !n.hasAttribute('data-blank-keep') && !kept[depOf(n)]; };
      Array.prototype.forEach.call(clone.querySelectorAll('[data-mirror-opt]'), function (m) { if (free(m)) { m.textContent = m.getAttribute('data-fallback') || ''; } });
      Array.prototype.forEach.call(clone.querySelectorAll('[data-show-if]'), function (n) { if (free(n)) { n.hidden = true; } });
      Array.prototype.forEach.call(clone.querySelectorAll('[data-hide-if]'), function (n) { if (free(n)) { n.hidden = false; } });
      Array.prototype.forEach.call(clone.querySelectorAll('[data-blank-show]'), function (n) { if (free(n)) { n.hidden = false; } });
      Array.prototype.forEach.call(clone.querySelectorAll('[data-blank-hide]'), function (n) { if (free(n)) { n.hidden = true; } });
      Array.prototype.forEach.call(clone.querySelectorAll('[data-sef-attach-list]'), function (n) { var b = n.closest('[data-sef-attach]'); if (!b || free(b)) { n.textContent = ''; } });
      Array.prototype.forEach.call(clone.querySelectorAll('[data-weekday]'), function (n) { n.textContent = 'την ………………'; });
      Array.prototype.forEach.call(clone.querySelectorAll('[data-plural]'), function (n) { n.textContent = n.getAttribute('data-many'); });
    }
    Array.prototype.forEach.call(clone.querySelectorAll('.sef-err, [data-sef-live]'), function (n) { n.remove(); });
    Array.prototype.forEach.call(clone.querySelectorAll('.is-bad-group'), function (n) { n.classList.remove('is-bad-group'); });
    Array.prototype.forEach.call(clone.querySelectorAll('label[for]'), function (l) { l.removeAttribute('for'); });
    Array.prototype.forEach.call(clone.querySelectorAll('[id]'), function (n) { n.removeAttribute('id'); });
    return clone.outerHTML;
  }
  /* Το CSS του εντύπου ενσωματώνεται ΜΕΣΑ στο πλαίσιο (όχι <link>), ώστε να μην
   * εξαρτάται από χρονισμό δικτύου, cache ή plugins που ενοποιούν τα CSS. */
  var cssPromise = null;
  function absolutize(css, base) {
    return css.replace(/url\(\s*(['"]?)([^'")]+)\1\s*\)/g, function (m, q, u) {
      if (/^(data:|https?:|\/\/)/i.test(u)) { return m; }
      try { return 'url("' + new URL(u, base).href + '")'; } catch (e) { return m; }
    });
  }
  function sheetCss(cfg) {
    if (cssPromise) { return cssPromise; }
    var fromDom = function () {
      // Εναλλακτικά: από το stylesheet που έχει ήδη φορτώσει η σελίδα.
      for (var i = 0; i < document.styleSheets.length; i++) {
        var sh = document.styleSheets[i];
        if (!sh.href || sh.href.indexOf('/assets/css/sheet.css') === -1) { continue; }
        try {
          var txt = Array.prototype.map.call(sh.cssRules, function (r) { return r.cssText; }).join('\n');
          if (txt) { return absolutize(txt, sh.href); }
        } catch (e) { /* cross-origin */ }
      }
      throw new Error('sheet.css unavailable');
    };
    cssPromise = fetch(cfg.sheetCss, { credentials: 'same-origin' }).then(function (r) {
      if (!r.ok) { throw new Error('HTTP ' + r.status); }
      return r.text();
    }).then(function (t) {
      if (t.indexOf('.sef-sheet') === -1) { throw new Error('bad css'); }
      return absolutize(t, cfg.sheetCss);
    }).catch(function () { return fromDom(); }).then(function (css) {
      // Η γραμματοσειρά φορτώνεται από bytes (installFonts)· όχι δεύτερη φορά από το δίκτυο.
      return css.replace(/@font-face\s*\{[^}]*\}/g, '');
    });
    cssPromise.catch(function () { cssPromise = null; });
    return cssPromise;
  }
  function frameDoc(html, css, bodyClass, title, toolbar) {
    var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
    return '<!doctype html><html lang="el"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
      '<title>' + esc(title) + '</title><style>' + css.replace(/<\/style/gi, '<\\/style') + '</style>' +
      '<style>html,body{margin:0;padding:0;background:#fff}' +
      '.sef-print-bar{position:sticky;top:0;z-index:2;display:flex;flex-wrap:wrap;gap:12px;align-items:center;padding:12px 18px;background:#eef3f8;border-bottom:1px solid #d5dde6;font:14px/1.4 system-ui,sans-serif;color:#16202c}' +
      '.sef-print-bar button{font:600 14px system-ui,sans-serif;border:0;border-radius:8px;background:#0d5eaf;color:#fff;padding:10px 16px;cursor:pointer}' +
      '@media screen{body.sef-print-mode{background:#e7ebf0}.sef-print-mode .sef-sheet{margin:20px auto;box-shadow:0 2px 16px rgba(0,0,0,.12)}}' +
      '</style></head><body class="' + bodyClass + '">' + (toolbar || '') + html + '</body></html>';
  }
  /* Φορτώνει τη γραμματοσειρά του εντύπου στο έγγραφο-στόχο από τα ΙΔΙΑ bytes που
   * ενσωματώνονται στο PDF, ώστε οι θέσεις των γραμμάτων να ταυτίζονται. */
  function installFonts(cfg, win, doc) {
    if (!win.FontFace || !doc.fonts || !window.SEFPDF || !window.SEFPDF.fontBytes) { return Promise.resolve(); }
    return Promise.all([
      window.SEFPDF.fontBytes('regular', cfg.fonts.regular),
      window.SEFPDF.fontBytes('bold', cfg.fonts.bold)
    ]).then(function (b) {
      var faces = [
        new win.FontFace('SEF Doc Sans', b[0].slice(0).buffer, { weight: '400', style: 'normal' }),
        new win.FontFace('SEF Doc Sans', b[1].slice(0).buffer, { weight: '700', style: 'normal' })
      ];
      return Promise.all(faces.map(function (f) { return f.load(); })).then(function (loaded) {
        loaded.forEach(function (f) { doc.fonts.add(f); });
      });
    });
  }
  function waitImages(doc) {
    return Promise.all(Array.prototype.map.call(doc.images, function (im) {
      return im.complete ? null : new Promise(function (r) { im.onload = im.onerror = r; setTimeout(r, 5000); });
    }));
  }
  /* Τελικός έλεγχος: ποτέ PDF χωρίς σωστή διάταξη και γραμματοσειρά. */
  function layoutReady(doc) {
    var sheet = doc.querySelector('.sef-sheet');
    if (!sheet) { return false; }
    var w = Math.round(sheet.getBoundingClientRect().width);
    var cs = doc.defaultView.getComputedStyle(sheet);
    var fontOk = !doc.fonts || doc.fonts.check('400 14px "SEF Doc Sans"');
    return w === 794 && cs.display === 'flex' && fontOk;
  }
  /* Αν το περιεχόμενο ξεπερνά λίγο τη σελίδα, «σφίγγει» τα διαστήματα ώστε να χωρά σε μία Α4. */
  function fitPage(sheet) {
    var limit = 1123;
    // Έντυπα δύο σελίδων: κάθε «αλλαγή σελίδας» μεγαλώνει ώστε το επόμενο μέρος να αρχίζει στην κορυφή νέας Α4.
    var breaks = sheet.querySelectorAll('[data-sef-pb]');
    if (breaks.length) {
      var PAGE = 1122.52, padTop = (parseFloat(getComputedStyle(sheet).paddingTop) || 0) - (sheet.classList.contains('sef-stamp') ? 76 : 0), s0 = sheet.getBoundingClientRect().top;
      Array.prototype.forEach.call(breaks, function (pb) {
        pb.style.height = '0px';
        var y = pb.getBoundingClientRect().top - s0;
        pb.style.height = Math.max(0, Math.ceil(y / PAGE) * PAGE - y + padTop) + 'px';
      });
      return;
    }
    if (sheet.scrollHeight <= limit + 1) { return; }
    sheet.classList.add('sef-fit-1');
    if (sheet.scrollHeight <= limit + 1) { return; }
    sheet.classList.add('sef-fit-2');
    if (sheet.scrollHeight <= limit + 1) { return; }
    sheet.classList.add('sef-fit-3'); // μακροσκελή κείμενα (π.χ. δήλωση αποχώρησης)
  }
  function makePdf(cfg, html, title) {
    if (!window.SEFPDF) { return Promise.reject(new Error('PDF module missing')); }
    var ifr = null;
    return sheetCss(cfg).then(function (css) {
      ifr = document.createElement('iframe');
      ifr.setAttribute('aria-hidden', 'true'); ifr.setAttribute('tabindex', '-1'); ifr.title = 'pdf';
      ifr.style.cssText = 'position:fixed;left:-10000px;top:0;width:820px;height:1200px;border:0;pointer-events:none;opacity:0';
      document.body.appendChild(ifr);
      var doc = ifr.contentDocument;
      doc.open(); doc.write(frameDoc(html, css, 'sef-pdf-mode', title)); doc.close();
      return installFonts(cfg, ifr.contentWindow, doc).then(function () { return waitImages(doc); }).then(function () {
        // Αναμονή έως 3″ να «κάτσει» η διάταξη· αλλιώς ακύρωση (ποτέ λάθος PDF).
        return new Promise(function (resolve, reject) {
          var t0 = Date.now();
          (function poll() {
            if (layoutReady(doc)) { resolve(); return; }
            if (Date.now() - t0 > 3000) { reject(new Error('Η μορφοποίηση του εντύπου δεν φορτώθηκε')); return; }
            setTimeout(poll, 100);
          })();
        });
      }).then(function () {
        var sheet = doc.querySelector('.sef-sheet');
        fitPage(sheet);
        return window.SEFPDF.render(sheet, { title: title, fonts: cfg.fonts, creator: 'School e-Forms · ' + cfg.school });
      });
    }).then(function (blob) { if (ifr) { ifr.remove(); } return blob; }, function (err) { if (ifr) { ifr.remove(); } throw err; });
  }
  function download(blob, name) {
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url; a.download = name; a.rel = 'noopener'; a.style.display = 'none';
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(function () { URL.revokeObjectURL(url); }, 15000);
  }

  /* ---------------- Ένα έντυπο ---------------- */
  function FormView(app, view, cfg, fcfg, id) {
    var sheet = view.querySelector('[data-sef-sheet]');
    var stepper = Array.prototype.slice.call(view.querySelectorAll('[data-sef-stepper]'));
    var nSteps = stepper.length;
    var done = {};
    var dirty = false, pdfMade = false;
    var sendStep = view.querySelector('[data-sef-send]');
    var sendN = sendStep ? Number(sendStep.getAttribute('data-sef-step')) : 0;
    var checkedFile = null;

    function setDone(n, v) {
      done[n] = !!v;
      var current = 0;
      for (var i = 1; i <= nSteps; i++) { if (!done[i]) { current = i; break; } }
      stepper.forEach(function (li) {
        var k = Number(li.getAttribute('data-sef-stepper'));
        var st = done[k] ? 'done' : k === current ? 'current' : 'todo';
        li.setAttribute('data-state', st);
        li.querySelector('.sef-dot').textContent = done[k] ? '✓' : String(k);
      });
      Array.prototype.forEach.call(view.querySelectorAll('[data-sef-step]'), function (sec) {
        var k = Number(sec.getAttribute('data-sef-step'));
        sec.setAttribute('data-state', done[k] ? 'done' : k === current ? 'current' : 'todo');
        var num = sec.querySelector('.sef-num'); if (num) { num.textContent = done[k] ? '✓' : String(k); }
      });
    }

    function field(name) { return sheet ? sheet.elements[name] : null; }
    function valueOf(name) {
      var el = field(name);
      if (!el) { return ''; }
      if (el.tagName === 'SELECT') { return el.value ? el.options[el.selectedIndex].text : ''; }
      if (typeof el.length === 'number' && !el.tagName) { // RadioNodeList
        var v = el.value; return v || '';
      }
      if (el.type === 'date') { return fmtDate(el.value); }
      return (el.value || '').trim();
    }
    function fillTemplate(tpl, mode) {
      // mode 'text' → απλό κείμενο, 'html' → με «κουκίδες» για όσα λείπουν
      // {πεδίο|Ετικέτα} → τιμή ή ετικέτα-κράτησης · {πεδίο@attr=προεπιλογή} → data-attr της επιλογής λίστας ή η προεπιλογή
      return String(tpl || '').replace(/\{([a-z0-9_]+)(?:@([a-z0-9_]+)(?:=([^}|]*))?)?(?:\|([^}]*))?\}/gi, function (m, name, attr, def, label) {
        if (attr) { var a = optAttr(name, attr); return a ? (mode === 'html' ? escHtml(a) : a) : (mode === 'html' ? escHtml(def || '') : (def || '')); }
        var v = valueOf(name);
        if (v) { return mode === 'html' ? escHtml(v) : v; }
        if (!label) { return ''; }
        return mode === 'html' ? '<span class="sef-ph">' + escHtml(label) + '</span>' : '[' + label + ']';
      }).replace(/\s+–\s+$/, '').replace(/\s{2,}/g, ' ').trim();
    }
    function escHtml(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function filename() {
      var base = String(fcfg.filename || 'Έντυπο').replace(/\{([a-z0-9_]+)\}/gi, function (m, n) { return valueOf(n); });
      base = base.replace(/[\\\/:*?"<>|]+/g, '').replace(/\s+/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
      return (base || 'Έντυπο') + '.pdf';
    }

    /* --- Αυτοματισμοί πάνω στο φύλλο --- */
    function applyDefaults() {
      if (!sheet) { return; }
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-today]'), function (el) { if (!el.value) { el.value = iso(new Date()); } });
      mirrors();
      Array.prototype.forEach.call(sheet.querySelectorAll('textarea'), grow);
    }
    function optAttr(name, attr) {
      var el = field(name);
      if (!el || el.tagName !== 'SELECT' || !el.value) { return ''; }
      return el.options[el.selectedIndex].getAttribute('data-' + attr) || '';
    }
    function mirrors() {
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-mirror]'), function (m) {
        m.textContent = m.getAttribute('data-mirror').split(/\s+/).map(valueOf).filter(Boolean).join(' ');
      });
      // Κείμενο που εξαρτάται από την επιλογή λίστας (π.χ. ΘΕΜΑ ανά είδος άδειας)
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-mirror-opt]'), function (m) {
        m.textContent = optAttr(m.getAttribute('data-mirror-opt'), m.getAttribute('data-opt-attr')) || m.getAttribute('data-fallback') || '';
      });
      // «την Τρίτη» / «το Σάββατο» από ημερομηνία
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-weekday]'), function (m) {
        var f = field(m.getAttribute('data-weekday'));
        var d = f && /^\d{4}-\d{2}-\d{2}$/.test(f.value) ? new Date(f.value + 'T12:00:00') : null;
        m.textContent = d ? WEEKDAYS[d.getDay()] : '';
      });
      // «ημέρας» / «ημερών» ανάλογα με τον αριθμό
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-plural]'), function (m) {
        var f = field(m.getAttribute('data-plural'));
        m.textContent = f && parseInt(f.value, 10) === 1 ? m.getAttribute('data-one') : m.getAttribute('data-many');
      });
      attachments();
      conditions();
    }
    // «πεδίο=τιμή» ή «πεδίο@attr=τιμή» (data-attr της επιλεγμένης επιλογής λίστας)
    function cond(expr) {
      var i = String(expr || '').indexOf('='); if (i < 1) { return false; }
      // «κλειδί=α|β»: αληθές για οποιαδήποτε από τις τιμές (0.9.9, π.χ. kids=3|4).
      var key = expr.slice(0, i), want = expr.slice(i + 1).split('|'), at = key.indexOf('@');
      if (at > 0) { return want.indexOf(optAttr(key.slice(0, at), key.slice(at + 1))) >= 0; }
      var el = field(key); return !!el && want.indexOf(el.value) >= 0;
    }
    /* Συνημμένα ανά επιλογή: κουτάκια από το data-docs («Α|Β») της επιλεγμένης επιλογής */
    function attachments() {
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-sef-attach]'), function (box) {
        var list = box.querySelector('[data-sef-attach-list]');
        var docs = optAttr(box.getAttribute('data-sef-attach'), 'docs');
        var sel = field(box.getAttribute('data-sef-attach'));
        box.hidden = !(sel && sel.value);
        if (list.getAttribute('data-built') === docs) { return; }
        list.setAttribute('data-built', docs);
        list.textContent = '';
        var names = docs ? docs.split('|').filter(Boolean) : [];
        names.forEach(function (nm, i) {
          var lab = document.createElement('label'); lab.className = 'sef-chk';
          var inp = document.createElement('input'); inp.type = 'checkbox'; inp.name = 'att_' + i; inp.value = nm;
          inp.id = (sheet.id || id) + '-att-' + i;
          var bx = document.createElement('span'); bx.className = 'sef-box'; bx.setAttribute('aria-hidden', 'true');
          var tx = document.createElement('span'); tx.textContent = nm;
          lab.appendChild(inp); lab.appendChild(bx); lab.appendChild(tx); list.appendChild(lab);
        });
      });
    }
    /* Εμφάνιση/απόκρυψη και υποχρεωτικό ανάλογα με επιλογή (data-show-if, data-hide-if, data-req-if) */
    function conditions() {
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-show-if]'), function (n) { n.hidden = !cond(n.getAttribute('data-show-if')); });
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-hide-if]'), function (n) { n.hidden = cond(n.getAttribute('data-hide-if')); });
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-req-if]'), function (el) {
        var on = cond(el.getAttribute('data-req-if'));
        if (!el.hasAttribute('data-label-off')) { el.setAttribute('data-label-off', el.getAttribute('aria-label') || ''); el.setAttribute('data-ph-off', el.getAttribute('placeholder') || ''); }
        var lab = on ? el.getAttribute('data-label-on') : el.getAttribute('data-label-off');
        var ph = on ? el.getAttribute('data-ph-on') : el.getAttribute('data-ph-off');
        if (lab) { el.setAttribute('aria-label', lab); }
        if (ph !== null) { el.setAttribute('placeholder', ph); }
        if (on) { el.setAttribute('data-req', '1'); el.setAttribute('aria-required', 'true'); }
        else if (el.hasAttribute('data-req')) {
          el.removeAttribute('data-req'); el.removeAttribute('aria-required');
          if (el.classList.contains('is-bad')) { clearError(el, false); }
        }
      });
    }
    function grow(ta) { ta.style.height = 'auto'; ta.style.height = (ta.scrollHeight + 2) + 'px'; }
    var touched = {};
    /* --- Μη εργάσιμες ημέρες σχολικού έτους (για άδειες σε εργάσιμες) --- */
    // Ορθόδοξο Πάσχα (Meeus, ιουλιανό + 13 ημέρες· ισχύει 1900–2099).
    var easterCache = {};
    function orthEaster(y) {
      if (easterCache[y]) { return easterCache[y]; }
      var a = y % 4, b = y % 7, c = y % 19, d = (19 * c + 15) % 30, e = (2 * a + 4 * b - d + 34) % 7;
      var m = Math.floor((d + e + 114) / 31), day = ((d + e + 114) % 31) + 1;
      var dt = new Date(y, m - 1, day, 12); dt.setDate(dt.getDate() + 13);
      return (easterCache[y] = dt);
    }
    function dayDiff(a, b) { return Math.round((a - b) / 864e5); }
    function holiday(d) {
      var y = d.getFullYear(), m = d.getMonth() + 1, dd = d.getDate();
      if ((m === 12 && dd >= 24) || (m === 1 && dd <= 7)) { return true; }  // διακοπές Χριστουγέννων 24/12–7/1
      if ((m === 3 && dd === 25) || (m === 10 && dd === 28) || (m === 5 && dd === 1)) { return true; } // 25η Μαρτίου, 28η Οκτωβρίου, 1η Μαΐου
      // Η 30ή Ιανουαρίου (Τριών Ιεραρχών) είναι εργάσιμη (οδηγία χρήστη 12-11-2026).
      var k = dayDiff(new Date(y, m - 1, dd, 12), orthEaster(y));
      if (k === -48 || k === 50) { return true; }                            // Καθαρά Δευτέρα, Αγίου Πνεύματος
      if (k >= -6 && k <= 7) { return true; }                                // Μεγάλη Δευτέρα έως Κυριακή του Θωμά
      var loc = cfg.holidays || [];
      for (var i = 0; i < loc.length; i++) {
        if (loc[i][0] === dd && loc[i][1] === m && (!loc[i][2] || loc[i][2] === y)) { return true; } // τοπικές αργίες
      }
      return false;
    }
    function setupDays() {
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-days]'), function (box) {
        var o; try { o = JSON.parse(box.getAttribute('data-days')); } catch (e) { return; }
        var from = field(o.from), to = field(o.to), out = field(o.n), wout = o.words ? field(o.words) : null;
        if (!from || !to || !out) { return; }
        // mode «weekdays»: μόνο εργάσιμες. modeBy: το είδος (π.χ. άδειας) αποφασίζει με data-work στην επιλογή του.
        var by = o.modeBy ? field(o.modeBy) : null;
        var onlyWork = function () {
          if (o.mode === 'weekdays') { return true; }
          var op = by && by.options ? by.options[by.selectedIndex] : null;
          return !!(op && op.getAttribute('data-work') === '1');
        };
        var workday = function (d) { var w = d.getDay(); return !onlyWork() || (w !== 0 && w !== 6 && !holiday(d)); };
        // Ημέρες → ημερομηνία λήξης (ημερολογιακές, ή εργάσιμες στο mode «weekdays»)
        var endFromDays = function () {
          var n = parseInt(out.value, 10);
          if (!from.value || !(n > 0) || n > 366) { return false; }
          var d = new Date(from.value + 'T12:00:00'), c = 0;
          for (var guard = 0; guard < 800; guard++) {
            if (workday(d)) { c++; if (c === n) { break; } }
            d.setDate(d.getDate() + 1);
          }
          to.value = iso(d);
          if (to.classList.contains('is-bad')) { clearError(to, false); }
          return true;
        };
        out.addEventListener('input', function (e) {
          if (e.isTrusted) { touched[o.n] = out.value !== ''; if (touched[o.n]) { endFromDays(); } }
          if (wout && !touched[o.words]) { wout.value = (o.case === 'gen' ? wordsGen : words)(parseInt(out.value, 10) || 0); }
        });
        if (wout) { wout.addEventListener('input', function (e) { if (e.isTrusted) { touched[o.words] = wout.value !== ''; } }); }
        // Ημερομηνίες → ημέρες
        var calc = function () {
          if (from.value && !to.value) { to.value = from.value; }
          if (!from.value || !to.value) { return; }
          var a = new Date(from.value + 'T12:00:00'), b = new Date(to.value + 'T12:00:00');
          if (b < a) { return; }
          var n = 0;
          for (var d = new Date(a); d <= b; d.setDate(d.getDate() + 1)) { if (workday(d)) { n++; } }
          if (n > 0) {
            out.value = String(n);
            if (out.classList.contains('is-bad')) { clearError(out, false); }
            if (wout && !touched[o.words]) { wout.value = (o.case === 'gen' ? wordsGen : words)(n); wout.classList.remove('is-bad'); }
          } else {
            // Όλο το διάστημα μη εργάσιμες (Σ/Κ, αργία, διακοπές): καμία ημέρα — όχι παλιός αριθμός.
            out.value = '';
            if (wout && !touched[o.words]) { wout.value = ''; }
          }
        };
        // Αλλαγή «από»: αν ο χρήστης έγραψε ημέρες, μετακινείται η λήξη· αλλιώς υπολογίζονται οι ημέρες.
        from.addEventListener('change', function () { if (!(touched[o.n] && endFromDays())) { calc(); } });
        // Αλλαγή «έως» με το χέρι: οι ημερομηνίες υπερισχύουν, οι ημέρες ξαναϋπολογίζονται.
        to.addEventListener('change', function () { touched[o.n] = false; calc(); });
        // Αλλαγή είδους: με ημερομηνίες συμπληρωμένες, οι ημέρες ξαναμετριούνται (π.χ. κανονική → χωρίς Σ/Κ).
        if (by) { by.addEventListener('change', function () { if (from.value && to.value) { touched[o.n] = false; calc(); } }); }
      });
    }

    /* --- Έλεγχος υποχρεωτικών --- */
    function labelFor(el) {
      if (el.getAttribute('aria-label')) { return el.getAttribute('aria-label'); }
      var l = el.id ? sheet.querySelector('label[for="' + el.id + '"]') : null;
      return l ? l.textContent.replace(/[:*()\d]+$/g, '').replace(/\s+/g, ' ').trim() : (el.placeholder || el.name);
    }
    /* --- Λάθη δίπλα στο πεδίο (WCAG 3.3.1/3.3.3): aria-invalid + aria-describedby --- */
    var live = document.createElement('p');
    live.className = 'sef-sr'; live.setAttribute('aria-live', 'polite'); live.setAttribute('data-sef-live', '');
    view.appendChild(live);
    function announce(msg) { live.textContent = ''; setTimeout(function () { live.textContent = msg; }, 60); }
    var errSeq = 0;
    function controlsOf(el, group) { return group ? Array.prototype.slice.call(el.querySelectorAll('input')) : [el]; }
    function setError(el, msg, group) {
      el.classList.add(group ? 'is-bad-group' : 'is-bad');
      var err = el._sefErr;
      if (!err) {
        err = document.createElement('span');
        err.className = 'sef-err';
        err.id = view.getAttribute('data-sef-view') + '-err-' + (++errSeq);
        if (group) { el.appendChild(err); } else { el.parentNode.insertBefore(err, el.nextSibling); }
        el._sefErr = err;
      }
      err.textContent = msg;
      controlsOf(el, group).forEach(function (c) {
        c.setAttribute('aria-invalid', 'true');
        var d = (c.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
        if (d.indexOf(err.id) === -1) { d.push(err.id); c.setAttribute('aria-describedby', d.join(' ')); }
      });
    }
    function clearError(el, group) {
      if (!el) { return; }
      el.classList.remove('is-bad', 'is-bad-group');
      var err = el._sefErr;
      controlsOf(el, group).forEach(function (c) {
        c.removeAttribute('aria-invalid');
        if (err) {
          var d = (c.getAttribute('aria-describedby') || '').split(/\s+/).filter(function (x) { return x && x !== err.id; });
          if (d.length) { c.setAttribute('aria-describedby', d.join(' ')); } else { c.removeAttribute('aria-describedby'); }
        }
      });
      if (err) { err.remove(); el._sefErr = null; }
    }
    function clearAllErrors() {
      Array.prototype.forEach.call(sheet.querySelectorAll('.is-bad'), function (el) { clearError(el, false); });
      Array.prototype.forEach.call(sheet.querySelectorAll('.is-bad-group'), function (el) { clearError(el, true); });
    }
    /* Έλεγχος μορφής: τηλέφωνο 10 ψηφία (δέχεται +30 / 0030), email. */
    function formatError(el) {
      var v = String(el.value || '').trim();
      if (!v) { return ''; }
      if (el.type === 'tel') {
        var d = v.replace(/[\s\-().]/g, '').replace(/^(\+30|0030)/, '');
        if (!/^\d{10}$/.test(d)) { return 'Το τηλέφωνο πρέπει να έχει 10 ψηφία, π.χ. 6912345678.'; }
      }
      if (el.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) { return 'Το email δεν είναι έγκυρο, π.χ. onoma@example.gr.'; }
      return '';
    }
    // Στοιχείο κρυμμένο μέσα στο φύλλο (π.χ. κείμενο άλλου είδους δήλωσης): δεν ελέγχεται.
    function off(el) { for (var n = el; n && n !== sheet; n = n.parentElement) { if (n.hidden) { return true; } } return false; }
    function missing() {
      var out = [];
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-req]'), function (el) {
        if (off(el)) { return; }
        if (!String(el.value || '').trim()) { out.push({ el: el, label: labelFor(el), msg: 'Υποχρεωτικό πεδίο: ' + labelFor(el) + '.' }); }
      });
      Array.prototype.forEach.call(sheet.querySelectorAll('input[type="tel"], input[type="email"]'), function (el) {
        var m = formatError(el);
        if (m) { out.push({ el: el, label: labelFor(el), msg: m }); }
      });
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-req-group]'), function (g) {
        if (off(g)) { return; }
        if (!g.querySelector('input:checked')) { out.push({ el: g, label: g.getAttribute('aria-label') || 'Επιλογή', group: true, msg: 'Επιλέξτε μία από τις επιλογές.' }); }
      });
      Array.prototype.forEach.call(sheet.querySelectorAll('[data-req-min]'), function (g) {
        if (off(g)) { return; }
        if (g.querySelectorAll('input:checked').length < Number(g.getAttribute('data-req-min'))) { out.push({ el: g, label: g.getAttribute('aria-label') || 'Επιλογή', group: true, msg: 'Επιλέξτε τουλάχιστον μία επιλογή.' }); }
      });
      var from = sheet.querySelector('[data-days]');
      if (from) {
        var o; try { o = JSON.parse(from.getAttribute('data-days')); } catch (e) { o = null; }
        if (o && field(o.from) && field(o.to) && field(o.from).value && field(o.to).value && field(o.to).value < field(o.from).value) {
          out.push({ el: field(o.to), label: 'Ημερομηνία λήξης', msg: 'Η τελική ημερομηνία είναι πριν από την αρχική.' });
        }
      }
      return out;
    }
    // Το κουτί «Χρειάζονται διορθώσεις» ακολουθεί τη συμπλήρωση: όσα διορθώθηκαν φεύγουν, χωρίς τίποτα → κρύβεται.
    var forceAction = null;
    function refreshFill() {
      var list = missing();
      setDone(1, list.length === 0);
      if (missingBox && !missingBox.hidden) {
        if (!list.length) { missingBox.hidden = true; missingBox.innerHTML = ''; } else { renderMissing(list); }
      }
    }

    /* --- Θέμα email / σύνδεσμος mailto --- */
    function refreshSubject() {
      var box = view.querySelector('[data-sef-subject]');
      if (box) { box.innerHTML = fillTemplate(fcfg.subject, 'html'); }
      var mailTo = fcfg.email || cfg.email; // έντυπο Μουσικού σε Δημοτικό: email του Μουσικού
      if (mailTo) {
        var subj = fillTemplate(fcfg.subject, 'text'), to = encodeURIComponent(mailTo), su = encodeURIComponent(subj);
        var urls = {
          gmail: 'https://mail.google.com/mail/?view=cm&fs=1&to=' + to + '&su=' + su,
          outlook: 'https://outlook.live.com/mail/0/deeplink/compose?to=' + to + '&subject=' + su,
          mailto: 'mailto:' + mailTo + '?subject=' + su
        };
        Array.prototype.forEach.call(view.querySelectorAll('[data-sef-compose]'), function (a) { a.href = urls[a.getAttribute('data-sef-compose')]; });
      }
      var inp = view.querySelector('[data-sef-subject-input]');
      if (inp && !inp.getAttribute('data-touched')) { inp.value = fillTemplate(fcfg.subject, 'text').replace(/\[[^\]]*\]/g, '').replace(/\s+–\s*(–\s*)*$/, '').replace(/\s{2,}/g, ' ').trim(); }
    }

    /* --- PDF / εκτύπωση --- */
    var missingBox = view.querySelector('[data-sef-missing]');
    var pdfStatus = view.querySelector('[data-sef-pdf-status]');
    function showMissing(list, onForce) {
      forceAction = onForce;
      clearAllErrors();
      list.forEach(function (m) { setError(m.el, m.msg, m.group); });
      renderMissing(list);
      var first = list[0]; scrollTo(first.el, 'center');
      var f = first.group ? first.el.querySelector('input') : first.el; if (f) { setTimeout(function () { f.focus({ preventScroll: true }); }, 350); }
    }
    function renderMissing(list) {
      missingBox.innerHTML = '';
      var h = document.createElement('p');
      h.innerHTML = '<b>Χρειάζονται διορθώσεις σε ' + list.length + (list.length === 1 ? ' σημείο' : ' σημεία') + ':</b>';
      var ul = document.createElement('ul');
      list.slice(0, 8).forEach(function (m) {
        var li = document.createElement('li'), b = document.createElement('button');
        b.type = 'button'; b.textContent = m.label;
        b.addEventListener('click', function () { scrollTo(m.el, 'center'); var f = m.group ? m.el.querySelector('input') : m.el; if (f) { f.focus({ preventScroll: true }); } });
        li.appendChild(b); ul.appendChild(li);
      });
      var force = document.createElement('button');
      force.type = 'button'; force.className = 'sef-btn sef-btn-ghost sef-btn-sm';
      force.textContent = 'Συνέχεια χωρίς αυτά';
      force.addEventListener('click', function () { missingBox.hidden = true; if (forceAction) { forceAction(); } });
      missingBox.appendChild(h); missingBox.appendChild(ul); missingBox.appendChild(force);
      missingBox.hidden = false;
    }
    function guard(action) {
      var list = missing();
      if (list.length) { showMissing(list, action); return; }
      missingBox.hidden = true; action();
    }
    function status(el, msg, kind) { if (!el) { return; } el.textContent = msg || ''; el.className = 'sef-status' + (kind ? ' is-' + kind : ''); }
    var busy = false;
    function doPdf() {
      if (busy) { return; }
      busy = true;
      var btn = view.querySelector('[data-sef-pdf]'); btn.disabled = true; btn.classList.add('is-busy');
      status(pdfStatus, 'Δημιουργία PDF…');
      makePdf(cfg, exportHTML(sheet), fcfg.title).then(function (blob) {
        download(blob, filename());
        pdfMade = true; setDone(2, true);
        status(pdfStatus, '✓ Το PDF αποθηκεύτηκε (' + filename() + '). ' + ('print' === fcfg.mode ? 'Εκτυπώστε το και υπογράψτε το.' : 'Επόμενο βήμα: η βεβαίωση στο gov.gr.'), 'ok');
        var next = view.querySelector('[data-sef-step="3"]'); if (next) { setTimeout(function () { scrollTo(next, 'center'); flash(next); }, 600); }
      }).catch(function (err) {
        if (window.console) { console.error('[School e-Forms]', err); }
        status(pdfStatus, 'Δεν ήταν δυνατή η δημιουργία PDF (' + (err && err.message ? err.message : 'σφάλμα') + '). Ανανεώστε τη σελίδα και δοκιμάστε ξανά ή πατήστε «Εκτύπωση» → «Αποθήκευση ως PDF».', 'err');
      }).then(function () { busy = false; btn.disabled = false; btn.classList.remove('is-busy'); });
    }
    function doPrint() {
      var w = window.open('', '_blank');   // συγχρόνως με το κλικ, για να μην μπλοκαριστεί
      if (!w) { status(pdfStatus, 'Ο browser εμπόδισε το νέο παράθυρο. Επιτρέψτε τα αναδυόμενα παράθυρα για αυτή τη σελίδα.', 'err'); return; }
      w.document.write('<!doctype html><meta charset="utf-8"><title>…</title><p style="font:16px system-ui,sans-serif;padding:24px">Προετοιμασία εκτύπωσης…</p>');
      var html = exportHTML(sheet);
      var bar = '<div class="sef-print-bar"><button type="button" onclick="window.print()">Εκτύπωση / Αποθήκευση ως PDF</button><span>Στον προορισμό επιλέξτε τον εκτυπωτή ή «Αποθήκευση ως PDF». Χωρίς κεφαλίδες/υποσέλιδα.</span></div>';
      sheetCss(cfg).then(function (css) {
        if (w.closed) { return null; }
        w.document.open();
        w.document.write(frameDoc(html, css, 'sef-print-mode', fcfg.title, bar));
        w.document.close();
        return installFonts(cfg, w, w.document).then(function () { return waitImages(w.document); }).then(function () {
          if (w.closed) { return; }
          var sh = w.document.querySelector('.sef-sheet'); if (sh) { fitPage(sh); }
          w.focus(); w.print();
        });
      }).catch(function () {
        if (!w.closed) { w.close(); }
        status(pdfStatus, 'Δεν φορτώθηκε η μορφοποίηση του εντύπου. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.', 'err');
      });
      pdfMade = true; setDone(2, true);
    }

    /* --- Έλεγχος αρχείου (τοπικά) --- */
    var checker = view.querySelector('[data-sef-checker]');
    function setupChecker() {
      if (!checker) { return; }
      var input = checker.querySelector('[data-sef-check-file]');
      var out = checker.querySelector('[data-sef-check-result]');
      var nameEl = checker.querySelector('[data-sef-file-name]');
      var drop = checker.querySelector('.sef-drop');
      ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.add('is-over'); }); });
      ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.remove('is-over'); }); });
      input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        checkedFile = null;
        if (!file) { out.hidden = true; nameEl.textContent = 'Δεν έχει επιλεγεί αρχείο'; return; }
        nameEl.textContent = file.name + ' · ' + sizeText(file.size);
        var upInfo = view.querySelector('[data-sef-upload-file]');
        window.SEFPDF.inspect(file).then(function (r) {
          var cls = 'bad', msg;
          if (!r.pdf) { msg = 'Το αρχείο δεν είναι PDF.'; }
          else if (r.signed) { cls = 'good'; msg = '✓ Το αρχείο φέρει ψηφιακή υπογραφή — είναι το βεβαιωμένο από το gov.gr. Μπορείτε να το στείλετε.'; checkedFile = file; }
          else if (fcfg.gov !== 'required' && fcfg.kind !== 'external') {
            // Η βεβαίωση gov.gr δεν απαιτείται για αυτό το έντυπο.
            cls = 'good'; checkedFile = file;
            msg = r.unsignedOriginal ? '✓ Είναι το PDF του εντύπου. Μπορείτε να το στείλετε.' : '✓ Το αρχείο είναι PDF. Μπορείτε να το στείλετε.';
          }
          else if (fcfg.govStrict) {
            // Έντυπα που γίνονται δεκτά ΜΟΝΟ με βεβαίωση gov.gr (π.χ. αίτηση άδειας): έντονη προειδοποίηση.
            cls = 'bad is-strong';
            msg = r.unsignedOriginal
              ? '✕ ΠΡΟΣΟΧΗ: αυτό είναι το αρχικό PDF από αυτή τη σελίδα, χωρίς ψηφιακή βεβαίωση. Με email η αίτηση ΔΕΝ γίνεται δεκτή. Ανεβάστε το στο gov.gr (βήμα 3) και στείλτε το αρχείο που θα σας δώσει — ή καταθέστε την υπογεγραμμένη στο σχολείο.'
              : '✕ ΠΡΟΣΟΧΗ: το αρχείο δεν φέρει ψηφιακή υπογραφή gov.gr. Με email η αίτηση ΔΕΝ γίνεται δεκτή. Επιλέξτε το αρχείο που κατεβάσατε από το gov.gr μετά τη βεβαίωση.';
          }
          else if (r.unsignedOriginal) { msg = 'Αυτό είναι το αρχικό PDF από αυτή τη σελίδα, όχι το βεβαιωμένο. Ανεβάστε το στο gov.gr (προηγούμενο βήμα) και επιλέξτε εδώ το αρχείο που θα σας δώσει.'; }
          else { cls = 'warn'; msg = 'Δεν εντοπίστηκε ψηφιακή υπογραφή. Βεβαιωθείτε ότι επιλέξατε το αρχείο που κατεβάσατε από το gov.gr μετά τη βεβαίωση.'; checkedFile = file; }
          if (r.pdf && fcfg.mode === 'upload' && file.size > cfg.maxBytes) { cls = 'bad'; msg = 'Το αρχείο (' + sizeText(file.size) + ') ξεπερνά το όριο αποστολής (' + sizeText(cfg.maxBytes) + '). Στείλτε το με email.'; checkedFile = null; }
          out.className = 'sef-fcheck is-' + cls; out.textContent = msg; out.hidden = false;
          out.setAttribute('role', /is-strong/.test(cls) ? 'alert' : 'status');
          if (upInfo) { upInfo.textContent = checkedFile ? 'Θα σταλεί: ' + file.name + ' (' + sizeText(file.size) + ')' : 'Επιλέξτε έγκυρο PDF στον «Έλεγχο αρχείου» παραπάνω.'; upInfo.classList.toggle('is-ok', !!checkedFile); }
          if (r.signed) {
            var govStep = fcfg.kind === 'external' ? 1 : (fcfg.gov === 'none' ? 0 : 3);
            if (govStep) { setDone(govStep, true); }
          }
        });
      });
    }

    /* --- Αποστολή από την ιστοσελίδα --- */
    var upload = view.querySelector('[data-sef-upload]');
    var challengeLoaded = false;
    function loadChallenge() {
      if (!upload) { return; }
      var q = upload.querySelector('[data-sef-question]');
      q.textContent = 'Ερώτηση ελέγχου: φόρτωση…';
      var fd = new FormData(); fd.append('action', 'sef_challenge');
      fetch(cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin', cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (j) {
        if (!j || !j.success) { throw new Error('challenge'); }
        upload.querySelector('[name="sef_cid"]').value = j.data.cid;
        upload.setAttribute('data-nonce', j.data.nonce);
        upload.querySelector('[name="sef_answer"]').value = '';
        q.textContent = 'Ερώτηση ελέγχου: ' + j.data.question;
        challengeLoaded = true;
      }).catch(function () { q.textContent = 'Η αποστολή δεν είναι διαθέσιμη αυτή τη στιγμή — στείλτε με email.'; });
    }
    function setupUpload() {
      if (!upload) { return; }
      var st = upload.querySelector('[data-sef-send-status]');
      var subj = upload.querySelector('[data-sef-subject-input]');
      subj.addEventListener('input', function () { subj.setAttribute('data-touched', '1'); });
      upload.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!checkedFile) { status(st, 'Επιλέξτε πρώτα το αρχείο από το gov.gr στον «Έλεγχο αρχείου».', 'err'); scrollTo(checker, 'center'); return; }
        if (!upload.reportValidity()) { return; }
        var fd = new FormData(upload);
        fd.append('sef_pdf', checkedFile, checkedFile.name);
        if (fcfg.healthBy && field(fcfg.healthBy)) { fd.append('sef_kind', field(fcfg.healthBy).value); } // ο διακομιστής απορρίπτει είδος «για λόγους υγείας»
        fd.append('action', 'sef_send_document');
        fd.append('sef_nonce', upload.getAttribute('data-nonce') || '');
        var btn = upload.querySelector('button[type="submit"]');
        btn.disabled = true; status(st, 'Αποστολή…');
        fetch(cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin', cache: 'no-store' }).then(function (r) {
          return r.json().catch(function () { return { success: false, data: { message: r.status === 413 ? 'Το αρχείο είναι πολύ μεγάλο για τον διακομιστή. Στείλτε το με email.' : 'Σφάλμα διακομιστή (' + r.status + ').' } }; });
        }).then(function (j) {
          var msg = j && j.data && j.data.message ? j.data.message : 'Η αποστολή δεν ολοκληρώθηκε.';
          if (j && j.success) {
            status(st, '✓ ' + msg, 'ok'); setDone(sendN, true); dirty = false;
            upload.classList.add('is-sent');
          } else {
            status(st, msg, 'err');
            if (j && j.data && j.data.refresh) { loadChallenge(); }
            if (j && j.data && j.data.fallback && cfg.email) { st.textContent += ' (' + cfg.email + ')'; }
          }
        }).catch(function () { status(st, 'Δεν ήταν δυνατή η σύνδεση. Δοκιμάστε ξανά ή στείλτε με email.', 'err'); })
          .then(function () { btn.disabled = false; });
      });
    }

    /* --- Σύνδεση γεγονότων --- */
    if (sheet) {
      applyDefaults();
      setupDays();
      sheet.addEventListener('input', function (e) {
        dirty = true;
        var t = e.target;
        if (t.classList && t.classList.contains('is-bad')) { clearError(t, false); }
        var grp = t.closest && t.closest('.is-bad-group');
        if (grp) { clearError(grp, true); }
        if (e.target.tagName === 'TEXTAREA') { grow(e.target); }
        mirrors(); refreshFill(); refreshSubject();
        if (pdfMade) { pdfMade = false; setDone(2, false); status(pdfStatus, 'Αλλάξατε το έντυπο· κατεβάστε ξανά το PDF.', 'warn'); }
      });
      sheet.addEventListener('change', function (e) {
        // Έτοιμο θέμα: γεμίζει τα πεδία data-fill-from, εκτός αν ο χρήστης τα άλλαξε μετά την τελευταία συμπλήρωση.
        if (e.target.tagName === 'SELECT' && e.target.name) {
          Array.prototype.forEach.call(sheet.querySelectorAll('[data-fill-from="' + e.target.name + '"]'), function (t) {
            var v = optAttr(e.target.name, t.getAttribute('data-fill-attr'));
            var auto = t.getAttribute('data-autofill');
            if (t.value.trim() && t.value !== auto) { return; } // γραμμένο από τον χρήστη: δεν το πειράζουμε
            t.value = v; t.setAttribute('data-autofill', v);
            if (v && t.classList.contains('is-bad')) { clearError(t, false); }
            if (t.tagName === 'TEXTAREA') { grow(t); }
          });
        }
        var grp = e.target.closest && e.target.closest('.is-bad-group');
        if (grp) { clearError(grp, true); }
        mirrors(); refreshFill(); refreshSubject();
      });
      sheet.addEventListener('focusout', function (e) {
        var t = e.target;
        if (!t || (t.type !== 'tel' && t.type !== 'email')) { return; }
        var m = formatError(t);
        if (m) { setError(t, m, false); announce(labelFor(t) + ': ' + m); } else if (t.classList.contains('is-bad') && String(t.value || '').trim()) { clearError(t, false); }
      });
      view.querySelector('[data-sef-clear]').addEventListener('click', function () {
        if (!window.confirm('Να σβηστούν όλα τα στοιχεία του εντύπου;')) { return; }
        sheet.reset(); touched = {};
        clearAllErrors();
        applyDefaults(); refreshFill(); refreshSubject(); setDone(2, false); pdfMade = false; dirty = false;
        missingBox.hidden = true; status(pdfStatus, '');
      });
      view.querySelector('[data-sef-pdf]').addEventListener('click', function () { guard(doPdf); });
      var blankBtn = view.querySelector('[data-sef-blank]');
      if (blankBtn) {
        blankBtn.addEventListener('click', function () {
          var st = view.querySelector('[data-sef-blank-status]');
          // Έντυπα με εναλλακτικά κείμενα (π.χ. δήλωση αποχώρησης): πρώτα επιλογή είδους
          var need = sheet.querySelector('[data-blank-need]');
          if (need && !need.value) { status(st, 'Επιλέξτε πρώτα «' + labelFor(need) + '» πάνω στο έντυπο· το κενό έντυπο βγαίνει για αυτό το είδος.', 'err'); need.focus(); return; }
          blankBtn.disabled = true; status(st, 'Δημιουργία κενού εντύπου…');
          var title = 'Κενό έντυπο – ' + fcfg.title;
          makePdf(cfg, exportHTML(sheet, true), title).then(function (blob) {
            download(blob, ('Κενό_' + fcfg.title).replace(/[\\\/:*?"<>|]+/g, '').replace(/\s+/g, '_') + '.pdf');
            var keptSel = sheet.querySelector('select[data-blank-keep]');
            var which = keptSel && keptSel.value ? ' (για «' + keptSel.options[keptSel.selectedIndex].text + '»)' : '';
            status(st, '✓ Το κενό έντυπο' + which + ' αποθηκεύτηκε. Εκτυπώστε το, συμπληρώστε το με το χέρι και καταθέστε το στο σχολείο. Πιο εύκολα: συμπληρώστε το έντυπο εδώ και πατήστε «Λήψη PDF» ή «Εκτύπωση» στο βήμα 2 — βγαίνει έτοιμο για υπογραφή και κατάθεση ιδιοχείρως.', 'ok');
          }).catch(function (err) {
            status(st, 'Δεν ήταν δυνατή η δημιουργία (' + (err && err.message ? err.message : 'σφάλμα') + ').', 'err');
          }).then(function () { blankBtn.disabled = false; });
        });
      }
      view.querySelector('[data-sef-print]').addEventListener('click', function () {
        var list = missing();
        if (list.length && !window.confirm('Λείπουν ' + list.length + ' στοιχεία. Να ανοίξει η εκτύπωση ούτως ή άλλως;')) { showMissing(list, doPrint); return; }
        doPrint();
      });
    }
    Array.prototype.forEach.call(view.querySelectorAll('[data-sef-gov]'), function (a) {
      a.addEventListener('click', function () { if (fcfg.kind === 'external') { setDone(1, true); } });
    });
    Array.prototype.forEach.call(view.querySelectorAll('[data-sef-guide]'), function (b) { b.addEventListener('click', function () { app.guide().open(); }); });
    Array.prototype.forEach.call(view.querySelectorAll('[data-sef-copy]'), function (b) {
      b.addEventListener('click', function () {
        var what = b.getAttribute('data-sef-copy');
        copyText(what === 'addr' ? (fcfg.email || cfg.email) : fillTemplate(fcfg.subject, 'text'), b);
        if (sendN) { setDone(sendN, true); }
      });
    });
    Array.prototype.forEach.call(view.querySelectorAll('[data-sef-compose]'), function (a) {
      a.addEventListener('click', function () { if (sendN) { setDone(sendN, true); } });
    });
    Array.prototype.forEach.call(view.querySelectorAll('[data-sef-done-check]'), function (c) {
      c.addEventListener('change', function () { setDone(Number(c.getAttribute('data-sef-done-check')), c.checked); });
    });
    Array.prototype.forEach.call(view.querySelectorAll('.sef-stepper a'), function (a) {
      a.addEventListener('click', function (e) { e.preventDefault(); var t = document.getElementById(a.getAttribute('href').slice(1)); scrollTo(t, 'start'); flash(t); });
    });
    setupChecker(); setupUpload();
    // Αποστολή από την ιστοσελίδα, εκτός αν επιλεγεί είδος «για λόγους υγείας» (π.χ. αναρρωτική) → μόνο email.
    var hbSel = fcfg.healthBy ? field(fcfg.healthBy) : null, upBox = view.querySelector('[data-sef-send-up]'), mailBox = view.querySelector('[data-sef-send-mail]');
    function healthSend() {
      if (!hbSel || !upBox || !mailBox) { return; }
      var op = hbSel.options[hbSel.selectedIndex], h = !!(op && op.getAttribute('data-health') === '1');
      upBox.hidden = h; mailBox.hidden = !h;
    }
    if (hbSel) { hbSel.addEventListener('change', healthSend); healthSend(); }
    if (sheet) { refreshFill(); }
    refreshSubject();
    setDone(0, true);

    // Προεπιλογές από συντόμευση/σύνδεσμο (#sef/teacher_leave?leave_type=sick)
    function preset(p) {
      if (!sheet || !p) { return; }
      Object.keys(p).forEach(function (k) {
        var el = field(k);
        if (!el || el.tagName !== 'SELECT') { return; }
        var ok = Array.prototype.some.call(el.options, function (o) { return o.value === p[k]; });
        if (ok && el.value !== p[k]) { el.value = p[k]; el.dispatchEvent(new Event('change', { bubbles: true })); }
      });
    }

    return {
      preset: preset,
      shown: function () { if (upload && !challengeLoaded) { loadChallenge(); } },
      dirty: function () { return dirty && !pdfMade; }
    };
  }

  /* ---------------- Εφαρμογή (ένα shortcode) ---------------- */
  function App(root) {
    var cfgEl = root.querySelector('.sef-config');
    var cfg = {};
    try { cfg = JSON.parse(cfgEl ? cfgEl.textContent : '{}'); } catch (e) { cfg = {}; }
    var views = {}, forms = {}, guide = null;
    Array.prototype.forEach.call(root.querySelectorAll('[data-sef-view]'), function (v) { views[v.getAttribute('data-sef-view')] = v; });
    var api = {
      guide: function () { if (!guide) { guide = Guide(root.querySelector('[data-sef-guide-dialog]'), cfg.guideBase); } return guide; }
    };
    function formFor(id) {
      if (!forms[id] && cfg.forms && cfg.forms[id]) { forms[id] = FormView(api, views[id], cfg, cfg.forms[id], id); }
      return forms[id];
    }
    function parseQuery(q) {
      var o = {};
      String(q || '').split('&').forEach(function (kv) { var i = kv.indexOf('='); if (i > 0) { try { o[decodeURIComponent(kv.slice(0, i))] = decodeURIComponent(kv.slice(i + 1)); } catch (e) { /* αγνοείται */ } } });
      return o;
    }
    function show(id, focus, pre) {
      Object.keys(views).forEach(function (k) { views[k].hidden = k !== id; });
      if (id !== 'home') {
        var f = formFor(id); if (f) { f.shown(); if (pre && f.preset) { f.preset(pre); } }
        if (focus) {
          scrollTo(root, 'start');
          var h = views[id].querySelector('.sef-h2'); if (h) { h.focus({ preventScroll: true }); }
        }
      } else if (focus) {
        scrollTo(root, 'start');
      }
    }
    function route(focus) {
      var m = /^#sef\/([a-z0-9_\-]+)(?:\?(.*))?/i.exec(location.hash);
      var id = m ? m[1].toLowerCase() : '';
      var pre = m && m[2] ? parseQuery(m[2]) : null;
      if (id && views[id] && id !== 'home') { show(id, focus, pre); return; }
      if (views.home) { show('home', focus && /^#sef/.test(location.hash)); }
    }
    root.addEventListener('click', function (e) {
      var open = e.target.closest('[data-sef-open]');
      var home = e.target.closest('[data-sef-home]');
      var gd = e.target.closest('[data-sef-guide]');
      if (gd && root.contains(gd) && gd.closest('[data-sef-view]') === views.home) {
        api.guide().open(); // ο οδηγός gov.gr από την κεντρική σελίδα
        return;
      }
      if (open && root.contains(open)) {
        e.preventDefault();
        history.pushState({ sef: 1 }, '', '#sef/' + open.getAttribute('data-sef-open'));
        route(true);
      } else if (home && root.contains(home)) {
        e.preventDefault();
        // Τα συμπληρωμένα στοιχεία διατηρούνται όσο η σελίδα είναι ανοιχτή.
        history.pushState({ sef: 1 }, '', location.pathname + location.search + '#sef');
        route(true);
      }
    });
    window.addEventListener('popstate', function () { route(true); });
    window.addEventListener('hashchange', function () { route(true); });
    window.addEventListener('beforeunload', function (e) {
      var anyDirty = Object.keys(forms).some(function (k) { return forms[k].dirty(); });
      if (anyDirty) { e.preventDefault(); e.returnValue = ''; }
    });

    // Φίλτρο κατηγοριών + αναζήτηση (χωρίς τόνους/πεζά-κεφαλαία)
    var chips = Array.prototype.slice.call(root.querySelectorAll('[data-sef-filter]'));
    var searchBox = root.querySelector('[data-sef-search]');
    var noRes = root.querySelector('[data-sef-noresults]');
    // Κεντρική σελίδα με ενότητες: με λέξη αναζήτησης, οι κάρτες ενοτήτων δίνουν τη θέση τους
    // στα αποτελέσματα από όλα τα έντυπα· χωρίς λέξη, επιστρέφουν.
    var hub = root.querySelector('[data-sef-hub]');
    var results = root.querySelector('[data-sef-results]');
    var activeCat = '';
    function norm(t) { return String(t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ς/g, 'σ'); }
    function applyFilter() {
      var q = searchBox ? norm(searchBox.value).trim() : '';
      var words = q ? q.split(/\s+/) : [];
      var total = 0;
      if (hub && results) { hub.hidden = !!q; results.hidden = !q; }
      Array.prototype.forEach.call((results || root).querySelectorAll('[data-sef-cat]'), function (sec) {
        var catOk = !activeCat || sec.getAttribute('data-sef-cat') === activeCat;
        var visible = 0;
        Array.prototype.forEach.call(sec.querySelectorAll('[data-sef-card]'), function (card) {
          var hay = norm(card.textContent);
          var ok = catOk && words.every(function (w) { return hay.indexOf(w) !== -1; });
          card.hidden = !ok;
          if (ok) { visible++; }
        });
        sec.hidden = !visible;
        total += visible;
      });
      if (noRes) { noRes.hidden = total > 0 || (hub && !q); }
    }
    chips.forEach(function (c) {
      c.addEventListener('click', function () {
        activeCat = c.getAttribute('data-sef-filter');
        chips.forEach(function (x) { x.setAttribute('aria-pressed', String(x === c)); });
        applyFilter();
      });
    });
    if (searchBox) { searchBox.addEventListener('input', applyFilter); }

    if (cfg.single) {
      var only = Object.keys(views)[0];
      if (only) { show(only, false, null); }
    } else {
      route(false);
    }
  }

  /* --- Αντίθεση χρωμάτων (WCAG 1.4.3, 4,5:1) για χρώμα που έρχεται από το θέμα --- */
  var cnv = null;
  function rgbOf(c) {
    if (!cnv) { cnv = document.createElement('canvas').getContext('2d'); }
    if (!cnv) { return null; }
    cnv.fillStyle = '#000'; cnv.fillStyle = String(c || '').trim();
    var v = cnv.fillStyle, m;
    if ((m = /^#([0-9a-f]{6})$/i.exec(v))) { var n = parseInt(m[1], 16); return [n >> 16 & 255, n >> 8 & 255, n & 255]; }
    if ((m = /rgba?\(([\d.]+),\s*([\d.]+),\s*([\d.]+)/.exec(v))) { return [+m[1], +m[2], +m[3]]; }
    return null;
  }
  function lum(rgb) { return rgb.map(function (x) { x /= 255; return x <= 0.03928 ? x / 12.92 : Math.pow((x + 0.055) / 1.055, 2.4); }).reduce(function (a, x, i) { return a + x * [0.2126, 0.7152, 0.0722][i]; }, 0); }
  function contrast(a, b) { var l1 = lum(a), l2 = lum(b); return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05); }
  function hex(rgb) { return '#' + rgb.map(function (x) { return ('0' + Math.round(x).toString(16)).slice(-2); }).join(''); }
  function fixContrast(root) {
    var cs = getComputedStyle(root);
    var acc = rgbOf(cs.getPropertyValue('--sef-accent')), surf = rgbOf(cs.getPropertyValue('--sef-surface')) || [255, 255, 255];
    if (!acc) { return; }
    // Γράμματα πάνω στο χρώμα: λευκά ή σκούρα, ό,τι δίνει μεγαλύτερη αντίθεση.
    var dark = [17, 24, 39];
    root.style.setProperty('--sef-accent-ink', contrast(acc, [255, 255, 255]) >= contrast(acc, dark) ? '#fff' : hex(dark));
    // Κείμενο/σύνδεσμοι στο χρώμα πάνω στην επιφάνεια: σκουραίνει (ή ανοίγει σε σκούρο θέμα) ως το 4,5:1.
    var toward = lum(surf) > 0.5 ? [0, 0, 0] : [255, 255, 255], t = acc.slice(), k = 0;
    while (contrast(t, surf) < 4.5 && k < 20) { k++; t = acc.map(function (x, i) { return x + (toward[i] - x) * k * 0.05; }); }
    root.style.setProperty('--sef-accent-text', hex(t));
    root.setAttribute('data-sef-contrast', contrast(acc, [255, 255, 255]).toFixed(2));
  }

  function boot() { Array.prototype.forEach.call(document.querySelectorAll('[data-sef]'), function (r) { try { fixContrast(r); } catch (e) { /* χωρίς διόρθωση */ } App(r); }); }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
