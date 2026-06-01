/**
 * Enterprise Moto — carousel.js
 * Lógica de carrusel para el bloque enterprise/post-stages.
 * Sin dependencias externas. Vanilla JS.
 */
(function () {
  'use strict';

  /* ── Inicializar todos los carruseles cuando el DOM está listo ── */
  function init() {
    document.querySelectorAll('.ent-stages--carousel').forEach( initCarousel );
  }

  if ( document.readyState !== 'loading' ) init();
  else document.addEventListener( 'DOMContentLoaded', init );

  /* ── Si el bloque se inserta en el editor (server-side render) ── */
  if ( window.wp && wp.hooks ) {
    wp.hooks.addAction( 'enqueue_block_editor_assets', 'enterprise-moto', function () {
      setTimeout( init, 500 );
    });
  }

  /* ─────────────────────────────────────────
     CARRUSEL INDIVIDUAL
  ───────────────────────────────────────── */
  function initCarousel( el ) {
    var uid     = el.id;
    var track   = el.querySelector('.ent-stages__track');
    var slides  = el.querySelectorAll('.ent-stages__slide');
    var count   = slides.length;
    if ( ! track || count < 2 ) return;

    var btnPrev   = el.querySelector('.ent-stages__nav-btn--prev');
    var btnNext   = el.querySelector('.ent-stages__nav-btn--next');
    var dots      = el.querySelectorAll('.ent-stages__dot');
    var counter   = el.querySelector('.ent-stages__nav-current');
    var current   = 0;

    /* Evitar doble inicialización */
    if ( el.dataset.carouselInit ) return;
    el.dataset.carouselInit = '1';

    /* ── Ir a slide ── */
    function goTo( index, animated ) {
      animated = animated !== false;
      index = Math.max( 0, Math.min( count - 1, index ) );
      current = index;

      /* Scroll al slide */
      var slide = slides[ index ];
      if ( slide ) {
        track.style.scrollBehavior = animated ? 'smooth' : 'auto';
        track.scrollLeft = slide.offsetLeft;
      }

      updateUI();
    }

    /* ── Actualizar UI ── */
    function updateUI() {
      if ( counter ) counter.textContent = current + 1;

      if ( btnPrev ) btnPrev.disabled = ( current === 0 );
      if ( btnNext ) btnNext.disabled = ( current === count - 1 );

      dots.forEach( function ( dot, i ) {
        dot.classList.toggle( 'is-active', i === current );
      });

      slides.forEach( function ( slide, i ) {
        slide.setAttribute( 'aria-hidden', i !== current ? 'true' : 'false' );
      });
    }

    /* ── Detectar posición actual por scroll ── */
    var scrollTimer;
    track.addEventListener( 'scroll', function () {
      clearTimeout( scrollTimer );
      scrollTimer = setTimeout( function () {
        var slideW = slides[0] ? slides[0].offsetWidth + 16 : 1; // gap = 16px
        var idx = Math.round( track.scrollLeft / slideW );
        if ( idx !== current ) {
          current = Math.max( 0, Math.min( count - 1, idx ) );
          updateUI();
        }
      }, 80 );
    }, { passive: true } );

    /* ── Botones prev/next ── */
    if ( btnPrev ) btnPrev.addEventListener( 'click', function () { goTo( current - 1 ); } );
    if ( btnNext ) btnNext.addEventListener( 'click', function () { goTo( current + 1 ); } );

    /* ── Dots ── */
    dots.forEach( function ( dot, i ) {
      dot.addEventListener( 'click', function () { goTo( i ); } );
    });

    /* ── Teclado (cuando el foco está dentro del carrusel) ── */
    el.addEventListener( 'keydown', function ( e ) {
      if ( e.key === 'ArrowLeft'  ) { goTo( current - 1 ); e.preventDefault(); }
      if ( e.key === 'ArrowRight' ) { goTo( current + 1 ); e.preventDefault(); }
    });

    /* ── Touch / swipe ── */
    var touchStartX = 0;
    var touchStartY = 0;
    var isDragging  = false;

    track.addEventListener( 'touchstart', function ( e ) {
      touchStartX = e.touches[0].clientX;
      touchStartY = e.touches[0].clientY;
      isDragging  = false;
    }, { passive: true } );

    track.addEventListener( 'touchmove', function ( e ) {
      var dx = Math.abs( e.touches[0].clientX - touchStartX );
      var dy = Math.abs( e.touches[0].clientY - touchStartY );
      if ( dx > dy && dx > 5 ) isDragging = true;
    }, { passive: true } );

    track.addEventListener( 'touchend', function ( e ) {
      if ( ! isDragging ) return;
      var dx = e.changedTouches[0].clientX - touchStartX;
      if ( Math.abs( dx ) > 40 ) {
        goTo( dx < 0 ? current + 1 : current - 1 );
      }
    } );

    /* ── Estado inicial ── */
    updateUI();
  }

  /* ─────────────────────────────────────────
     BOTONES FUERA DEL CARRUSEL (data-target)
     Permite colocar botones prev/next anywhere
  ───────────────────────────────────────── */
  document.addEventListener( 'click', function ( e ) {
    var btn = e.target.closest('[data-target][class*="ent-stages__nav-btn"]');
    if ( ! btn ) return;
    var targetId = btn.dataset.target;
    var carousel = document.getElementById( targetId );
    if ( ! carousel ) return;
    // El evento ya está manejado por initCarousel, nada más que hacer.
  });

})();
