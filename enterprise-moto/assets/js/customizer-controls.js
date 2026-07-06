/**
 * Enterprise Moto — customizer-controls.js v1.8.2
 *
 * Autocomplete por nombre en los campos del Personalizador.
 * - "Hijos de categoría": muestra el campo de nombre/categoría raíz
 * - Múltiples etiquetas: completa token a token separando por coma
 * - Al seleccionar sugerencia: escribe el NOMBRE (no el slug)
 */
(function ($, api) {
  'use strict';

  if (!$ || !api) return;

  var ajaxUrl = (window.enterpriseCustomizer || {}).ajaxUrl || '';
  var nonce   = (window.enterpriseCustomizer || {}).nonce   || '';

  /* ── Búsqueda AJAX de términos ── */
  function searchTerms(q, tax, callback) {
    if (!ajaxUrl || !q || q.length < 2) { callback([]); return; }
    $.getJSON(ajaxUrl, {
      action: 'enterprise_term_search',
      nonce:  nonce,
      q:      q,
      tax:    tax,
    }, function (res) {
      callback(res.success ? res.data : []);
    }).fail(function () { callback([]); });
  }

  /* ── Inicializar autocompletado en un <input> ── */
  function initAutocomplete($input, getType) {
    if ($input.data('ent-ac')) return;
    $input.data('ent-ac', true);

    /* Lista de sugerencias */
    var $list = $('<ul>').css({
      position: 'fixed', zIndex: 999999,
      background: '#fff', border: '1px solid #ddd', borderTop: 'none',
      listStyle: 'none', margin: 0, padding: 0,
      maxHeight: 220, overflowY: 'auto',
      boxShadow: '0 4px 12px rgba(0,0,0,.15)',
      fontFamily: 'inherit', fontSize: 13,
    }).appendTo('body').hide();

    var timer, active = -1;

    function positionList() {
      var offset = $input.offset();
      $list.css({
        top:   offset.top + $input.outerHeight(),
        left:  offset.left,
        width: $input.outerWidth(),
      });
    }

    function showSuggestions(items) {
      $list.empty().hide();
      if (!items.length) return;
      var type = getType();
      items.forEach(function (item, idx) {
        $('<li>').text(item.label).css({
          padding: '7px 12px', cursor: 'pointer',
          borderBottom: '1px solid #f0f0f0',
        })
        .on('mouseenter', function () {
          $list.find('li').css('background', '');
          $(this).css('background', '#f5f5f2');
          active = idx;
        })
        .on('click', function () {
          selectItem(item, type);
          $list.hide();
        })
        .appendTo($list);
      });
      positionList();
      $list.show();
      active = -1;
    }

    function selectItem(item, type) {
      /* Insertar el NOMBRE, no el slug */
      var name = item.name;
      if (type === 'tag') {
        /* Completar solo el último token */
        var tokens = $input.val().split(',');
        tokens[tokens.length - 1] = ' ' + name;
        $input.val(tokens.join(',').replace(/^,\s*/, '')).trigger('change');
      } else {
        $input.val(name).trigger('change');
      }
    }

    $input.on('input', function () {
      clearTimeout(timer);
      var val  = $input.val();
      var type = getType();
      if (!type || type === '') { $list.hide(); return; }

      /* Para tags, buscar solo el último token */
      var q = type === 'tag'
        ? val.split(',').pop().trim()
        : val.trim();

      if (q.length < 2) { $list.hide(); return; }

      var tax = type === 'cat' || type === 'cat_children'
        ? 'category'
        : 'post_tag';

      timer = setTimeout(function () {
        searchTerms(q, tax, showSuggestions);
      }, 300);
    });

    $input.on('keydown', function (e) {
      var $items = $list.find('li');
      if (!$items.length || !$list.is(':visible')) return;
      if (e.key === 'ArrowDown') {
        active = Math.min(active + 1, $items.length - 1);
        $items.css('background', '').eq(active).css('background', '#f5f5f2');
        e.preventDefault();
      } else if (e.key === 'ArrowUp') {
        active = Math.max(active - 1, 0);
        $items.css('background', '').eq(active).css('background', '#f5f5f2');
        e.preventDefault();
      } else if (e.key === 'Enter' && active >= 0) {
        $items.eq(active).trigger('click');
        e.preventDefault();
      } else if (e.key === 'Escape') {
        $list.hide();
      }
    });

    $(window).on('scroll resize', function () {
      if ($list.is(':visible')) positionList();
    });

    $(document).on('click', function (e) {
      if (!$(e.target).is($input) && !$(e.target).closest($list).length) {
        $list.hide();
      }
    });
  }

  /* ── Gestionar un grupo del Personalizador ── */
  function setupGroup(n) {
    var typeId = 'enterprise_group_' + n + '_type';
    var slugId = 'enterprise_group_' + n + '_slug';

    api(typeId, function (typeSetting) {
      api(slugId, function (slugSetting) {

        function getType() { return typeSetting.get(); }

        function update() {
          var type = getType();
          var $slugCtrl = $('#customize-control-' + slugId);
          if (!$slugCtrl.length) return;

          var $input = $slugCtrl.find('input[type="text"]');
          var $title = $slugCtrl.find('.customize-control-title');
          var $desc  = $slugCtrl.find('.description');

          /* Bug fix: mostrar siempre el campo, incluso para cat_children */
          $slugCtrl.show();

          /* Ajustar etiquetas según el tipo */
          if (type === 'cat_children') {
            $title.text('Categoría raíz — escribe el nombre');
            $desc.text('Escribe el nombre de la categoría cuyos hijos se mostrarán como secciones independientes. Ej: Tipo de salida');
          } else if (type === 'cat') {
            $title.text('Categoría — escribe el nombre');
            $desc.text('Escribe el nombre de la categoría y selecciona de las sugerencias.');
          } else if (type === 'tag') {
            $title.text('Etiqueta(s) — escribe el nombre');
            $desc.text('Cada etiqueta genera su propia sección. Para varias, sepáralas con coma. Ej: Italia, Sicilia, Andalucía');
          } else {
            /* Desactivada: ocultar campo slug */
            $slugCtrl.hide();
            return;
          }

          /* Inicializar autocompletado */
          if ($input.length) initAutocomplete($input, getType);
        }

        typeSetting.bind(function () { setTimeout(update, 100); });
        api.bind('ready', function () { setTimeout(update, 600); });
      });
    });
  }

  api.bind('ready', function () {
    for (var n = 1; n <= 6; n++) setupGroup(n);


  });

}(window.jQuery, window.wp && window.wp.customize));
