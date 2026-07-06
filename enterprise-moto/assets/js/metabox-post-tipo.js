/**
 * Enterprise Moto — metabox-post-tipo.js
 * Muestra/oculta campos del metabox según el tipo de entrada seleccionado.
 * Gestiona el selector de etapas (tipo Viaje) con autocomplete AJAX.
 */
(function ($) {
  'use strict';

  var cfg = window.enterpriseMetabox || {};
  var ajaxUrl = cfg.ajaxUrl || '';
  var nonce   = cfg.nonce   || '';
  var i18n    = cfg.i18n    || {};

  /* ── Mostrar/ocultar grupos según el tipo ── */
  function switchTipo(tipo) {
    $('.ent-mb-group').removeClass('active');
    $('.ent-mb-group[data-tipo="' + tipo + '"]').addClass('active');
    /* Campo «Nombre en el ticker»: común a viaje/etapa/jornada, oculto en genérica */
    $('.ent-mb-ticker').toggle(tipo !== 'generica');
  }

  /* ── Chips de etapas seleccionadas ── */
  function getSelectedIds() {
    var val = $('#ent-etapas-ids').val();
    if (!val) return [];
    return val.split(',').map(Number).filter(Boolean);
  }

  function addEtapa(id, title) {
    var ids = getSelectedIds();
    if (ids.indexOf(id) !== -1) return; // ya existe
    ids.push(id);
    $('#ent-etapas-ids').val(ids.join(','));

    var $chip = $('<span class="ent-mb-chip" data-id="' + id + '">')
      .append($('<span>').text(title))
      .append($('<span class="ent-mb-chip-del" title="Quitar">×</span>'));
    $('#ent-etapas-chips').append($chip);
  }

  function removeEtapa(id) {
    var ids = getSelectedIds().filter(function(i) { return i !== id; });
    $('#ent-etapas-ids').val(ids.join(','));
    $('.ent-mb-chip[data-id="' + id + '"]').remove();
  }

  /* ── Autocomplete búsqueda de etapas ── */
  var acTimer;

  function searchEtapas(q) {
    if (q.length < 2) { $('#ent-etapas-ac').hide(); return; }
    var exclude = getSelectedIds().join(',');

    $.getJSON(ajaxUrl, {
      action:  'enterprise_search_posts',
      nonce:   nonce,
      q:       q,
      exclude: exclude,
    }, function(res) {
      var $ac = $('#ent-etapas-ac').empty().hide();
      if (!res.success || !res.data.length) {
        $ac.append($('<div class="ent-mb-ac-item">').text(i18n.noResults || 'Sin resultados')).show();
        return;
      }
      res.data.forEach(function(item) {
        var label = item.title + (item.meta ? ' — ' + item.meta : '');
        $('<div class="ent-mb-ac-item">').text(label)
          .on('click', function() {
            addEtapa(item.id, item.title);
            $('#ent-etapas-search').val('');
            $ac.hide();
          })
          .appendTo($ac);
      });
      $ac.show();
    });
  }

  /* ── Init ── */
  $(function () {
    var $tipo = $('#ent_post_tipo');
    if (!$tipo.length) return;

    /* Inicializar estado */
    switchTipo($tipo.val());

    /* Cambio de tipo */
    $tipo.on('change', function () {
      switchTipo($(this).val());
    });

    /* Quitar chip */
    $(document).on('click', '.ent-mb-chip-del', function () {
      var id = parseInt($(this).closest('.ent-mb-chip').data('id'), 10);
      removeEtapa(id);
    });

    /* Autocomplete etapas */
    $('#ent-etapas-search').on('input', function () {
      clearTimeout(acTimer);
      var q = $(this).val().trim();
      acTimer = setTimeout(function () { searchEtapas(q); }, 300);
    });

    /* Cerrar lista al hacer clic fuera */
    $(document).on('click', function (e) {
      if (!$(e.target).closest('#ent-etapas-search, #ent-etapas-ac').length) {
        $('#ent-etapas-ac').hide();
      }
    });
  });

}(window.jQuery));
