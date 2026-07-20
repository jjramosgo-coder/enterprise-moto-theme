/**
 * Enterprise Moto — map-frontend.js v1.6.1
 *
 * OpenLayers para enterprise/location-map y enterprise/route-map.
 * Doble GPX, escala de altitudes, toggle/hover de rutas,
 * distancias duales coloreadas, elevación desde el GPX correcto.
 */
(function () {
  'use strict';

  /* ═══════════════════════════════════════════
     UTILIDADES
  ═══════════════════════════════════════════ */
  function haversine(la1,lo1,la2,lo2){var R=6371000,dL=(la2-la1)*Math.PI/180,dO=(lo2-lo1)*Math.PI/180,a=Math.sin(dL/2)*Math.sin(dL/2)+Math.cos(la1*Math.PI/180)*Math.cos(la2*Math.PI/180)*Math.sin(dO/2)*Math.sin(dO/2);return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));}

  function subsample(pts,max){if(pts.length<=max)return pts;var s=pts.length/max,r=[];for(var i=0;i<max;i++)r.push(pts[Math.round(i*s)]);r.push(pts[pts.length-1]);return r;}

  function parseGPX(xml){
    var doc=new DOMParser().parseFromString(xml,'application/xml');
    var res={trackSegments:[],waypoints:[]};
    doc.querySelectorAll('trkseg').forEach(function(seg){
      var pts=[];
      seg.querySelectorAll('trkpt').forEach(function(pt){
        var la=parseFloat(pt.getAttribute('lat')),lo=parseFloat(pt.getAttribute('lon'));
        var el=pt.querySelector('ele');
        if(!isNaN(la)&&!isNaN(lo))pts.push({lat:la,lng:lo,ele:el?parseFloat(el.textContent):null});
      });
      if(pts.length)res.trackSegments.push(pts);
    });
    doc.querySelectorAll('rtept,wpt').forEach(function(pt){
      var la=parseFloat(pt.getAttribute('lat')),lo=parseFloat(pt.getAttribute('lon'));
      if(!isNaN(la)&&!isNaN(lo)){
        var n=pt.querySelector('name'),d=pt.querySelector('desc');
        res.waypoints.push({lat:la,lng:lo,name:n?n.textContent.trim():'',desc:d?d.textContent.trim():''});
      }
    });
    return res;
  }

  function calcStats(pts){
    var dist=0,gain=0;
    for(var i=1;i<pts.length;i++){
      dist+=haversine(pts[i-1].lat,pts[i-1].lng,pts[i].lat,pts[i].lng);
      if(pts[i].ele!==null&&pts[i-1].ele!==null){var d=pts[i].ele-pts[i-1].ele;if(d>0)gain+=d;}
    }
    return{km:(dist/1000).toFixed(1)+' km',gain:'+'+Math.round(gain)+' m',
           eles:pts.map(function(p){return p.ele;}).filter(function(e){return e!==null;})};
  }

  function hexRgb(hex){
    hex=hex.replace('#','');
    if(hex.length===3)hex=hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
    var n=parseInt(hex,16);return[(n>>16)&255,(n>>8)&255,n&255];
  }

  /* ── Perfil de elevación con escala de altitudes ── */
  function drawElevation(canvasId, eles, lineColor) {
    lineColor = lineColor || '#001f5c';
    var canvas = document.getElementById(canvasId);
    if (!canvas || !eles || eles.length < 2) return;

    var LPAD = 52; // espacio para etiquetas Y
    var wrap  = canvas.parentElement;
    var W     = wrap.offsetWidth || 600;
    var H     = Math.max((wrap.offsetHeight || 120) - 24, 60);
    canvas.width  = W;
    canvas.height = H;

    var ctx  = canvas.getContext('2d');
    var minE = Math.min.apply(null, eles);
    var maxE = Math.max.apply(null, eles);
    var rng  = maxE - minE || 1;
    var pad  = 8;

    function yPos(e) { return H - pad - ((e - minE) / rng) * (H - pad * 2); }

    /* Etiquetas Y y líneas de guía */
    var levels = [minE, (minE + maxE) / 2, maxE];
    ctx.font         = '10px sans-serif';
    ctx.textAlign    = 'right';
    ctx.textBaseline = 'middle';
    levels.forEach(function (e) {
      var y = yPos(e);
      ctx.fillStyle   = '#1a1a1a'; /* negro — visible sobre fondo claro */
      ctx.fillText(Math.round(e) + 'm', LPAD - 5, y);
      ctx.beginPath();
      ctx.strokeStyle = 'rgba(0,0,0,.07)';
      ctx.lineWidth   = 0.5;
      ctx.moveTo(LPAD, y);
      ctx.lineTo(W, y);
      ctx.stroke();
    });

    /* Línea vertical del eje Y */
    ctx.beginPath();
    ctx.strokeStyle = 'rgba(0,0,0,.12)';
    ctx.lineWidth   = 1;
    ctx.moveTo(LPAD, pad);
    ctx.lineTo(LPAD, H - pad);
    ctx.stroke();

    /* Gradiente de relleno */
    var rgb  = hexRgb(lineColor);
    var grad = ctx.createLinearGradient(0, 0, 0, H);
    grad.addColorStop(0, 'rgba(' + rgb + ',.35)');
    grad.addColorStop(1, 'rgba(' + rgb + ',.04)');

    var chartW = W - LPAD;
    function xPos(i) { return LPAD + (i / (eles.length - 1)) * chartW; }

    ctx.beginPath();
    eles.forEach(function (e, i) { i ? ctx.lineTo(xPos(i), yPos(e)) : ctx.moveTo(xPos(i), yPos(e)); });
    ctx.lineTo(W, H); ctx.lineTo(LPAD, H); ctx.closePath();
    ctx.fillStyle = grad; ctx.fill();

    ctx.beginPath();
    eles.forEach(function (e, i) { i ? ctx.lineTo(xPos(i), yPos(e)) : ctx.moveTo(xPos(i), yPos(e)); });
    ctx.strokeStyle = lineColor; ctx.lineWidth = 2; ctx.lineJoin = 'round'; ctx.stroke();
  }

  /* ═══════════════════════════════════════════
     ESTILOS OPENL AYERS
  ═══════════════════════════════════════════ */

  function olPinStyle(number) {
    var num = number != null ? String(number) : '';
    var svg = encodeURIComponent(
      '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="36">' +
      '<path d="M14 1C8.2 1 3.5 5.7 3.5 11.5c0 8.1 10.5 23.5 10.5 23.5s10.5-15.4 10.5-23.5C24.5 5.7 19.8 1 14 1z"' +
      ' fill="#0e0e0e" stroke="#f2c118" stroke-width="2.2"/>' +
      (num ? '<text x="14" y="13" font-family="sans-serif" font-size="10" font-weight="700" fill="#fff" text-anchor="middle" dominant-baseline="middle">'+num+'</text>' : '') +
      '</svg>'
    );
    return new ol.style.Style({
      image: new ol.style.Icon({
        src: 'data:image/svg+xml,' + svg,
        anchor: [0.5, 1.0], anchorXUnits: 'fraction', anchorYUnits: 'fraction',
      }),
    });
  }

  function olDiamondStyle() {
    return new ol.style.Style({
      image: new ol.style.RegularShape({
        points: 4, radius: 10, angle: Math.PI / 4,
        fill:   new ol.style.Fill({ color: '#0e0e0e' }),
        stroke: new ol.style.Stroke({ color: '#f2c118', width: 2.2 }),
      }),
    });
  }

  function olLabelStyle(text, bg) {
    return new ol.style.Style({
      image: new ol.style.Circle({
        radius: 7,
        fill:   new ol.style.Fill({ color: bg }),
        stroke: new ol.style.Stroke({ color: '#f2c118', width: 2 }),
      }),
      text: new ol.style.Text({
        text: text, font: 'bold 11px sans-serif',
        fill: new ol.style.Fill({ color: '#fff' }),
        backgroundFill: new ol.style.Fill({ color: bg }),
        padding: [3, 7, 3, 7], offsetY: -24, textAlign: 'center',
      }),
    });
  }

  /* ── Popup ── */
  function makePopup(container) {
    var el = document.createElement('div');
    el.className  = 'ent-ol-popup';
    el.style.cssText = 'display:none;background:#fff;border:1px solid #e2e2de;padding:12px 16px;min-width:160px;max-width:240px;font-family:inherit;box-shadow:0 4px 16px rgba(0,0,0,.12);pointer-events:none;';
    container.appendChild(el);
    var ov = new ol.Overlay({
      element: el, positioning: 'bottom-center', offset: [0, -18],
      autoPan: { animation: { duration: 200 } },
    });
    return { el: el, overlay: ov };
  }

  function popupHtml(name, desc, url) {
    var h = '<div class="ent-map-popup">';
    if (name) h += '<div class="ent-map-popup__title">' + name + '</div>';
    if (desc) h += '<div class="ent-map-popup__desc">'  + desc + '</div>';
    if (url)  h += '<a class="ent-map-popup__link" href="'+url+'">\u2192 Leer la entrada</a>';
    return h + '</div>';
  }

  function attachInteraction(map, pop) {
    map.on('click', function (e) {
      var f = map.forEachFeatureAtPixel(e.pixel, function(x){return x;});
      if (f && f.get('_pt')) {
        pop.el.innerHTML = popupHtml(f.get('_name'), f.get('_desc'), f.get('_url') || '');
        pop.el.style.display = 'block';
        pop.overlay.setPosition(e.coordinate);
      } else {
        pop.el.style.display = 'none';
        pop.overlay.setPosition(undefined);
      }
    });
    map.on('pointermove', function (e) {
      map.getTargetElement().style.cursor = map.hasFeatureAtPixel(e.pixel) ? 'pointer' : '';
    });
  }

  /* ── Hint flotante (Ctrl+scroll / dos dedos) ── */
  function showMapHint(container, msg) {
    var hint = container.querySelector('.ent-map-hint');
    if (!hint) {
      hint = document.createElement('div');
      hint.className = 'ent-map-hint';
      hint.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(14,14,14,.82);color:#f5f4ef;padding:10px 20px;font-size:13px;font-family:inherit;pointer-events:none;z-index:20;white-space:nowrap;opacity:0;transition:opacity .2s;border-radius:2px;letter-spacing:.02em;';
      container.appendChild(hint);
    }
    hint.textContent = msg;
    hint.style.opacity = '1';
    clearTimeout(hint._t);
    hint._t = setTimeout(function () { hint.style.opacity = '0'; }, 1800);
  }

  /* ── Base map ──
     Desktop:  wheel sin Ctrl → bloqueado (capture), se muestra hint.
     Móvil:    pointermove a 1 dedo → bloqueado (capture), OL no mueve el mapa.
               pointermove a 2 dedos → pasa a OL → pinch-zoom funciona.
     Los pointerdown SIEMPRE llegan a OL (necesario para que gestione el pinch). */
  function makeMap(uid, popOverlay) {
    var map = new ol.Map({
      target:   uid,
      overlays: popOverlay ? [popOverlay] : [],
      layers:   [new ol.layer.Tile({ source: new ol.source.OSM() })],
      view:     new ol.View({ center: ol.proj.fromLonLat([13, 38]), zoom: 6 }),
    });

    var container = document.getElementById(uid);
    if (!container) return map;

    /* ── Desktop: bloquear wheel sin Ctrl ── */
    container.addEventListener('wheel', function (e) {
      if (!e.ctrlKey && !e.metaKey) {
        e.stopImmediatePropagation();
        showMapHint(container, '\uD83D\uDDB1 Ctrl\u00a0+\u00a0scroll\u00a0para\u00a0hacer\u00a0zoom');
      }
    }, { capture: true, passive: true });

    /* ── Móvil: rastrear punteros activos (sin interferir con OL) ── */
    var activePointers = new Set();

    /* pointerdown en burbuja → OL lo ve primero, luego nosotros lo registramos */
    container.addEventListener('pointerdown', function (e) {
      activePointers.add(e.pointerId);
    }, { passive: true });

    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (evt) {
      container.addEventListener(evt, function (e) {
        activePointers.delete(e.pointerId);
      }, { passive: true });
    });

    /* pointermove en CAPTURE: si solo hay 1 dedo, OL nunca lo ve → mapa no se mueve.
       Con 2+ dedos, el evento pasa → OL gestiona pinch-zoom normalmente. */
    container.addEventListener('pointermove', function (e) {
      if (e.pointerType === 'touch' && activePointers.size <= 1) {
        e.stopImmediatePropagation();
        showMapHint(container, '\u261D\uFE0F\u00a0Usa\u00a0dos\u00a0dedos\u00a0para\u00a0mover\u00a0el\u00a0mapa');
      }
    }, { capture: true, passive: true });

    return map;
  }

  /* ═══════════════════════════════════════════
     MAPA DE LOCALIZACIONES
  ═══════════════════════════════════════════ */
  function initLocationMap(container) {
    if (container._ent_init) return;
    container._ent_init = true;

    var uid     = container.id;
    var zoom    = parseInt(container.dataset.zoom, 10) || 6;
    var markers = [];
    try { markers = JSON.parse(container.dataset.markers || '[]'); } catch(e) { return; }
    if (!markers.length) return;

    var pop = makePopup(container);
    var map = makeMap(uid, pop.overlay);
    container._olMap = map;
    attachInteraction(map, pop);

    var feats = [];
    markers.forEach(function (m, i) {
      if (!m.lat || !m.lng) return;
      var f = new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.fromLonLat([m.lng, m.lat])),
        _pt: true, _name: m.name||'', _desc: m.description||'', _url: m.postUrl||'',
      });
      f.setStyle(olPinStyle(i + 1));
      feats.push(f);
    });

    var src = new ol.source.Vector({ features: feats });
    map.addLayer(new ol.layer.Vector({ source: src }));

    if (feats.length === 1) {
      map.getView().setCenter(feats[0].getGeometry().getCoordinates());
      map.getView().setZoom(zoom);
    } else if (feats.length > 1) {
      map.getView().fit(src.getExtent(), { size: map.getSize(), padding: [60,60,60,60] });
    }

    var wrap = document.getElementById(uid + '-wrap');
    if (wrap) {
      wrap.querySelectorAll('[data-legend-index]').forEach(function (item) {
        item.addEventListener('click', function () {
          var f = feats[parseInt(this.dataset.legendIndex, 10)];
          if (!f) return;
          var c = f.getGeometry().getCoordinates();
          map.getView().animate({ center: c, zoom: Math.max(map.getView().getZoom(), 12), duration: 400 });
          setTimeout(function () {
            pop.el.innerHTML = popupHtml(f.get('_name'), f.get('_desc'), f.get('_url'));
            pop.el.style.display = 'block';
            pop.overlay.setPosition(c);
          }, 450);
        });
      });
    }
  }

  /* ═══════════════════════════════════════════
     MAPA DE RUTA (doble GPX)
  ═══════════════════════════════════════════ */
  function initRouteMap(container) {
    if (container._ent_init) return;
    container._ent_init = true;

    var uid        = container.id;
    var gpxUrl1    = container.dataset.gpxUrl      || '';
    var gpxUrl2    = container.dataset.gpxUrl2     || '';
    var color1     = container.dataset.routeColor  || '#001f5c';
    var color2     = container.dataset.routeColor2 || '#c0392b';
    var weight     = parseInt(container.dataset.routeWeight, 10) || 4;
    var startLabel = container.dataset.startLabel  || '';
    var endLabel   = container.dataset.endLabel    || '';
    var showElev   = container.dataset.showElevation === 'true';
    var gpxLabel1  = container.dataset.gpxLabel1   || 'Ruta planificada';
    var gpxLabel2  = container.dataset.gpxLabel2   || 'Ruta GPS';

    /* IDs de stats — render.php genera uid-stat-km1/km2 o uid-stat-km según si hay doble GPX */
    var elKm1  = document.getElementById(uid + '-stat-km1')  || document.getElementById(uid + '-stat-km');
    var elKm2  = document.getElementById(uid + '-stat-km2');
    var elElev = document.getElementById(uid + '-stat-elev');
    var elevWrap   = document.getElementById(uid + '-elev-wrap');
    var elevCanvas = document.getElementById(uid + '-canvas');

    var pop = makePopup(container);
    var map = makeMap(uid, pop.overlay);
    container._olMap = map;
    attachInteraction(map, pop);

    if (!gpxUrl1 && !gpxUrl2) return;

    var f1 = gpxUrl1 ? fetch(gpxUrl1,{cache:'force-cache'}).then(function(r){return r.text();}) : Promise.resolve(null);
    var f2 = gpxUrl2 ? fetch(gpxUrl2,{cache:'force-cache'}).then(function(r){return r.text();}) : Promise.resolve(null);

    Promise.all([f1, f2]).then(function (xmls) {
      var gpx1 = xmls[0] ? parseGPX(xmls[0]) : null;
      var gpx2 = xmls[1] ? parseGPX(xmls[1]) : null;

      var allFeat = [];

      /* ── Renderizar una ruta ─────────────────────────── */
      function renderRoute(gpx, color, isMain) {
        if (!gpx) return { routeLayer: null, pointLayer: null, stats: null };
        var rFeats = [], pFeats = [], allPts = [];

        gpx.trackSegments.forEach(function (seg) {
          if (seg.length < 2) return;
          allPts = allPts.concat(seg);
          var coords = subsample(seg, 1000).map(function(p){ return ol.proj.fromLonLat([p.lng,p.lat]); });
          var f = new ol.Feature({ geometry: new ol.geom.LineString(coords) });
          f.setStyle(new ol.style.Style({
            stroke: new ol.style.Stroke({ color: color, width: weight, lineCap: 'round', lineJoin: 'round' }),
          }));
          rFeats.push(f);
        });

        if (isMain && gpx.trackSegments.length) {
          var fs = gpx.trackSegments[0];
          var ls = gpx.trackSegments[gpx.trackSegments.length - 1];
          if (startLabel) {
            var sf = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([fs[0].lng,fs[0].lat])), _pt:true, _name:startLabel, _desc:'' });
            sf.setStyle(olLabelStyle(startLabel, '#0e0e0e')); pFeats.push(sf);
          }
          if (endLabel) {
            var ep = ls[ls.length-1];
            var ef = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([ep.lng,ep.lat])), _pt:true, _name:endLabel, _desc:'' });
            ef.setStyle(olLabelStyle(endLabel, '#001f5c')); pFeats.push(ef);
          }
        }

        gpx.waypoints.forEach(function (wp) {
          var f = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([wp.lng,wp.lat])), _pt:true, _name:wp.name, _desc:wp.desc });
          f.setStyle(olDiamondStyle()); pFeats.push(f);
        });

        var rl = new ol.layer.Vector({ source: new ol.source.Vector({ features: rFeats }) });
        var pl = new ol.layer.Vector({ source: new ol.source.Vector({ features: pFeats }) });
        map.addLayer(rl); map.addLayer(pl);
        allFeat = allFeat.concat(rFeats, pFeats);

        return { routeLayer: rl, pointLayer: pl, stats: allPts.length ? calcStats(allPts) : null };
      }

      var r1 = renderRoute(gpx1, color1, true);
      var r2 = renderRoute(gpx2, color2, false);

      /* Ajustar vista */
      if (allFeat.length) {
        map.getView().fit(
          new ol.source.Vector({ features: allFeat }).getExtent(),
          { size: map.getSize(), padding: [40,40,40,40], maxZoom: 14 }
        );
      }

      /* ── Estadísticas ────────────────────────────────── */

      /* Distancia ruta 1 */
      if (r1.stats && elKm1) {
        elKm1.textContent = r1.stats.km;
        elKm1.style.color = color1;
      }
      /* Distancia ruta 2 */
      if (r2.stats && elKm2) {
        elKm2.textContent = r2.stats.km;
        elKm2.style.color = color2;
        elKm2.style.display = '';
      }

      /* Desnivel (del primero que tenga elevación) */
      var elevStats  = null;
      var elevColor  = color1;
      if (r1.stats && r1.stats.eles.length > 1) { elevStats = r1.stats; elevColor = color1; }
      else if (r2.stats && r2.stats.eles.length > 1) { elevStats = r2.stats; elevColor = color2; }

      if (elevStats) {
        if (elElev && !elElev.textContent.trim()) elElev.textContent = elevStats.gain;
        if (showElev) drawElevation(uid + '-canvas', elevStats.eles, elevColor);
      } else if (showElev && elevWrap) {
        if (elevCanvas) elevCanvas.style.display = 'none';
        var msg = document.createElement('p');
        msg.style.cssText = 'text-align:center;font-size:12px;color:#888;padding:20px 0;margin:0;font-family:inherit;';
        msg.textContent   = 'No hay datos de elevaci\u00f3n en este archivo GPX';
        elevWrap.appendChild(msg);
      }

      /* ── Leyenda interactiva (solo con doble GPX) ────── */
      if (gpxUrl1 && gpxUrl2) {
        var routes = [
          { rLayer: r1.routeLayer, pLayer: r1.pointLayer, color: color1, label: gpxLabel1, visible: true },
          { rLayer: r2.routeLayer, pLayer: r2.pointLayer, color: color2, label: gpxLabel2, visible: true },
        ];

        var leg = document.createElement('div');
        leg.className  = 'ent-map-dual-legend';
        leg.style.cssText = 'position:absolute;bottom:32px;left:10px;z-index:10;background:rgba(255,255,255,.94);border:1px solid #e2e2de;padding:8px 10px;font-family:inherit;font-size:12px;box-shadow:0 2px 12px rgba(0,0,0,.12);user-select:none;';

        routes.forEach(function (route, idx) {
          var item = document.createElement('div');
          item.className  = 'ent-leg-item';
          item.style.cssText = 'display:flex;align-items:center;gap:8px;padding:4px 2px;cursor:pointer;border-radius:2px;transition:opacity .15s;' + (idx ? 'margin-top:4px;' : '');

          /* Línea de color */
          var swatch = document.createElement('div');
          swatch.style.cssText = 'width:24px;height:3px;background:'+route.color+';border-radius:2px;flex-shrink:0;';

          /* Etiqueta */
          var label = document.createElement('span');
          label.textContent = route.label;

          /* Botón ojo */
          var eye = document.createElement('button');
          eye.title = 'Mostrar/ocultar';
          eye.style.cssText = 'background:none;border:none;cursor:pointer;padding:0 2px;font-size:13px;line-height:1;color:#666;margin-left:auto;flex-shrink:0;';
          eye.textContent = '\uD83D\uDC41'; // 👁

          item.appendChild(swatch);
          item.appendChild(label);
          item.appendChild(eye);
          leg.appendChild(item);

          /* Hover: resaltar esta ruta, difuminar la otra.
             SOLO en dispositivos con hover real (no táctil):
             en móvil mouseenter se dispara al tocar pero
             mouseleave nunca llega → estado atascado. */
          var supportsHover = window.matchMedia('(hover: hover)').matches;
          if (supportsHover) {
            item.addEventListener('mouseenter', function () {
              routes.forEach(function (r, i) {
                if (r.rLayer) r.rLayer.setOpacity(i === idx ? 1 : 0.15);
              });
            });
            item.addEventListener('mouseleave', function () {
              routes.forEach(function (r) {
                if (r.rLayer) r.rLayer.setOpacity(r.visible ? 1 : 0);
              });
            });
          }

          /* Click en ojo: toggle visibilidad.
             Después de cambiar el estado, recalcula la opacidad
             de TODAS las rutas según su nuevo estado visible.
             Esto evita el bug donde una ruta re-activada queda
             invisible porque su opacidad era 0. */
          eye.addEventListener('click', function (e) {
            e.stopPropagation();
            route.visible = !route.visible;
            if (route.rLayer) route.rLayer.setVisible(route.visible);
            if (route.pLayer) route.pLayer.setVisible(route.visible);
            /* Recalcular opacidades de TODAS las rutas tras el cambio */
            routes.forEach(function (r) {
              if (r.rLayer) r.rLayer.setOpacity(r.visible ? 1 : 0);
            });
            eye.style.opacity  = route.visible ? '1' : '0.3';
            item.style.opacity = route.visible ? '1' : '0.5';
          });
        });

        container.appendChild(leg);
      }

    }).catch(function (err) {
      console.warn('Enterprise Moto: error cargando GPX:', err);
    });
  }


  /* ═══════════════════════════════════════════
     MAPA DE RUTA ANIMADO
     Sincroniza el perfil de elevación con un
     marcador en el mapa via mousemove.
  ═══════════════════════════════════════════ */
  function initAnimatedRouteMap(container) {
    if (container._ent_init) return;
    container._ent_init = true;

    var uid         = container.id;
    var gpxUrl      = container.dataset.gpxUrl      || '';
    var routeColor  = container.dataset.routeColor  || '#001f5c';
    var markerColor = container.dataset.markerColor || '#f2c118';
    var routeWeight = parseInt(container.dataset.routeWeight, 10) || 4;
    var startLabel  = container.dataset.startLabel  || '';
    var endLabel    = container.dataset.endLabel    || '';
    var showElev    = container.dataset.showElevation === 'true';
    var statKmEl    = document.getElementById(uid + '-stat-km');
    var statElEl    = document.getElementById(uid + '-stat-elev');
    var elevWrap    = document.getElementById(uid + '-elev-wrap');
    var elevCanvas  = document.getElementById(uid + '-canvas');
    var elevCursor  = document.getElementById(uid + '-elev-cursor');

    var pop = makePopup(container);
    var map = makeMap(uid, pop.overlay);
    container._olMap = map;
    attachInteraction(map, pop);

    if (!gpxUrl) return;

    fetch(gpxUrl, { cache: 'force-cache' })
      .then(function(r) { if (!r.ok) throw new Error(r.status); return r.text(); })
      .then(function(xml) {
        var gpx    = parseGPX(xml);
        var allPts = [];
        var rFeats = [];
        var pFeats = [];

        /* Trazar la ruta */
        gpx.trackSegments.forEach(function(seg) {
          if (seg.length < 2) return;
          allPts = allPts.concat(seg);
          var drawn  = subsample(seg, 1000);
          var coords = drawn.map(function(p){ return ol.proj.fromLonLat([p.lng,p.lat]); });
          var f = new ol.Feature({ geometry: new ol.geom.LineString(coords) });
          f.setStyle(new ol.style.Style({
            stroke: new ol.style.Stroke({ color:routeColor, width:routeWeight, lineCap:'round', lineJoin:'round' }),
          }));
          rFeats.push(f);
        });

        /* Etiquetas inicio/fin */
        if (gpx.trackSegments.length) {
          var fs = gpx.trackSegments[0];
          var ls = gpx.trackSegments[gpx.trackSegments.length-1];
          if (startLabel) {
            var sf = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([fs[0].lng,fs[0].lat])), _pt:true, _name:startLabel, _desc:'' });
            sf.setStyle(olLabelStyle(startLabel,'#0e0e0e')); pFeats.push(sf);
          }
          if (endLabel) {
            var ep = ls[ls.length-1];
            var ef = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([ep.lng,ep.lat])), _pt:true, _name:endLabel, _desc:'' });
            ef.setStyle(olLabelStyle(endLabel,'#001f5c')); pFeats.push(ef);
          }
        }

        /* Waypoints */
        gpx.waypoints.forEach(function(wp) {
          var f = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([wp.lng,wp.lat])), _pt:true, _name:wp.name, _desc:wp.desc });
          f.setStyle(olDiamondStyle()); pFeats.push(f);
        });

        map.addLayer(new ol.layer.Vector({ source: new ol.source.Vector({ features: rFeats }) }));
        map.addLayer(new ol.layer.Vector({ source: new ol.source.Vector({ features: pFeats }) }));

        /* Ajustar vista */
        if (rFeats.length) {
          var ext = new ol.source.Vector({ features: rFeats }).getExtent();
          map.getView().fit(ext, { size: map.getSize(), padding:[40,40,40,40], maxZoom:14 });
        }

        /* Estadísticas */
        if (allPts.length) {
          var stats = calcStats(allPts);
          if (statKmEl && !statKmEl.textContent.trim()) statKmEl.textContent = stats.km;
          if (statElEl && !statElEl.textContent.trim()) statElEl.textContent = stats.gain;

          /* ── Perfil de elevación interactivo ── */
          if (showElev && elevCanvas && stats.eles.length > 1) {
            drawElevation(uid + '-canvas', stats.eles, routeColor);

            /* Marcador animado sobre el mapa */
            var animMarkerSrc  = new ol.source.Vector();
            var animMarkerLayer = new ol.layer.Vector({ source: animMarkerSrc, zIndex: 100 });
            map.addLayer(animMarkerLayer);

            /* Estilo del marcador animado: círculo pulsante */
            function animMarkerStyle(color) {
              return new ol.style.Style({
                image: new ol.style.Circle({
                  radius: 8,
                  fill:   new ol.style.Fill({ color: color }),
                  stroke: new ol.style.Stroke({ color: '#ffffff', width: 2.5 }),
                }),
              });
            }

            /* Sincronización mousemove sobre el canvas de elevación */
            var LPAD = 52; /* debe coincidir con drawElevation */

            elevCanvas.addEventListener('mousemove', function(e) {
              var rect   = elevCanvas.getBoundingClientRect();
              var xRaw   = e.clientX - rect.left - LPAD;
              var chartW = rect.width - LPAD;
              if (xRaw < 0 || xRaw > chartW) {
                animMarkerSrc.clear();
                if (elevCursor) elevCursor.style.display = 'none';
                return;
              }

              /* Ratio 0-1 → índice en allPts */
              var ratio = Math.max(0, Math.min(1, xRaw / chartW));
              var idx   = Math.round(ratio * (allPts.length - 1));
              var pt    = allPts[idx];
              if (!pt) return;

              /* Mover marcador en el mapa */
              animMarkerSrc.clear();
              var coord = ol.proj.fromLonLat([pt.lng, pt.lat]);
              var marker = new ol.Feature({ geometry: new ol.geom.Point(coord) });
              marker.setStyle(animMarkerStyle(markerColor));
              animMarkerSrc.addFeature(marker);

              /* Línea vertical en el canvas */
              if (elevCursor) {
                elevCursor.style.display = 'block';
                elevCursor.style.left    = (LPAD + xRaw) + 'px';
              }
            }, { passive: true });

            /* Sincronizar color de la barra con el color de la ruta */
            if (elevCursor) elevCursor.style.background = routeColor;

            elevCanvas.addEventListener('mouseleave', function() {
              animMarkerSrc.clear();
              if (elevCursor) elevCursor.style.display = 'none';
            });

            /* Táctil: touchmove */
            elevCanvas.addEventListener('touchmove', function(e) {
              e.preventDefault();
              var touch  = e.touches[0];
              var rect   = elevCanvas.getBoundingClientRect();
              var xRaw   = touch.clientX - rect.left - LPAD;
              var chartW = rect.width - LPAD;
              var ratio  = Math.max(0, Math.min(1, xRaw / chartW));
              var idx    = Math.round(ratio * (allPts.length - 1));
              var pt     = allPts[idx];
              if (!pt) return;
              animMarkerSrc.clear();
              var coord = ol.proj.fromLonLat([pt.lng, pt.lat]);
              var marker = new ol.Feature({ geometry: new ol.geom.Point(coord) });
              marker.setStyle(animMarkerStyle(markerColor));
              animMarkerSrc.addFeature(marker);
              if (elevCursor) {
                elevCursor.style.display = 'block';
                elevCursor.style.left    = (LPAD + Math.max(0, xRaw)) + 'px';
              }
            }, { passive: false });

          } else if (showElev && elevWrap && stats.eles.length <= 1) {
            if (elevCanvas) elevCanvas.style.display = 'none';
            var msg = document.createElement('p');
            msg.style.cssText = 'text-align:center;font-size:12px;color:#888;padding:20px 0;margin:0;font-family:inherit;';
            msg.textContent   = 'No hay datos de elevación en este archivo GPX';
            elevWrap.appendChild(msg);
          }
        }
      })
      .catch(function(err) {
        console.warn('Enterprise Moto (animated route): error cargando GPX:', err);
      });
  }

  /* ═══════════════════════════════════════════
     MAPA COMPARATIVA DE RUTAS
     Copia fiel de initRouteMap con 3 cambios:
     1. Elevación forzada a GPX2 (GPX1 ignorada)
     2. Sincronización posición ↔ perfil (del animado)
     3. Etiquetas por defecto "Ruta planificada/realizada"
  ═══════════════════════════════════════════ */
  function initRouteComparisonMap(container) {
    if (container._ent_init) return;
    container._ent_init = true;

    var uid        = container.id;
    var gpxUrl1    = container.dataset.gpxUrl      || '';
    var gpxUrl2    = container.dataset.gpxUrl2     || '';
    var color1     = container.dataset.routeColor  || '#001f5c';
    var color2     = container.dataset.routeColor2 || '#c0392b';
    var weight     = parseInt(container.dataset.routeWeight, 10) || 4;
    var startLabel = container.dataset.startLabel  || '';
    var endLabel   = container.dataset.endLabel    || '';
    var showElev   = container.dataset.showElevation === 'true';
    var markerColor = container.dataset.markerColor || '#f2c118';
    /* CAMBIO 3: etiquetas por defecto */
    var gpxLabel1  = container.dataset.gpxLabel1   || 'GPX1 \u2014 Ruta planificada';
    var gpxLabel2  = container.dataset.gpxLabel2   || 'GPX2 \u2014 Ruta realizada';

    var elKm1  = document.getElementById(uid + '-stat-km1')  || document.getElementById(uid + '-stat-km');
    var elKm2  = document.getElementById(uid + '-stat-km2');
    var elElev = document.getElementById(uid + '-stat-elev');
    var elevWrap   = document.getElementById(uid + '-elev-wrap');
    var elevCanvas = document.getElementById(uid + '-canvas');
    var elevCursor = document.getElementById(uid + '-elev-cursor');

    var pop = makePopup(container);
    var map = makeMap(uid, pop.overlay);
    container._olMap = map;
    attachInteraction(map, pop);

    if (!gpxUrl1 && !gpxUrl2) return;

    var f1 = gpxUrl1 ? fetch(gpxUrl1,{cache:'force-cache'}).then(function(r){return r.text();}) : Promise.resolve(null);
    var f2 = gpxUrl2 ? fetch(gpxUrl2,{cache:'force-cache'}).then(function(r){return r.text();}) : Promise.resolve(null);

    Promise.all([f1, f2]).then(function (xmls) {
      var gpx1 = xmls[0] ? parseGPX(xmls[0]) : null;
      var gpx2 = xmls[1] ? parseGPX(xmls[1]) : null;

      var allFeat = [];

      function renderRoute(gpx, color, isMain) {
        if (!gpx) return { routeLayer: null, pointLayer: null, stats: null, allPts: [] };
        var rFeats = [], pFeats = [], allPts = [];

        gpx.trackSegments.forEach(function (seg) {
          if (seg.length < 2) return;
          allPts = allPts.concat(seg);
          var coords = subsample(seg, 1000).map(function(p){ return ol.proj.fromLonLat([p.lng,p.lat]); });
          var f = new ol.Feature({ geometry: new ol.geom.LineString(coords) });
          f.setStyle(new ol.style.Style({
            stroke: new ol.style.Stroke({ color: color, width: weight, lineCap: 'round', lineJoin: 'round' }),
          }));
          rFeats.push(f);
        });

        if (isMain && gpx.trackSegments.length) {
          var fs = gpx.trackSegments[0];
          var ls = gpx.trackSegments[gpx.trackSegments.length - 1];
          if (startLabel) {
            var sf = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([fs[0].lng,fs[0].lat])), _pt:true, _name:startLabel, _desc:'' });
            sf.setStyle(olLabelStyle(startLabel, '#0e0e0e')); pFeats.push(sf);
          }
          if (endLabel) {
            var ep = ls[ls.length-1];
            var ef = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([ep.lng,ep.lat])), _pt:true, _name:endLabel, _desc:'' });
            ef.setStyle(olLabelStyle(endLabel, '#001f5c')); pFeats.push(ef);
          }
        }

        gpx.waypoints.forEach(function (wp) {
          var f = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([wp.lng,wp.lat])), _pt:true, _name:wp.name, _desc:wp.desc });
          f.setStyle(olDiamondStyle()); pFeats.push(f);
        });

        var rl = new ol.layer.Vector({ source: new ol.source.Vector({ features: rFeats }) });
        var pl = new ol.layer.Vector({ source: new ol.source.Vector({ features: pFeats }) });
        map.addLayer(rl); map.addLayer(pl);
        allFeat = allFeat.concat(rFeats, pFeats);

        return { routeLayer: rl, pointLayer: pl, stats: allPts.length ? calcStats(allPts) : null, allPts: allPts };
      }

      var r1 = renderRoute(gpx1, color1, true);
      var r2 = renderRoute(gpx2, color2, false);

      if (allFeat.length) {
        map.getView().fit(
          new ol.source.Vector({ features: allFeat }).getExtent(),
          { size: map.getSize(), padding: [40,40,40,40], maxZoom: 14 }
        );
      }

      /* Distancia ruta 1 */
      if (r1.stats && elKm1) {
        elKm1.textContent = r1.stats.km;
        elKm1.style.color = color1;
      }
      /* Distancia ruta 2 */
      if (r2.stats && elKm2) {
        elKm2.textContent = r2.stats.km;
        elKm2.style.color = color2;
        elKm2.style.display = '';
      }

      /* CAMBIO 1: elevación forzada a GPX2, GPX1 ignorada aunque tenga altitudes */
      var elevStats = (r2.stats && r2.stats.eles.length > 1) ? r2.stats : null;
      var elevColor = color2;

      if (elevStats) {
        if (elElev && !elElev.textContent.trim()) elElev.textContent = elevStats.gain;
        if (showElev && elevCanvas) {
          drawElevation(uid + '-canvas', elevStats.eles, elevColor);

          /* CAMBIO 2: sincronización posición ↔ perfil (del bloque animado) */
          var pts2 = r2.allPts;
          var animMarkerSrc   = new ol.source.Vector();
          var animMarkerLayer = new ol.layer.Vector({ source: animMarkerSrc, zIndex: 100 });
          map.addLayer(animMarkerLayer);

          function animMarkerStyle(color) {
            return new ol.style.Style({
              image: new ol.style.Circle({
                radius: 8,
                fill:   new ol.style.Fill({ color: color }),
                stroke: new ol.style.Stroke({ color: '#ffffff', width: 2.5 }),
              }),
            });
          }

          var LPAD = 52;
          function syncFromX(xRaw) {
            var chartW = elevCanvas.getBoundingClientRect().width - LPAD;
            if (xRaw < 0 || xRaw > chartW) {
              animMarkerSrc.clear();
              if (elevCursor) elevCursor.style.display = 'none';
              return;
            }
            var ratio  = Math.max(0, Math.min(1, xRaw / chartW));
            var idx    = Math.round(ratio * (pts2.length - 1));
            var pt     = pts2[idx];
            if (!pt) return;
            animMarkerSrc.clear();
            var marker = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([pt.lng, pt.lat])) });
            marker.setStyle(animMarkerStyle(markerColor));
            animMarkerSrc.addFeature(marker);
            if (elevCursor) {
              elevCursor.style.display = 'block';
              elevCursor.style.left    = (LPAD + xRaw) + 'px';
            }
          }

          elevCanvas.addEventListener('mousemove', function(e) {
            var rect = elevCanvas.getBoundingClientRect();
            syncFromX(e.clientX - rect.left - LPAD);
          }, { passive: true });
          elevCanvas.addEventListener('mouseleave', function() {
            animMarkerSrc.clear();
            if (elevCursor) elevCursor.style.display = 'none';
          });
          elevCanvas.addEventListener('touchmove', function(e) {
            e.preventDefault();
            var rect = elevCanvas.getBoundingClientRect();
            syncFromX(e.touches[0].clientX - rect.left - LPAD);
          }, { passive: false });

          if (elevCursor) elevCursor.style.background = elevColor;
        }
      } else if (showElev && elevWrap) {
        if (elevCanvas) elevCanvas.style.display = 'none';
        var msg = document.createElement('p');
        msg.style.cssText = 'text-align:center;font-size:12px;color:#888;padding:20px 0;margin:0;font-family:inherit;';
        msg.textContent   = 'No hay datos de elevaci\u00f3n en GPX2 (ruta realizada)';
        elevWrap.appendChild(msg);
      }

      /* Leyenda interactiva — idéntica a route-map */
      if (gpxUrl1 && gpxUrl2) {
        var routes = [
          { rLayer: r1.routeLayer, pLayer: r1.pointLayer, color: color1, label: gpxLabel1, visible: true },
          { rLayer: r2.routeLayer, pLayer: r2.pointLayer, color: color2, label: gpxLabel2, visible: true },
        ];

        var leg = document.createElement('div');
        leg.className  = 'ent-map-dual-legend';
        leg.style.cssText = 'position:absolute;bottom:32px;left:10px;z-index:10;background:rgba(255,255,255,.94);border:1px solid #e2e2de;padding:8px 10px;font-family:inherit;font-size:12px;box-shadow:0 2px 12px rgba(0,0,0,.12);user-select:none;';

        routes.forEach(function (route, idx) {
          var item = document.createElement('div');
          item.className  = 'ent-leg-item';
          item.style.cssText = 'display:flex;align-items:center;gap:8px;padding:4px 2px;cursor:pointer;border-radius:2px;transition:opacity .15s;' + (idx ? 'margin-top:4px;' : '');

          var swatch = document.createElement('div');
          swatch.style.cssText = 'width:24px;height:3px;background:'+route.color+';border-radius:2px;flex-shrink:0;';

          var labelEl = document.createElement('span');
          labelEl.textContent = route.label;

          var eye = document.createElement('button');
          eye.title = 'Mostrar/ocultar';
          eye.style.cssText = 'background:none;border:none;cursor:pointer;padding:0 2px;font-size:13px;line-height:1;color:#666;margin-left:auto;flex-shrink:0;';
          eye.textContent = '\uD83D\uDC41';

          item.appendChild(swatch);
          item.appendChild(labelEl);
          item.appendChild(eye);
          leg.appendChild(item);

          var supportsHover = window.matchMedia('(hover: hover)').matches;
          if (supportsHover) {
            item.addEventListener('mouseenter', function () {
              routes.forEach(function (r, i) {
                if (r.rLayer) r.rLayer.setOpacity(i === idx ? 1 : 0.15);
              });
            });
            item.addEventListener('mouseleave', function () {
              routes.forEach(function (r) {
                if (r.rLayer) r.rLayer.setOpacity(r.visible ? 1 : 0);
              });
            });
          }

          eye.addEventListener('click', function (e) {
            e.stopPropagation();
            route.visible = !route.visible;
            if (route.rLayer) route.rLayer.setVisible(route.visible);
            if (route.pLayer) route.pLayer.setVisible(route.visible);
            routes.forEach(function (r) {
              if (r.rLayer) r.rLayer.setOpacity(r.visible ? 1 : 0);
            });
            eye.style.opacity  = route.visible ? '1' : '0.3';
            item.style.opacity = route.visible ? '1' : '0.5';
          });
        });

        container.appendChild(leg);
      }

    }).catch(function (err) {
      console.warn('Enterprise Moto (route-comparison): error cargando GPX:', err);
    });
  }

  /* ═══════════════════════════════════════════
     MAPA DE RUTAS POR LOCALIZACIÓN (#17)
     Aditivo. Copia fiel de initLocationMap con dos cambios:
       1. El enlace del popup usa m.url (URL de destino derivada del filtro
          compuesto en render.php), no m.postUrl.
       2. La etiqueta del enlace es «→ Entradas relacionadas».
     NO altera la rama "location" ni sus funciones.
  ═══════════════════════════════════════════ */
  function popupHtmlRbl(name, desc, url) {
    var h = '<div class="ent-map-popup">';
    if (name) h += '<div class="ent-map-popup__title">' + name + '</div>';
    if (desc) h += '<div class="ent-map-popup__desc">'  + desc + '</div>';
    if (url)  h += '<a class="ent-map-popup__link" href="'+url+'">→ Entradas relacionadas</a>';
    return h + '</div>';
  }

  function attachInteractionRbl(map, pop) {
    map.on('click', function (e) {
      var f = map.forEachFeatureAtPixel(e.pixel, function(x){return x;});
      if (f && f.get('_pt')) {
        pop.el.innerHTML = popupHtmlRbl(f.get('_name'), f.get('_desc'), f.get('_url') || '');
        pop.el.style.display = 'block';
        pop.overlay.setPosition(e.coordinate);
      } else {
        pop.el.style.display = 'none';
        pop.overlay.setPosition(undefined);
      }
    });
    map.on('pointermove', function (e) {
      map.getTargetElement().style.cursor = map.hasFeatureAtPixel(e.pixel) ? 'pointer' : '';
    });
  }

  function initRoutesByLocationMap(container) {
    if (container._ent_init) return;
    container._ent_init = true;

    var uid     = container.id;
    var zoom    = parseInt(container.dataset.zoom, 10) || 6;
    var markers = [];
    try { markers = JSON.parse(container.dataset.markers || '[]'); } catch(e) { return; }
    if (!markers.length) return;

    var pop = makePopup(container);
    var map = makeMap(uid, pop.overlay);
    container._olMap = map;
    attachInteractionRbl(map, pop);

    var feats = [];
    markers.forEach(function (m, i) {
      if (!m.lat || !m.lng) return;
      var f = new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.fromLonLat([m.lng, m.lat])),
        _pt: true, _name: m.name||'', _desc: m.description||'', _url: m.url||'',
      });
      f.setStyle(olPinStyle(i + 1));
      feats.push(f);
    });

    var src = new ol.source.Vector({ features: feats });
    map.addLayer(new ol.layer.Vector({ source: src }));

    if (feats.length === 1) {
      map.getView().setCenter(feats[0].getGeometry().getCoordinates());
      map.getView().setZoom(zoom);
    } else if (feats.length > 1) {
      map.getView().fit(src.getExtent(), { size: map.getSize(), padding: [60,60,60,60] });
    }

    var wrap = document.getElementById(uid + '-wrap');
    if (wrap) {
      wrap.querySelectorAll('[data-legend-index]').forEach(function (item) {
        item.addEventListener('click', function () {
          var f = feats[parseInt(this.dataset.legendIndex, 10)];
          if (!f) return;
          var c = f.getGeometry().getCoordinates();
          map.getView().animate({ center: c, zoom: Math.max(map.getView().getZoom(), 12), duration: 400 });
          setTimeout(function () {
            pop.el.innerHTML = popupHtmlRbl(f.get('_name'), f.get('_desc'), f.get('_url'));
            pop.el.style.display = 'block';
            pop.overlay.setPosition(c);
          }, 450);
        });
      });
    }
  }

  function initAll() {
    if (typeof ol === 'undefined') return;
    document.querySelectorAll('.ent-map[data-map-type="location"]').forEach(function (el) {
      if (!el._ent_init) initLocationMap(el);
    });
    document.querySelectorAll('.ent-map[data-map-type="route"]').forEach(function (el) {
      if (!el._ent_init) initRouteMap(el);
    });
    document.querySelectorAll('.ent-map[data-map-type="route-comparison"]').forEach(function (el) {
      if (!el._ent_init) initRouteComparisonMap(el);
    });
    document.querySelectorAll('.ent-map[data-map-type="animated-route"]').forEach(function (el) {
      if (!el._ent_init) initAnimatedRouteMap(el);
    });
    document.querySelectorAll('.ent-map[data-map-type="routes-by-location"]').forEach(function (el) {
      if (!el._ent_init) initRoutesByLocationMap(el);
    });
  }

  function tryInit() {
    if (typeof ol !== 'undefined') { initAll(); return; }
    setTimeout(tryInit, 100);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', tryInit);
  else tryInit();
  window.addEventListener('load', initAll);
  window.addEventListener('resize', function () {
    document.querySelectorAll('.ent-map[data-map-type]').forEach(function (el) {
      if (el._olMap) el._olMap.updateSize();
    });
  }, { passive: true });

})();
