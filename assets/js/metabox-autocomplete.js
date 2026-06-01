/**
 * Enterprise Moto — metabox-autocomplete.js
 * Autocompletado AJAX en los campos del metabox de expedición.
 * Funciona con los campos que tengan data-ac-tax="category|post_tag".
 * Trabaja con nombres (no slugs).
 */
(function ($) {
  'use strict';

  if (!$ || !window.enterpriseMetabox) return;

  var ajaxUrl = window.enterpriseMetabox.ajaxUrl;
  var nonce   = window.enterpriseMetabox.nonce;

  function buildSuggestionList() {
    return $('<ul>').css({
      position:   'absolute',
      zIndex:     9999,
      background: '#fff',
      border:     '1px solid #ddd',
      borderTop:  'none',
      listStyle:  'none',
      margin:     0, padding: 0,
      boxShadow:  '0 4px 12px rgba(0,0,0,.12)',
      fontFamily: 'inherit', fontSize: 13,
    }).appendTo('body').hide();
  }

  function initField($input) {
    if ($input.data('ent-ac')) return;
    $input.data('ent-ac', true);

    var tax    = $input.data('ac-tax') || 'category';
    var isTag  = tax === 'post_tag';
    var $list  = buildSuggestionList();
    var timer, active = -1;

    function positionList() {
      var o = $input.offset();
      $list.css({ top: o.top + $input.outerHeight(), left: o.left, width: $input.outerWidth() });
    }

    function showResults(items) {
      $list.empty().hide();
      if (!items.length) return;
      items.forEach(function (item, idx) {
        $('<li>').text(item.name + (item.count ? ' (' + item.count + ')' : '')).css({
          padding: '7px 12px', cursor: 'pointer',
          borderBottom: '1px solid #f0f0f0',
        })
        .on('mouseenter', function () {
          $list.find('li').css('background','');
          $(this).css('background','#f5f5f2');
          active = idx;
        })
        .on('click', function () {
          selectItem(item);
          $list.hide();
        })
        .appendTo($list);
      });
      positionList();
      $list.show();
      active = -1;
    }

    function selectItem(item) {
      if (isTag) {
        // Para etiquetas: completar el último token
        var tokens = $input.val().split(',');
        tokens[tokens.length - 1] = ' ' + item.name;
        $input.val(tokens.join(',').replace(/^,\s*/, ''));
      } else {
        $input.val(item.name);
      }
    }

    $input.on('input', function () {
      clearTimeout(timer);
      var val = $input.val();
      var q   = isTag ? val.split(',').pop().trim() : val.trim();
      if (q.length < 2) { $list.hide(); return; }
      timer = setTimeout(function () {
        $.getJSON(ajaxUrl, {
          action: 'enterprise_term_search',
          nonce:  nonce,
          q:      q,
          tax:    tax,
        }, function (res) {
          showResults(res.success ? res.data : []);
        });
      }, 300);
    });

    $input.on('keydown', function (e) {
      var $items = $list.find('li');
      if (!$items.length || !$list.is(':visible')) return;
      if (e.key === 'ArrowDown')  { active = Math.min(active + 1, $items.length - 1); $items.css('background','').eq(active).css('background','#f5f5f2'); e.preventDefault(); }
      else if (e.key === 'ArrowUp') { active = Math.max(active - 1, 0); $items.css('background','').eq(active).css('background','#f5f5f2'); e.preventDefault(); }
      else if (e.key === 'Enter' && active >= 0) { $items.eq(active).trigger('click'); e.preventDefault(); }
      else if (e.key === 'Escape') { $list.hide(); }
    });

    $(window).on('scroll resize', function () { if ($list.is(':visible')) positionList(); });
    $(document).on('click', function (e) {
      if (!$(e.target).is($input) && !$(e.target).closest($list).length) $list.hide();
    });
  }

  // Inicializar todos los campos con data-ac-tax al cargar
  $(function () {
    $('[data-ac-tax]').each(function () { initField($(this)); });
  });

}(window.jQuery));
