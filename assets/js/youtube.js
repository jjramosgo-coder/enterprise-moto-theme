/**
 * enterprise-moto — youtube.js
 * Frontend: youtube-video + youtube-reels
 * Carga diferida de iframes + dots de navegación en móvil.
 */
(function () {
  'use strict';

  /* ── Activar iframe al hacer click en el stage ── */
  function initStage(stage) {
    if (stage._yt_init) return;
    stage._yt_init = true;

    var embedUrl  = stage.dataset.embedUrl;
    var iframeWrap = stage.querySelector('.ent-yt-iframe-wrap');
    var playBtn    = stage.querySelector('.ent-yt-play');

    function activate() {
      if (!embedUrl || !iframeWrap) return;
      var iframe = document.createElement('iframe');
      iframe.src             = embedUrl;
      iframe.allow           = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
      iframe.allowFullscreen = true;
      iframe.loading         = 'lazy';
      iframeWrap.appendChild(iframe);
      iframeWrap.hidden = false;
      stage.classList.add('is-playing');
    }

    stage.addEventListener('click', activate);
    if (playBtn) playBtn.addEventListener('click', function(e) { e.stopPropagation(); activate(); });
  }

  /* ── Carrusel de reels: dots + flechas prev/next ── */
  function initReelsDots(wrap) {
    var track   = wrap.querySelector('.ent-reels-track');
    var dots    = wrap.querySelectorAll('.ent-reels-dot');
    var prevBtn = wrap.querySelector('.ent-reels-prev');
    var nextBtn = wrap.querySelector('.ent-reels-next');
    if (!track) return;

    var cards = track.querySelectorAll('.ent-reel-card');
    if (!cards.length) return;

    var current = 0;

    function scrollTo(idx) {
      if (idx < 0) idx = cards.length - 1;
      if (idx >= cards.length) idx = 0;
      current = idx;
      cards[current].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      dots.forEach(function(d, i) { d.classList.toggle('is-active', i === current); });
    }

    // Dots clickables
    dots.forEach(function(d, i) {
      d.addEventListener('click', function() { scrollTo(i); });
    });

    // Flechas
    if (prevBtn) prevBtn.addEventListener('click', function() { scrollTo(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function() { scrollTo(current + 1); });

    // Actualizar dot activo al hacer scroll nativo (swipe móvil)
    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (!entry.isIntersecting) return;
        var idx = Array.from(cards).indexOf(entry.target);
        if (idx !== -1) {
          current = idx;
          dots.forEach(function(d, i) { d.classList.toggle('is-active', i === idx); });
        }
      });
    }, { root: track, threshold: 0.5 });

    cards.forEach(function(card) { observer.observe(card); });
  }

  /* ── Init ── */
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ent-yt-stage').forEach(initStage);
    document.querySelectorAll('.ent-reels-wrap').forEach(initReelsDots);
  });

})();
