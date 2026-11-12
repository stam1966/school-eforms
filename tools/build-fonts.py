#!/usr/bin/env python3
"""Δημιουργεί τις γραμματοσειρές εγγράφων του School e-Forms.

Παίρνει τη Liberation Sans (SIL OFL 1.1), κρατά μόνο Λατινικά + Ελληνικά
+ βασική στίξη, αφαιρεί kerning/ligatures (ώστε η θέση κάθε γράμματος στον
browser να ταυτίζεται με το PDF) και γράφει:
  assets/fonts/sef-doc-regular.ttf, sef-doc-bold.ttf
  assets/js/sef-fontmeta.js   (cmap, πλάτη, μετρικά για τον PDF writer)
Χρήση: python3 tools/build-fonts.py <dir με LiberationSans-*.ttf>
"""
import json, sys, os
from fontTools.ttLib import TTFont
from fontTools import subset

SRC = sys.argv[1] if len(sys.argv) > 1 else '/usr/share/fonts/truetype/liberation'
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
RANGES = [(0x20, 0x7E), (0xA0, 0xFF), (0x152, 0x153), (0x2B9, 0x2BC), (0x370, 0x3FF),
          (0x1F00, 0x1FFF), (0x2010, 0x2027), (0x2030, 0x203A), (0x20AC, 0x20AC),
          (0x2116, 0x2116), (0x2122, 0x2122)]
unicodes = [c for a, b in RANGES for c in range(a, b + 1)]
meta = {}
for key, fname in (('regular', 'LiberationSans-Regular.ttf'), ('bold', 'LiberationSans-Bold.ttf')):
    font = TTFont(os.path.join(SRC, fname))
    opts = subset.Options()
    opts.layout_features = []          # χωρίς kerning / ligatures
    opts.drop_tables += ['kern', 'GPOS', 'GSUB', 'GDEF', 'DSIG']
    opts.name_IDs = ['*']              # κρατάμε copyright/άδεια
    opts.notdef_outline = True
    opts.hinting = True
    sub = subset.Subsetter(opts)
    sub.populate(unicodes=unicodes)
    sub.subset(font)
    # Η Liberation έχει Reserved Font Name: το τροποποιημένο (υποσύνολο)
    # αρχείο πρέπει να φέρει άλλο όνομα (SIL OFL 1.1, όρος 3).
    style = 'Bold' if key == 'bold' else 'Regular'
    for rec in font['name'].names:
        if rec.nameID in (1, 16):
            rec.string = 'SEF Doc Sans'
        elif rec.nameID in (2, 17):
            rec.string = style
        elif rec.nameID == 3:
            rec.string = 'SEF Doc Sans %s (subset of Liberation Sans 2)' % style
        elif rec.nameID == 4:
            rec.string = 'SEF Doc Sans %s' % style
        elif rec.nameID == 6:
            rec.string = 'SEFDocSans-%s' % style
    out = os.path.join(ROOT, 'assets', 'fonts', 'sef-doc-%s.ttf' % key)
    font.save(out)
    font = TTFont(out)
    order = font.getGlyphOrder()
    gid = {n: i for i, n in enumerate(order)}
    cmap = {str(cp): gid[n] for cp, n in font.getBestCmap().items()}
    widths = [font['hmtx'][n][0] for n in order]
    head, hhea, os2, post = font['head'], font['hhea'], font['OS/2'], font['post']
    meta[key] = {
        'name': 'SEFDocSans-' + style,
        'upem': head.unitsPerEm,
        'asc': hhea.ascent, 'desc': -hhea.descent,
        'cap': getattr(os2, 'sCapHeight', hhea.ascent),
        'bbox': [head.xMin, head.yMin, head.xMax, head.yMax],
        'italic': post.italicAngle,
        'stemV': 140 if key == 'bold' else 80,
        'cmap': cmap, 'w': widths,
    }
    print(key, os.path.getsize(out), 'bytes', len(order), 'glyphs')
js = '/* Δημιουργήθηκε από tools/build-fonts.py — μην το επεξεργάζεστε. */\nwindow.SEF_FONTMETA=' + json.dumps(meta, separators=(',', ':')) + ';\n'
open(os.path.join(ROOT, 'assets', 'js', 'sef-fontmeta.js'), 'w').write(js)
print('meta', len(js), 'bytes')
