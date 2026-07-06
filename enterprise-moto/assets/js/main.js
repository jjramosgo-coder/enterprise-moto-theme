/**
 * Enterprise Moto Theme — main.js
 * Nav scroll · Hamburger · Fade-in · Ticker
 */
(function () {
  'use strict';

  /* ── DOM ready ── */
  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    navScroll();
    hamburger();
    fadeInOnScroll();
    postCardHover();
    tickerDuplicate();
    stickyDataStrip();
  });

  /* ─────────────────────────────────────────
     NAV: sombra al hacer scroll
  ───────────────────────────────────────── */
  function navScroll() {
    var header = document.getElementById('site-header');
    if (!header) return;
    function update() {
      header.classList.toggle('scrolled', window.scrollY > 10);
    }
    window.addEventListener('scroll', update, { passive: true });
    update();
  }

  /* ─────────────────────────────────────────
     HAMBURGER: menú móvil
  ───────────────────────────────────────── */
  function hamburger() {
    var toggle = document.getElementById('nav-toggle');
    var wrapper = document.getElementById('nav-wrapper');
    if (!toggle || !wrapper) return;

    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:399;display:none;backdrop-filter:blur(2px);';
    document.body.appendChild(overlay);

    function open() {
      wrapper.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
      toggle.setAttribute('aria-label', 'Cerrar menú');
      overlay.style.display = 'block';
      document.body.style.overflow = 'hidden';
      var first = wrapper.querySelector('a');
      if (first) first.focus();
    }

    function close() {
      wrapper.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Abrir menú');
      overlay.style.display = 'none';
      document.body.style.overflow = '';
      toggle.focus();
    }

    toggle.addEventListener('click', function () {
      wrapper.classList.contains('is-open') ? close() : open();
    });

    overlay.addEventListener('click', close);

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && wrapper.classList.contains('is-open')) close();
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 640 && wrapper.classList.contains('is-open')) close();
    }, { passive: true });
  }

  /* ─────────────────────────────────────────
     FADE-IN: aparición suave al hacer scroll
  ───────────────────────────────────────── */
  function fadeInOnScroll() {
    if (!('IntersectionObserver' in window)) return;

    var style = document.createElement('style');
    style.textContent = '.fade-target{opacity:0;transform:translateY(20px);transition:opacity .55s ease,transform .55s ease;}.fade-target.is-visible{opacity:1;transform:none;}';
    document.head.appendChild(style);

    var targets = document.querySelectorAll(
      '.post-card, .featured-grid, .destination-item, .about-grid, .post-data-item'
    );

    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });

    targets.forEach(function (el, i) {
      el.classList.add('fade-target');
      el.style.transitionDelay = Math.min(i % 4 * 80, 240) + 'ms';
      obs.observe(el);
    });
  }

  /* ─────────────────────────────────────────
     POST CARD: animar barra de km
  ───────────────────────────────────────── */
  function postCardHover() {
    // Las transiciones CSS ya cubren el hover.
    // Aquí añadimos click en toda la card para móvil.
    var cards = document.querySelectorAll('.post-card');
    cards.forEach(function (card) {
      card.style.cursor = 'pointer';
      card.addEventListener('click', function (e) {
        if (e.target.tagName === 'A') return;
        var link = card.querySelector('.post-card-title a, .post-card-arrow');
        if (link) window.location.href = link.href;
      });
    });
  }

  /* ─────────────────────────────────────────
     TICKER: duplicar contenido para bucle
     (el CSS ya lo hace si hay suficientes items,
     pero esto lo garantiza dinámicamente)
  ───────────────────────────────────────── */
  function tickerDuplicate() {
    var track = document.querySelector('.ticker-track');
    if (!track) return;
    // Si el contenido ya está duplicado por PHP, no hacemos nada.
    // Si no, duplicamos.
    var items = track.querySelectorAll('.ticker-item');
    if (items.length < 6) {
      track.innerHTML += track.innerHTML;
    }
  }

  /* ─────────────────────────────────────────
     DATA STRIP: ocultar al llegar al footer
  ───────────────────────────────────────── */
  function stickyDataStrip() {
    var strip = document.querySelector('.post-data-strip');
    if (!strip) return;
    // La franja no es sticky, solo scroll-aware para futuros usos.
  }

  /* ─────────────────────────────────────────
     SMOOTH SCROLL para anclas internas
  ───────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var link = e.target.closest('a[href^="#"]');
    if (!link) return;
    var id = link.getAttribute('href').slice(1);
    if (!id) return;
    var target = document.getElementById(id);
    if (!target) return;
    e.preventDefault();
    var navH = (document.getElementById('site-header') || {}).offsetHeight || 64;
    var top = target.getBoundingClientRect().top + window.scrollY - navH - 16;
    window.scrollTo({ top: top, behavior: 'smooth' });
  });

})();

  /* ─────────────────────────────────────────
     BOTÓN SCROLL AL INICIO
     Aparece tras hacer scroll 300px.
     Click → smooth scroll al top.
  ───────────────────────────────────────── */
  (function () {
    var btn = document.createElement('button');
    btn.className    = 'ent-scroll-top';
    btn.setAttribute('aria-label', 'Volver al inicio de la página');
    btn.setAttribute('title',      'Volver al inicio');
    btn.innerHTML    = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>';
    document.body.appendChild(btn);

    var ticking = false;
    window.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () {
        btn.classList.toggle('is-visible', window.scrollY > 300);
        ticking = false;
      });
    }, { passive: true });

    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  })();

  /* ─────────────────────────────────────────
     CURSOR PERSONALIZADO
     Solo en dispositivos con ratón/trackpad.
     Punto interior: sigue el cursor al instante.
     Anillo exterior: sigue con lerp (suavizado).
  ───────────────────────────────────────── */
  (function () {
    if (!window.matchMedia('(pointer: fine)').matches) return;
    if (!document.body.classList.contains('cursor-custom-enabled')) return;

    /* Crear elementos */
    var dot  = document.createElement('div');
    var ring = document.createElement('div');
    dot.className  = 'ent-cursor-dot';
    ring.className = 'ent-cursor-ring';
    document.body.appendChild(dot);
    document.body.appendChild(ring);

    var mx = -100, my = -100; /* posición del ratón */
    var rx = -100, ry = -100; /* posición del anillo (lerp) */
    var LERP = 0.12;           /* factor de suavizado (0=inmóvil, 1=instantáneo) */
    var raf;

    document.addEventListener('mousemove', function (e) {
      mx = e.clientX;
      my = e.clientY;
      /* El punto sigue al instante */
      dot.style.transform  = 'translate(' + mx + 'px,' + my + 'px)';
    }, { passive: true });

    /* Anillo con lerp en requestAnimationFrame */
    function tick() {
      rx += (mx - rx) * LERP;
      ry += (my - ry) * LERP;
      ring.style.transform = 'translate(' + rx + 'px,' + ry + 'px)';
      raf = requestAnimationFrame(tick);
    }
    raf = requestAnimationFrame(tick);

    /* Estados: hover sobre links/botones → anillo más grande */
    function onEnter() { ring.classList.add('is-hover'); dot.classList.add('is-hover'); }
    function onLeave() { ring.classList.remove('is-hover'); dot.classList.remove('is-hover'); }

    document.addEventListener('mouseover', function (e) {
      if (e.target.closest('a, button, [role="button"], label, .post-card, .ent-tl-card, .ent-card')) onEnter();
    }, { passive: true });
    document.addEventListener('mouseout', function (e) {
      if (e.target.closest('a, button, [role="button"], label, .post-card, .ent-tl-card, .ent-card')) onLeave();
    }, { passive: true });

    /* Ocultar cursor nativo globalmente */
    document.documentElement.classList.add('has-custom-cursor');
  })();
