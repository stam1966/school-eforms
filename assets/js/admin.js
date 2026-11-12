/* School e-Forms — ρυθμίσεις διαχειριστή */
(function ($) {
  'use strict';
  $(function () {
    var $tabs = $('[data-sef-tab]');
    function show(tab) {
      $tabs.removeClass('nav-tab-active').filter('[data-sef-tab="' + tab + '"]').addClass('nav-tab-active');
      $('[data-sef-panel]').each(function () {
        var p = $(this).attr('data-sef-panel');
        this.hidden = !(p === tab || (tab === 'send' && p === 'send-extra'));
      });
      $('[data-sef-submit]').prop('hidden', tab === 'help');
      // Μετά την αποθήκευση επιστρέφουμε στην ίδια καρτέλα.
      var $ref = $('input[name="_wp_http_referer"]');
      if ($ref.length) {
        var v = $ref.val().replace(/([?&])tab=[^&]*&?/, '$1').replace(/[?&]$/, '');
        $ref.val(v + (v.indexOf('?') === -1 ? '?' : '&') + 'tab=' + tab);
      }
      if (window.history && history.replaceState) {
        var u = new URL(window.location.href); u.searchParams.set('tab', tab);
        u.searchParams.delete('sef_mail'); u.searchParams.delete('sef_err'); u.searchParams.delete('settings-updated');
        history.replaceState(null, '', u.toString());
      }
    }
    $tabs.on('click', function (e) { e.preventDefault(); show($(this).attr('data-sef-tab')); });
    var initial = $tabs.filter('.nav-tab-active').attr('data-sef-tab') || 'school';
    show(initial);

    // Χρήσιμες υπηρεσίες: προσθήκη / διαγραφή / σειρά / βαθμίδα
    var $links = $('[data-sef-links] tbody');
    var schoolType = $('#sef-school_level').val();
    var tpl = $('[data-sef-link-template]').html() || '';
    var counter = Date.now();
    $('[data-sef-link-add]').on('click', function () {
      var $row = $(tpl.replace(/__i__/g, 'n' + (counter++)));
      $links.append($row);
      $row.find('input').first().trigger('focus');
    });
    $links.on('click', '[data-sef-link-del]', function () { $(this).closest('tr').remove(); });
    $links.on('click', '[data-sef-up]', function () { var $r = $(this).closest('tr'); $r.prev().before($r); });
    $links.on('click', '[data-sef-down]', function () { var $r = $(this).closest('tr'); $r.next().after($r); });
    function markLevels() {
      schoolType = $('#sef-school_level').val();
      $links.find('[data-sef-link-row]').each(function () {
        var lv = $(this).find('[data-sef-level]').val();
        var match = lv === 'all' || lv === schoolType || (lv === 'secondary' && schoolType !== 'primary');
        $(this).toggleClass('is-other-level', !match);
      });
    }
    $links.on('change', '[data-sef-level]', markLevels);
    $('#sef-school_level').on('change', markLevels);

    // Επιλογή αρχείων από τα Πολυμέσα (κενό έντυπο, έγγραφα για εκτύπωση)
    $(document).on('click', '[data-sef-media-pick]', function (e) {
      e.preventDefault();
      if (!window.wp || !wp.media) { return; }
      var $box = $(this).closest('[data-sef-media]');
      var multiple = $box.attr('data-multiple') === '1';
      var frame = wp.media({ title: multiple ? 'Αρχεία εντύπου (PDF / Word)' : 'Κενό έντυπο (PDF / Word)', button: { text: 'Χρήση' }, multiple: multiple ? 'add' : false });
      frame.on('select', function () {
        var sel = frame.state().get('selection').toJSON();
        $box.find('[data-sef-media-ids]').val(sel.map(function (a) { return a.id; }).join(','));
        $box.find('[data-sef-media-list]').empty().append(sel.map(function (a) { return $('<span class="sef-media-item">').text(a.filename || a.title); }));
        $box.find('[data-sef-media-clear]').prop('hidden', false);
        $box.find('[data-sef-media-pick]').text('Αλλαγή');
      });
      frame.open();
    });
    $(document).on('click', '[data-sef-media-clear]', function (e) {
      e.preventDefault();
      var $box = $(this).closest('[data-sef-media]');
      $box.find('[data-sef-media-ids]').val('');
      $box.find('[data-sef-media-list]').empty();
      $(this).prop('hidden', true);
    });

    // Έγγραφα για εκτύπωση & σύνδεσμοι
    var $docs = $('[data-sef-docs] tbody');
    var docTpl = $('[data-sef-doc-template]').html() || '';
    $('[data-sef-doc-add]').on('click', function () {
      var $row = $(docTpl.replace(/__i__/g, 'n' + (counter++)));
      $docs.append($row);
      $row.find('input[type="text"]').first().trigger('focus');
    });
    $docs.on('click', '[data-sef-doc-del]', function () { $(this).closest('tr').remove(); });
    $docs.on('change', '[data-sef-doc-kind]', function () { $(this).closest('tr').attr('data-kind', $(this).val()); });

    // Είδη άδειας εκπαιδευτικών
    var $leaves = $('[data-sef-leaves] tbody'); // και ο πίνακας «Περιπτώσεις απουσιών» (ίδια χειριστήρια)
    var absTpl = $('[data-sef-absence-template]').html() || '';
    var topTpl = $('[data-sef-topic-template]').html() || '';
    $('[data-sef-topic-add]').on('click', function () {
      var $row = $(topTpl.replace(/__i__/g, 'n' + (counter++)));
      $('[data-sef-topics] tbody').append($row);
      $row.find('input[type="text"]').first().trigger('focus');
    });
    $('[data-sef-absence-add]').on('click', function () {
      var $row = $(absTpl.replace(/__i__/g, 'n' + (counter++)));
      $('[data-sef-absences] tbody').append($row);
      $row.find('input[type="text"]').first().trigger('focus');
    });
    var leaveTpl = $('[data-sef-leave-template]').html() || '';
    $('[data-sef-leave-add]').on('click', function () {
      var $row = $(leaveTpl.replace(/__i__/g, 'n' + (counter++)));
      $('[data-sef-panel="leaves"] [data-sef-leaves] tbody').append($row);
      $row.find('input[type="text"]').first().trigger('focus');
    });
    $leaves.on('click', '[data-sef-leave-del]', function () { $(this).closest('tr').remove(); });
    $leaves.on('click', '[data-sef-up]', function () { var $r = $(this).closest('tr'); $r.prev().before($r); });
    $leaves.on('click', '[data-sef-down]', function () { var $r = $(this).closest('tr'); $r.next().after($r); });

    // «Χρήση πρότασης» (π.χ. επωνυμία σε γενική)
    $(document).on('click', '[data-sef-fill]', function () { $('#' + $(this).attr('data-sef-fill')).val($(this).attr('data-value')).trigger('focus'); $(this).remove(); });

    if ($.fn.wpColorPicker) { $('.sef-color').wpColorPicker(); }

    var frame;
    $('[data-sef-logo-pick]').on('click', function (e) {
      e.preventDefault();
      if (!window.wp || !wp.media) { return; }
      if (!frame) {
        frame = wp.media({ title: 'Λογότυπο σχολείου', button: { text: 'Χρήση εικόνας' }, library: { type: 'image' }, multiple: false });
        frame.on('select', function () {
          var a = frame.state().get('selection').first().toJSON();
          var url = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
          $('[data-sef-logo-id]').val(a.id);
          $('[data-sef-logo-preview]').attr('src', url).prop('hidden', false);
          $('[data-sef-logo-clear]').prop('hidden', false);
        });
      }
      frame.open();
    });
    $('[data-sef-logo-clear]').on('click', function (e) {
      e.preventDefault();
      $('[data-sef-logo-id]').val('0');
      $('[data-sef-logo-preview]').prop('hidden', true).attr('src', '');
      $(this).prop('hidden', true);
    });
  });
  /* 0.9.2: έτοιμα shortcode του συνδέσμου-προβολής — αντιγραφή και «φτιάξτε το δικό σας» με προεπισκόπηση */
  $(function () {
    $(document).on('click', '[data-sef-copy]', function () {
      var b = this, f = document.querySelector(b.getAttribute('data-sef-copy')); if (!f) { return; }
      var done = function () { var t = b.textContent; b.textContent = 'Αντιγράφηκε ✓'; setTimeout(function () { b.textContent = t; }, 1600); };
      if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(f.value).then(done, function () { f.select(); document.execCommand('copy'); done(); }); }
      else { f.focus(); f.select(); try { document.execCommand('copy'); } catch (e) {} done(); }
    });
    var $b = $('[data-sef-promo-builder]'); if (!$b.length) { return; }
    var D = { kicker: 'Νέα υπηρεσία του σχολείου', title: 'Ηλεκτρονικά έντυπα και αιτήσεις', text: 'Αιτήσεις και δηλώσεις γονέων, άδειες εκπαιδευτικών και υπεύθυνες δηλώσεις, από το σπίτι.', button: 'Είσοδος' };
    var q = function (v) { return String(v).replace(/"([^"]*)"/g, '«$1»').replace(/"/g, '«').replace(/[\[\]]/g, ''); };
    function update() {
      var side = $b.find('[name="sef_pb_place"]:checked').val() === 'side', a = [];
      if (side) { a.push('heading="h3"'); }
      if ($b.find('[data-pb="sections"]').prop('checked')) { a.push('sections="yes"'); }
      if ($b.find('[data-pb="season"]').prop('checked')) { a.push('season="yes"'); }
      ['kicker', 'title', 'text', 'button'].forEach(function (k) { var v = $.trim($b.find('[data-pb="' + k + '"]').val()); if (v !== D[k]) { a.push(k + '="' + q(v) + '"'); } });
      $b.find('[data-pb-out]').val('[school_eforms_link' + (a.length ? ' ' + a.join(' ') : '') + ']');
      // προεπισκόπηση
      var $f = $b.find('[data-pb-frame]'); $f.toggleClass('is-side', side);
      var kv = $.trim($b.find('[data-pb="kicker"]').val()), tv = $.trim($b.find('[data-pb="text"]').val());
      $f.find('.sef-promo__kicker').text(kv).prop('hidden', !kv);
      $f.find('.sef-promo__title').text($.trim($b.find('[data-pb="title"]').val()) || D.title);
      $f.find('.sef-promo__text').text(tv).prop('hidden', !tv);
      $f.find('.sef-promo__btn').contents().first()[0].nodeValue = $.trim($b.find('[data-pb="button"]').val()) || D.button;
      $f.find('.sef-promo__links').prop('hidden', !$b.find('[data-pb="sections"]').prop('checked'));
      $f.find('.sef-promo__now').prop('hidden', !$b.find('[data-pb="season"]').prop('checked'));
    }
    $b.on('input change', 'input, textarea', update); update();
  });
})(jQuery);
