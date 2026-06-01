/**
 * enterprise-moto — blocks-media.js
 * Frontend JS para: photo-gallery y stories
 */
(function () {
  'use strict';

  /* ════════════════════════════════════════════════════════════
     PHOTO GALLERY — Carrusel + Lightbox
  ════════════════════════════════════════════════════════════ */
  function initGallery(wrap) {
    var track   = wrap.querySelector('.ent-gallery-track');
    var slides  = wrap.querySelectorAll('.ent-gallery-slide');
    var prevBtn = wrap.querySelector('.ent-gallery-prev');
    var nextBtn = wrap.querySelector('.ent-gallery-next');
    var dots    = wrap.querySelectorAll('.ent-gallery-dot');
    var playBtn = wrap.querySelector('.ent-gallery-play-toggle');
    var lbOverlay = wrap.querySelector('.ent-lb-overlay');
    var lbImg   = wrap.querySelector('.ent-lb-img');
    var lbCap   = wrap.querySelector('.ent-lb-caption');
    var lbClose = wrap.querySelector('.ent-lb-close');
    var lbPrev  = wrap.querySelector('.ent-lb-prev');
    var lbNext  = wrap.querySelector('.ent-lb-next');

    var total    = slides.length;
    var current  = 0;
    var autoplay = wrap.dataset.autoplay === 'true';
    var delay    = parseInt(wrap.dataset.delay, 10) || 4000;
    var timer    = null;
    var lbIndex  = 0;

    if (total === 0) return;

    // Recopilar hrefs para lightbox
    var hrefs    = [];
    var captions = [];
    slides.forEach(function (s) {
      var a = s.querySelector('a.ent-gallery-lb');
      hrefs.push(a ? a.href : '');
      captions.push(a ? (a.dataset.caption || '') : '');
    });

    function goTo(idx) {
      if (idx < 0) idx = total - 1;
      if (idx >= total) idx = 0;
      current = idx;
      track.style.transform = 'translateX(-' + (current * 100) + '%)';
      dots.forEach(function (d, i) {
        d.classList.toggle('is-active', i === current);
      });
    }

    function startAuto() {
      if (!autoplay) return;
      clearInterval(timer);
      timer = setInterval(function () { goTo(current + 1); }, delay);
    }
    function stopAuto() { clearInterval(timer); }

    if (prevBtn) prevBtn.addEventListener('click', function () { stopAuto(); goTo(current - 1); startAuto(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { stopAuto(); goTo(current + 1); startAuto(); });

    dots.forEach(function (d) {
      d.addEventListener('click', function () {
        stopAuto();
        goTo(parseInt(d.dataset.index, 10));
        startAuto();
      });
    });

    if (playBtn) {
      playBtn.addEventListener('click', function () {
        autoplay = !autoplay;
        playBtn.textContent = autoplay ? '⏸' : '▶';
        autoplay ? startAuto() : stopAuto();
      });
    }

    // Swipe táctil
    var touchStartX = 0;
    var stage = wrap.querySelector('.ent-gallery-stage');
    stage.addEventListener('touchstart', function (e) { touchStartX = e.touches[0].clientX; }, { passive: true });
    stage.addEventListener('touchend', function (e) {
      var dx = e.changedTouches[0].clientX - touchStartX;
      if (Math.abs(dx) > 40) { stopAuto(); goTo(current + (dx < 0 ? 1 : -1)); startAuto(); }
    });

    // Lightbox
    slides.forEach(function (s, i) {
      var a = s.querySelector('a.ent-gallery-lb');
      if (!a) return;
      a.addEventListener('click', function (e) {
        e.preventDefault();
        lbIndex = i;
        openLb(lbIndex);
      });
    });

    function openLb(idx) {
      lbImg.src = hrefs[idx] || '';
      lbCap.textContent = captions[idx] || '';
      lbIndex = idx;
      lbOverlay.hidden = false;
      document.body.style.overflow = 'hidden';
    }
    function closeLb() {
      lbOverlay.hidden = true;
      document.body.style.overflow = '';
    }

    if (lbClose) lbClose.addEventListener('click', closeLb);
    if (lbOverlay) lbOverlay.addEventListener('click', function (e) { if (e.target === lbOverlay) closeLb(); });
    if (lbPrev) lbPrev.addEventListener('click', function () { openLb((lbIndex - 1 + total) % total); });
    if (lbNext) lbNext.addEventListener('click', function () { openLb((lbIndex + 1) % total); });
    document.addEventListener('keydown', function (e) {
      if (lbOverlay.hidden) return;
      if (e.key === 'Escape') closeLb();
      if (e.key === 'ArrowLeft')  openLb((lbIndex - 1 + total) % total);
      if (e.key === 'ArrowRight') openLb((lbIndex + 1) % total);
    });

    goTo(0);
    startAuto();
  }

  /* ════════════════════════════════════════════════════════════
     STORIES — visor fullscreen con barra de progreso
  ════════════════════════════════════════════════════════════ */
  function initStories(wrap) {
    var thumbBtns = wrap.querySelectorAll('.ent-stories-thumb-btn');
    var viewer    = wrap.querySelector('.ent-stories-viewer');
    var closeBtn  = wrap.querySelector('.ent-stories-close');
    var bars      = wrap.querySelectorAll('.ent-stories-bar');
    var fills     = wrap.querySelectorAll('.ent-stories-fill');
    var slides    = wrap.querySelectorAll('.ent-stories-slide');
    var label     = wrap.querySelector('.ent-stories-label');
    var tapPrev   = wrap.querySelector('.ent-stories-tap-prev');
    var tapHold   = wrap.querySelector('.ent-stories-tap-hold');
    var tapNext   = wrap.querySelector('.ent-stories-tap-next');

    var total     = slides.length;
    var current   = 0;
    var duration  = parseInt(wrap.dataset.duration, 10) || 5000;
    var loop      = wrap.dataset.loop === 'true';
    var raf       = null;
    var startTs   = null;
    var paused    = false;
    var elapsed   = 0;
    var isOpen    = false;

    if (total === 0) return;

    function showSlide(idx) {
      slides.forEach(function (s, i) { s.hidden = (i !== idx); });
      bars.forEach(function (b, i) {
        b.classList.toggle('is-done', i < idx);
        b.classList.remove('is-active');
        fills[i].style.width = i < idx ? '100%' : (i === idx ? '0%' : '0%');
      });
      bars[idx] && bars[idx].classList.add('is-active');
      if (label) label.textContent = slides[idx].dataset.label || '';
      current = idx;
      elapsed = 0;

      // Pausa si es vídeo y empieza a reproducir
      var vid = slides[idx].querySelector('video');
      if (vid) {
        vid.currentTime = 0;
        vid.play().catch(function () {});
        // Usar duración real del vídeo si está disponible
        vid.onloadedmetadata = function () {
          duration = Math.round(vid.duration * 1000) || duration;
        };
      }
    }

    function nextSlide() {
      cancelAnimationFrame(raf);
      if (current + 1 < total) {
        showSlide(current + 1);
        if (!paused) animate();
      } else if (loop) {
        showSlide(0);
        if (!paused) animate();
      } else {
        fills[current].style.width = '100%';
        // Cerrar al terminar la última
        setTimeout(close, 400);
      }
    }

    function prevSlide() {
      cancelAnimationFrame(raf);
      showSlide(Math.max(0, current - 1));
      if (!paused) animate();
    }

    function animate(ts) {
      if (!startTs) startTs = ts;
      if (!ts) { startTs = null; raf = requestAnimationFrame(animate); return; }
      var dt    = ts - startTs + elapsed;
      var pct   = Math.min(100, (dt / duration) * 100);
      fills[current].style.width = pct + '%';
      if (pct >= 100) {
        startTs = null;
        elapsed = 0;
        nextSlide();
      } else {
        raf = requestAnimationFrame(animate);
      }
    }

    function pause() {
      if (paused) return;
      paused = true;
      elapsed += (performance.now() - (startTs || performance.now()));
      startTs = null;
      cancelAnimationFrame(raf);
      var vid = slides[current].querySelector('video');
      if (vid) vid.pause();
    }

    function resume() {
      if (!paused) return;
      paused = false;
      var vid = slides[current].querySelector('video');
      if (vid) vid.play().catch(function () {});
      raf = requestAnimationFrame(animate);
    }

    function open(idx) {
      isOpen = true;
      viewer.hidden = false;
      document.body.style.overflow = 'hidden';
      paused = false;
      elapsed = 0;
      showSlide(idx);
      raf = requestAnimationFrame(animate);
    }

    function close() {
      isOpen = false;
      cancelAnimationFrame(raf);
      viewer.hidden = true;
      document.body.style.overflow = '';
      var vid = slides[current].querySelector('video');
      if (vid) { vid.pause(); vid.currentTime = 0; }
    }

    // Abrir desde thumbnails
    thumbBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        open(parseInt(btn.dataset.index, 10) || 0);
      });
    });

    // Cerrar
    if (closeBtn) closeBtn.addEventListener('click', close);

    // Zonas de toque
    if (tapPrev) tapPrev.addEventListener('click', prevSlide);
    if (tapNext) tapNext.addEventListener('click', nextSlide);
    if (tapHold) {
      tapHold.addEventListener('mousedown',   pause);
      tapHold.addEventListener('mouseup',     resume);
      tapHold.addEventListener('mouseleave',  resume);
      tapHold.addEventListener('touchstart',  pause,  { passive: true });
      tapHold.addEventListener('touchend',    resume, { passive: true });
      tapHold.addEventListener('touchcancel', resume, { passive: true });
    }

    // Teclado
    document.addEventListener('keydown', function (e) {
      if (!isOpen) return;
      if (e.key === 'Escape')      close();
      if (e.key === 'ArrowLeft')   prevSlide();
      if (e.key === 'ArrowRight')  nextSlide();
      if (e.key === ' ')           paused ? resume() : pause();
    });
  }

  /* ════════════════════════════════════════════════════════════
     INIT
  ════════════════════════════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ent-gallery-wrap').forEach(initGallery);
    document.querySelectorAll('.ent-stories-wrap').forEach(initStories);
  });

})();
