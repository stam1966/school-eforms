/*!
 * School e-Forms — PDF writer
 * Μετατρέπει ένα «φύλλο Α4» της σελίδας σε διανυσματικό PDF με πραγματικό,
 * επιλέξιμο ελληνικό κείμενο και ενσωματωμένη γραμματοσειρά.
 * Χωρίς εξωτερικές βιβλιοθήκες: η διάταξη υπολογίζεται από τον ίδιο τον
 * browser και ο writer αποτυπώνει κείμενο, γραμμές, φόντα, εικόνες και τικ.
 * Δομή βασισμένη στο PDF/A-1b (XMP, OutputIntent sRGB, ενσωματωμένες
 * γραμματοσειρές, χωρίς διαφάνειες).
 */
(function (global) {
  'use strict';

  var PX2PT = 0.75;                // 72pt / 96px
  var PAGE_H_PX = 1122.52;         // 297mm @ 96dpi
  var PW = 595.28, PH = 841.89;    // A4 σε points
  var ICC_B64 = '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';
  var enc = new TextEncoder();
  var fontBytes = {};

  function loadFont(key, url) {
    if (!fontBytes[key]) {
      fontBytes[key] = fetch(url, { credentials: 'same-origin' }).then(function (r) {
        if (!r.ok) { throw new Error('Font HTTP ' + r.status); }
        return r.arrayBuffer();
      }).then(function (b) { return new Uint8Array(b); });
      fontBytes[key].catch(function () { delete fontBytes[key]; }); // νέα προσπάθεια την επόμενη φορά
    }
    return fontBytes[key];
  }

  function deflate(bytes) {
    if (typeof CompressionStream === 'undefined') { return Promise.resolve(null); }
    try {
      var stream = new Blob([bytes]).stream().pipeThrough(new CompressionStream('deflate'));
      return new Response(stream).arrayBuffer().then(function (b) { return new Uint8Array(b); }, function () { return null; });
    } catch (e) { return Promise.resolve(null); }
  }

  function parseColor(c) {
    var m = /rgba?\(([^)]+)\)/.exec(c || '');
    if (!m) { return null; }
    var p = m[1].split(/[\s,\/]+/).filter(Boolean).map(Number);
    var a = p.length > 3 ? p[3] : 1;
    if (a < 0.05) { return null; }
    // Ημιδιαφανή χρώματα αναμειγνύονται με λευκό (το PDF/A-1 δεν επιτρέπει διαφάνεια).
    return [0, 1, 2].map(function (i) { return (p[i] * a + 255 * (1 - a)) / 255; });
  }
  function isWhite(c) { return c && c[0] > 0.995 && c[1] > 0.995 && c[2] > 0.995; }
  function n(v) { return (Math.round(v * 100) / 100).toString(); }
  function col(c) { return n(c[0]) + ' ' + n(c[1]) + ' ' + n(c[2]); }
  function hex4(v) { return ('0000' + v.toString(16)).slice(-4).toUpperCase(); }
  function utf16hex(str) {
    var h = '';
    for (var i = 0; i < str.length; i++) { h += hex4(str.charCodeAt(i)); }
    return h;
  }

  function hasUnderline(el, root, win) {
    for (var e = el; e && e !== root.parentNode; e = e.parentElement) {
      var d = win.getComputedStyle(e).textDecorationLine || win.getComputedStyle(e).textDecoration || '';
      if (d.indexOf('underline') !== -1) { return true; }
      if (e === root) { break; }
    }
    return false;
  }

  /* ---------- 1. Συλλογή στοιχείων από τη διάταξη του browser ---------- */
  function collect(root, meta) {
    var doc = root.ownerDocument, win = doc.defaultView;
    var o = root.getBoundingClientRect();
    var items = [];
    var all = [root].concat(Array.prototype.slice.call(root.querySelectorAll('*')));

    all.forEach(function (el) {
      var cs = win.getComputedStyle(el);
      if (cs.display === 'none' || cs.visibility === 'hidden') { return; }
      var r = el.getBoundingClientRect();
      if (!r.width && !r.height) { return; }
      var x = r.left - o.left, y = r.top - o.top, w = r.width, h = r.height;
      if (el !== root) {
        var bg = parseColor(cs.backgroundColor);
        if (bg && !isWhite(bg)) { items.push({ t: 'rect', x: x, y: y, w: w, h: h, c: bg }); }
      }
      var sides = [
        ['Top', x, y, w, null], ['Right', null, y, null, h],
        ['Bottom', x, null, w, null], ['Left', x, y, null, h]
      ];
      sides.forEach(function (s) {
        var bw = parseFloat(cs['border' + s[0] + 'Width']) || 0;
        var bs = cs['border' + s[0] + 'Style'];
        if (bw <= 0 || bs === 'none' || bs === 'hidden') { return; }
        var c = parseColor(cs['border' + s[0] + 'Color']);
        if (!c) { return; }
        var rx = s[0] === 'Right' ? x + w - bw : x;
        var ry = s[0] === 'Bottom' ? y + h - bw : y;
        var rw = (s[0] === 'Top' || s[0] === 'Bottom') ? w : bw;
        var rh = (s[0] === 'Top' || s[0] === 'Bottom') ? bw : h;
        items.push({ t: (bs === 'dotted' || bs === 'dashed') ? 'dash' : 'rect', x: rx, y: ry, w: rw, h: rh, c: c, dot: bs === 'dotted' });
      });
      if (el.tagName === 'IMG' && el.complete && el.naturalWidth) {
        items.push({ t: 'img', x: x, y: y, w: w, h: h, el: el });
      }
      if (el.getAttribute('data-pdf-check') === '1') {
        items.push({ t: 'check', x: x, y: y, w: w, h: h, c: parseColor(cs.color) || [0, 0, 0] });
      }
    });

    var walker = doc.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    var range = doc.createRange();
    var node;
    while ((node = walker.nextNode())) {
      var s = node.nodeValue;
      if (!s || !/\S/.test(s)) { continue; }
      var p = node.parentElement;
      var cs = win.getComputedStyle(p);
      if (cs.visibility === 'hidden') { continue; }
      var c = parseColor(cs.color);
      if (!c) { continue; }
      var size = parseFloat(cs.fontSize);
      var font = (parseInt(cs.fontWeight, 10) || 400) >= 600 ? 'bold' : 'regular';
      var fm = meta[font];
      var ls = parseFloat(cs.letterSpacing) || 0;
      var ul = hasUnderline(p, root, win);
      var upper = cs.textTransform === 'uppercase';
      var run = null;
      var flush = function () {
        if (!run) { return; }
        var text = upper ? run.text.toLocaleUpperCase('el') : run.text;
        var base = run.top + run.h * fm.asc / (fm.asc + fm.desc);
        items.push({ t: 'text', x: run.x, top: run.top, base: base, size: size, font: font, c: c, text: text, ls: ls, ul: ul, right: run.nextX });
        run = null;
      };
      for (var i = 0; i < s.length; i++) {
        var ch = s.charAt(i);
        var code = s.charCodeAt(i);
        if (/\s/.test(ch)) { flush(); continue; }
        var pair = code >= 0xD800 && code <= 0xDBFF;
        range.setStart(node, i);
        range.setEnd(node, pair ? i + 2 : i + 1);
        var rr = range.getBoundingClientRect();
        if (pair) { i++; ch = s.substr(i - 1, 2); }
        if (!rr.width && !rr.height) { flush(); continue; }
        var cx = rr.left - o.left, top = rr.top - o.top;
        if (run && Math.abs(top - run.top) < 0.6 && Math.abs(cx - run.nextX) < 0.75) {
          run.text += ch;
        } else {
          flush();
          run = { x: cx, top: top, h: rr.height, text: ch };
        }
        run.nextX = rr.right - o.left;
      }
      flush();
    }
    var pages = Math.max(1, Math.ceil((root.scrollHeight - 4) / PAGE_H_PX));
    return { items: items, pages: pages };
  }

  /* ---------- 2. Εικόνες → JPEG ---------- */
  function imageToJpeg(img) {
    return new Promise(function (resolve) {
      try {
        var w = img.naturalWidth, h = img.naturalHeight, k = Math.min(1, 2400 / Math.max(w, h));
        var cv = document.createElement('canvas');
        cv.width = Math.round(w * k); cv.height = Math.round(h * k);
        var cx = cv.getContext('2d');
        cx.fillStyle = '#fff'; cx.fillRect(0, 0, cv.width, cv.height);
        cx.drawImage(img, 0, 0, cv.width, cv.height);
        cv.toBlob(function (b) {
          if (!b) { resolve(null); return; }
          b.arrayBuffer().then(function (buf) { resolve({ bytes: new Uint8Array(buf), w: cv.width, h: cv.height }); });
        }, 'image/jpeg', 0.92);
      } catch (e) { resolve(null); } // π.χ. εικόνα από άλλο domain
    });
  }

  /* ---------- 3. Συγγραφή PDF ---------- */
  function b64ToBytes(b) {
    var s = atob(b), u = new Uint8Array(s.length);
    for (var i = 0; i < s.length; i++) { u[i] = s.charCodeAt(i); }
    return u;
  }
  function xmlEsc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
  function randHex(len) {
    var a = new Uint8Array(len); crypto.getRandomValues(a);
    return Array.prototype.map.call(a, function (x) { return ('0' + x.toString(16)).slice(-2); }).join('').toUpperCase();
  }

  function render(root, opts) {
    var meta = global.SEF_FONTMETA;
    if (!meta) { return Promise.reject(new Error('Font metadata missing')); }
    var title = opts.title || 'Έντυπο';
    var data = collect(root, meta);
    var imgs = data.items.filter(function (it) { return it.t === 'img'; });

    return Promise.all([
      loadFont('regular', opts.fonts.regular),
      loadFont('bold', opts.fonts.bold),
      Promise.all(imgs.map(function (it) { return imageToJpeg(it.el); }))
    ]).then(function (res) {
      var fonts = { regular: res[0], bold: res[1] };
      imgs.forEach(function (it, i) { it.jpeg = res[2][i]; });
      return build(data, fonts, meta, title, opts);
    });
  }

  function build(data, fontFiles, meta, title, opts) {
    var used = { regular: {}, bold: {} };     // gid -> unicode
    var pageOps = [];
    for (var p = 0; p < data.pages; p++) { pageOps.push([]); }
    var imgRefs = [];

    function pagesFor(y, h) {
      var a = Math.max(0, Math.floor(y / PAGE_H_PX)), b = Math.min(data.pages - 1, Math.floor((y + Math.max(h, 0.01) - 0.01) / PAGE_H_PX));
      var out = []; for (var i = a; i <= b; i++) { out.push(i); } return out;
    }
    function Y(ypx, page) { return PH - (ypx - page * PAGE_H_PX) * PX2PT; }

    data.items.forEach(function (it) {
      if (it.t === 'rect') {
        pagesFor(it.y, it.h).forEach(function (pg) {
          pageOps[pg].push(col(it.c) + ' rg ' + n(it.x * PX2PT) + ' ' + n(Y(it.y + it.h, pg)) + ' ' + n(it.w * PX2PT) + ' ' + n(it.h * PX2PT) + ' re f');
        });
      } else if (it.t === 'dash') {
        pagesFor(it.y, it.h).forEach(function (pg) {
          var horiz = it.w >= it.h, lw = (horiz ? it.h : it.w) * PX2PT;
          var x1 = it.x, y1 = it.y, x2 = it.x, y2 = it.y;
          if (horiz) { y1 = y2 = it.y + it.h / 2; x2 = it.x + it.w; } else { x1 = x2 = it.x + it.w / 2; y2 = it.y + it.h; }
          pageOps[pg].push('q ' + col(it.c) + ' RG ' + n(lw) + ' w ' + (it.dot ? '[' + n(lw) + ' ' + n(lw * 2) + '] 0 d' : '[' + n(lw * 3) + ' ' + n(lw * 2) + '] 0 d') +
            ' ' + n(x1 * PX2PT) + ' ' + n(Y(y1, pg)) + ' m ' + n(x2 * PX2PT) + ' ' + n(Y(y2, pg)) + ' l S Q');
        });
      } else if (it.t === 'check') {
        var pg = pagesFor(it.y, it.h)[0];
        var lw = Math.max(0.9, it.w * 0.11 * PX2PT);
        var pt = function (fx, fy) { return n((it.x + it.w * fx) * PX2PT) + ' ' + n(Y(it.y + it.h * fy, pg)); };
        pageOps[pg].push('q ' + col(it.c) + ' RG ' + n(lw) + ' w 1 J 1 j ' + pt(0.2, 0.55) + ' m ' + pt(0.42, 0.78) + ' l ' + pt(0.82, 0.2) + ' l S Q');
      } else if (it.t === 'img' && it.jpeg) {
        var name = 'Im' + imgRefs.length;
        imgRefs.push(it.jpeg);
        pagesFor(it.y, it.h).forEach(function (pg) {
          pageOps[pg].push('q ' + n(it.w * PX2PT) + ' 0 0 ' + n(it.h * PX2PT) + ' ' + n(it.x * PX2PT) + ' ' + n(Y(it.y + it.h, pg)) + ' cm /' + name + ' Do Q');
        });
      } else if (it.t === 'text') {
        var fm = meta[it.font], u = used[it.font];
        var hex = '', adv = 0, chars = Array.from(it.text);
        chars.forEach(function (ch) {
          var gid = fm.cmap[ch.codePointAt(0)];
          if (gid === undefined) { gid = 0; }
          if (gid && !u[gid]) { u[gid] = ch; }
          hex += hex4(gid);
          adv += fm.w[gid] * it.size / fm.upem + it.ls;
        });
        var page = Math.min(data.pages - 1, Math.max(0, Math.floor((it.top + 1) / PAGE_H_PX)));
        var F = it.font === 'bold' ? 'F2' : 'F1';
        pageOps[page].push('BT /' + F + ' ' + n(it.size * PX2PT) + ' Tf ' + col(it.c) + ' rg ' + (it.ls ? n(it.ls * PX2PT) + ' Tc ' : '') +
          '1 0 0 1 ' + n(it.x * PX2PT) + ' ' + n(Y(it.base, page)) + ' Tm <' + hex + '> Tj ET');
        if (it.ul) {
          var th = Math.max(0.5, it.size * 0.065);
          pageOps[page].push(col(it.c) + ' rg ' + n(it.x * PX2PT) + ' ' + n(Y(it.base + it.size * 0.12 + th, page)) + ' ' + n(adv * PX2PT) + ' ' + n(th * PX2PT) + ' re f');
        }
      }
    });

    var fontKeys = ['regular', 'bold'];
    return Promise.all(fontKeys.map(function (k) { return deflate(fontFiles[k]); })).then(function (comp) {
      return Promise.all(pageOps.map(function (ops) { return deflate(enc.encode(ops.join('\n'))); })).then(function (pcomp) {
        return assemble(pageOps, pcomp, fontFiles, comp, meta, used, imgRefs, title, opts);
      });
    });
  }

  function assemble(pageOps, pcomp, fontFiles, fcomp, meta, used, imgRefs, title, opts) {
    var chunks = [], len = 0, offsets = [];
    function push(d) { var b = typeof d === 'string' ? enc.encode(d) : d; chunks.push(b); len += b.length; }
    var next = 1;
    function alloc() { return next++; }
    function obj(num, body) { offsets[num] = len; push(num + ' 0 obj\n'); body(); push('\nendobj\n'); }
    function stream(dict, bytes) { push('<< ' + dict + ' /Length ' + bytes.length + ' >>\nstream\n'); push(bytes); push('\nendstream'); }

    var now = new Date(), p2 = function (v) { return ('0' + v).slice(-2); };
    var off = -now.getTimezoneOffset(), sg = off >= 0 ? '+' : '-', oh = p2(Math.floor(Math.abs(off) / 60)), om = p2(Math.abs(off) % 60);
    var Yr = now.getFullYear(), Mo = p2(now.getMonth() + 1), Da = p2(now.getDate()), Hh = p2(now.getHours()), Mi = p2(now.getMinutes()), Se = p2(now.getSeconds());
    var pdfDate = 'D:' + Yr + Mo + Da + Hh + Mi + Se + sg + oh + "'" + om + "'";
    var xmpDate = Yr + '-' + Mo + '-' + Da + 'T' + Hh + ':' + Mi + ':' + Se + sg + oh + ':' + om;
    var producer = 'School e-Forms PDF writer';
    var creator = (opts.creator || 'School e-Forms').replace(/[()\\]/g, '');

    var catalog = alloc(), pagesId = alloc(), xmpId = alloc(), oiId = alloc(), iccId = alloc(), infoId = alloc();
    var fontIds = {};
    ['regular', 'bold'].forEach(function (k) {
      fontIds[k] = { type0: alloc(), cid: alloc(), desc: alloc(), file: alloc(), tu: alloc() };
    });
    var imgIds = imgRefs.map(function () { return alloc(); });
    var pageIds = pageOps.map(function () { return { page: alloc(), content: alloc() }; });

    push('%PDF-1.4\n'); push(new Uint8Array([0x25, 0xE2, 0xE3, 0xCF, 0xD3, 0x0A]));

    obj(catalog, function () { push('<< /Type /Catalog /Pages ' + pagesId + ' 0 R /Metadata ' + xmpId + ' 0 R /OutputIntents [' + oiId + ' 0 R] /Lang (el-GR) >>'); });
    obj(pagesId, function () { push('<< /Type /Pages /Kids [' + pageIds.map(function (p) { return p.page + ' 0 R'; }).join(' ') + '] /Count ' + pageIds.length + ' >>'); });

    var xmp = '<?xpacket begin="﻿" id="W5M0MpCehiHzreSzNTczkc9d"?>\n<x:xmpmeta xmlns:x="adobe:ns:meta/">\n<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">\n' +
      '<rdf:Description rdf:about="" xmlns:pdfaid="http://www.aiim.org/pdfa/ns/id/"><pdfaid:part>1</pdfaid:part><pdfaid:conformance>B</pdfaid:conformance></rdf:Description>\n' +
      '<rdf:Description rdf:about="" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:format>application/pdf</dc:format><dc:title><rdf:Alt><rdf:li xml:lang="x-default">' + xmlEsc(title) + '</rdf:li></rdf:Alt></dc:title></rdf:Description>\n' +
      '<rdf:Description rdf:about="" xmlns:xmp="http://ns.adobe.com/xap/1.0/"><xmp:CreateDate>' + xmpDate + '</xmp:CreateDate><xmp:ModifyDate>' + xmpDate + '</xmp:ModifyDate><xmp:MetadataDate>' + xmpDate + '</xmp:MetadataDate><xmp:CreatorTool>' + xmlEsc(creator) + '</xmp:CreatorTool></rdf:Description>\n' +
      '<rdf:Description rdf:about="" xmlns:pdf="http://ns.adobe.com/pdf/1.3/"><pdf:Producer>' + producer + '</pdf:Producer></rdf:Description>\n' +
      '</rdf:RDF>\n</x:xmpmeta>\n<?xpacket end="w"?>';
    obj(xmpId, function () { stream('/Type /Metadata /Subtype /XML', enc.encode(xmp)); });
    obj(oiId, function () { push('<< /Type /OutputIntent /S /GTS_PDFA1 /OutputConditionIdentifier (sRGB IEC61966-2.1) /Info (sRGB IEC61966-2.1) /RegistryName (http://www.color.org) /DestOutputProfile ' + iccId + ' 0 R >>'); });
    obj(iccId, function () { stream('/N 3', b64ToBytes(ICC_B64)); });
    obj(infoId, function () {
      push('<< /Title <FEFF' + utf16hex(title) + '> /Producer (' + producer + ') /Creator <FEFF' + utf16hex(creator) + '> /CreationDate (' + pdfDate + ') /ModDate (' + pdfDate + ') >>');
    });

    ['regular', 'bold'].forEach(function (k, idx) {
      var fm = meta[k], ids = fontIds[k], scale = 1000 / fm.upem;
      var gids = Object.keys(used[k]).map(Number).sort(function (a, b) { return a - b; });
      obj(ids.type0, function () {
        push('<< /Type /Font /Subtype /Type0 /BaseFont /' + fm.name + ' /Encoding /Identity-H /DescendantFonts [' + ids.cid + ' 0 R] /ToUnicode ' + ids.tu + ' 0 R >>');
      });
      obj(ids.cid, function () {
        var W = gids.map(function (g) { return g + ' [' + Math.round(fm.w[g] * scale) + ']'; }).join(' ');
        push('<< /Type /Font /Subtype /CIDFontType2 /BaseFont /' + fm.name + ' /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /FontDescriptor ' + ids.desc + ' 0 R /DW ' + Math.round(fm.w[0] * scale) + ' /W [' + W + '] /CIDToGIDMap /Identity >>');
      });
      obj(ids.desc, function () {
        var b = fm.bbox.map(function (v) { return Math.round(v * scale); }).join(' ');
        push('<< /Type /FontDescriptor /FontName /' + fm.name + ' /Flags 32 /FontBBox [' + b + '] /ItalicAngle ' + fm.italic + ' /Ascent ' + Math.round(fm.asc * scale) + ' /Descent ' + Math.round(-fm.desc * scale) + ' /CapHeight ' + Math.round(fm.cap * scale) + ' /StemV ' + fm.stemV + ' /FontFile2 ' + ids.file + ' 0 R >>');
      });
      obj(ids.file, function () {
        var raw = fontFiles[k], c = fcomp[idx];
        if (c) { stream('/Length1 ' + raw.length + ' /Filter /FlateDecode', c); } else { stream('/Length1 ' + raw.length, raw); }
      });
      obj(ids.tu, function () {
        var lines = ['/CIDInit /ProcSet findresource begin', '12 dict begin', 'begincmap',
          '/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def',
          '/CMapName /Adobe-Identity-UCS def', '/CMapType 2 def',
          '1 begincodespacerange', '<0000> <FFFF>', 'endcodespacerange'];
        for (var i = 0; i < gids.length; i += 100) {
          var part = gids.slice(i, i + 100);
          lines.push(part.length + ' beginbfchar');
          part.forEach(function (g) { lines.push('<' + hex4(g) + '> <' + utf16hex(used[k][g]) + '>'); });
          lines.push('endbfchar');
        }
        lines.push('endcmap', 'CMapName currentdict /CMap defineresource pop', 'end', 'end');
        stream('', enc.encode(lines.join('\n')));
      });
    });

    imgRefs.forEach(function (im, i) {
      obj(imgIds[i], function () {
        stream('/Type /XObject /Subtype /Image /Width ' + im.w + ' /Height ' + im.h + ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode', im.bytes);
      });
    });

    var xobj = imgRefs.length ? ' /XObject << ' + imgIds.map(function (id, i) { return '/Im' + i + ' ' + id + ' 0 R'; }).join(' ') + ' >>' : '';
    pageIds.forEach(function (pid, i) {
      obj(pid.page, function () {
        push('<< /Type /Page /Parent ' + pagesId + ' 0 R /MediaBox [0 0 ' + PW + ' ' + PH + '] /Resources << /Font << /F1 ' + fontIds.regular.type0 + ' 0 R /F2 ' + fontIds.bold.type0 + ' 0 R >>' + xobj + ' /ProcSet [/PDF /Text /ImageC] >> /Contents ' + pid.content + ' 0 R >>');
      });
      obj(pid.content, function () {
        if (pcomp[i]) { stream('/Filter /FlateDecode', pcomp[i]); } else { stream('', enc.encode(pageOps[i].join('\n'))); }
      });
    });

    var xref = len, size = next;
    var xs = 'xref\n0 ' + size + '\n0000000000 65535 f \n';
    for (var i = 1; i < size; i++) { xs += ('0000000000' + offsets[i]).slice(-10) + ' 00000 n \n'; }
    push(xs);
    var id = randHex(16);
    push('trailer\n<< /Size ' + size + ' /Root ' + catalog + ' 0 R /Info ' + infoId + ' 0 R /ID [<' + id + '> <' + id + '>] >>\nstartxref\n' + xref + '\n%%EOF\n');
    return new Blob(chunks, { type: 'application/pdf' });
  }

  /* ---------- 4. Έλεγχος αρχείου (τοπικά, χωρίς αποστολή) ---------- */
  function inspect(file) {
    return file.arrayBuffer().then(function (buf) {
      var u = new Uint8Array(buf), s = '';
      for (var i = 0; i < u.length; i += 65536) { s += String.fromCharCode.apply(null, u.subarray(i, Math.min(u.length, i + 65536))); }
      var isPdf = s.slice(0, 5) === '%PDF-';
      var signed = isPdf && s.indexOf('/ByteRange') !== -1 && /\/Type\s*\/Sig\b|adbe\.pkcs7|ETSI\.CAdES/.test(s);
      var ours = s.indexOf('School e-Forms PDF writer') !== -1;
      return { pdf: isPdf, signed: signed, unsignedOriginal: ours && !signed, size: u.length };
    });
  }

  global.SEFPDF = { render: render, inspect: inspect, fontBytes: loadFont, PAGE_H_PX: PAGE_H_PX };
})(window);
