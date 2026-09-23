/**
 * lugares-view-switcher.js — Selector Lista/Mapa/Split para /lugares/{slug}
 * Requiere: window.LLVS_CONFIG = { slug, mode, pathPrefix, catIcon, catColor }
 * v2: MarkerCluster + popups estilo bodegas + datos de auxiliar_poi
 */
(function () {
  'use strict';
  var cfg = window.LLVS_CONFIG || {};
  var SLUG = cfg.slug || '', MODE = cfg.mode || '';
  var PATH_PREFIX = cfg.pathPrefix || '', CAT_ICON = cfg.catIcon || '📍';
  var CAT_COLOR = cfg.catColor || '#2d6a4f';

  var leafletLoaded = false, clusterLoaded = false;
  var mapMain = null, mapSplit = null;
  var clusterMain = null, clusterSplit = null;
  var geoData = null, markersMain = {}, markersSplit = {};
  var currentView = 'list';

  var btnList  = document.querySelector('[data-view="list"]');
  var btnMap   = document.querySelector('[data-view="map"]');
  var btnSplit = document.querySelector('[data-view="split"]');
  var listGrid  = document.getElementById('ll-list-grid');
  var mapWrap   = document.getElementById('ll-map-wrap');
  var splitWrap = document.getElementById('ll-split-wrap');
  var splitList = document.getElementById('ll-split-list');
  var pinCount  = document.getElementById('ll-map-pin-count');
  var mapLoading = document.getElementById('ll-map-loading');
  if (!btnMap || !listGrid) return;

  try {
    var s = localStorage.getItem('ll-view');
    if (s && ['list','map','split'].indexOf(s) > -1) {
      currentView = (s === 'split' && window.innerWidth < 900) ? 'map' : s;
    }
  } catch(e) {}

  function loadLeaflet(cb) {
    if (leafletLoaded && clusterLoaded) { cb(); return; }
    // CSS Leaflet + MarkerCluster
    ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
     'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css',
     'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css'
    ].forEach(function(href){
      var l = document.createElement('link'); l.rel='stylesheet'; l.href=href;
      document.head.appendChild(l);
    });
    // JS Leaflet → luego MarkerCluster → callback
    var scL = document.createElement('script');
    scL.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    scL.onload = function() {
      leafletLoaded = true;
      var scMC = document.createElement('script');
      scMC.src = 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js';
      scMC.onload = function() { clusterLoaded = true; cb(); };
      scMC.onerror = function() { clusterLoaded = true; cb(); };
      document.head.appendChild(scMC);
    };
    scL.onerror = function(){ if (mapLoading) mapLoading.innerHTML = '<span>⚠️ No se pudo cargar el mapa.</span>'; };
    document.head.appendChild(scL);
  }

  function fetchGeoData(cb) {
    if (geoData) { cb(geoData); return; }
    fetch('/api/get_lugares_geojson.php?slug=' + encodeURIComponent(SLUG) + '&mode=' + encodeURIComponent(MODE))
      .then(function(r){ return r.json(); })
      .then(function(d){ geoData = d; cb(d); })
      .catch(function(){ if (mapLoading) mapLoading.innerHTML = '<span>ℹ️ Sin coordenadas para esta categoría.</span>'; });
  }

  // ── Popup enriquecido estilo bodegas ──────────────────────────────────────
  function buildPopup(p) {
    var fallback = 'https://images.unsplash.com/photo-1506377247377-2a5b3b417ebb?auto=format&fit=crop&w=300&q=80';
    var imgSrc = p.foto || fallback;
    var html = '<div class="ll-popup">'
      + '<img class="ll-popup__img" src="' + imgSrc + '" alt="'
      + (p.nombre||'').replace(/"/g,'&quot;')
      + '" loading="lazy" onerror="this.src=\'' + fallback + '\'">'
      + '<div class="ll-popup__body">'
      + '<p class="ll-popup__name">' + (p.nombre||'') + '</p>';
    if (p.categoria) html += '<p class="ll-popup__cat">🏷️ ' + p.categoria + '</p>';
    var loc = [p.municipio, p.provincia].filter(Boolean).join(', ');
    if (loc)        html += '<p class="ll-popup__loc">📍 ' + loc + '</p>';
    if (p.telefono) html += '<p class="ll-popup__tel">📞 ' + p.telefono + '</p>';
    if (p.source === 'aux') {
      if (p.web) html += '<a href="' + p.web + '" target="_blank" rel="noopener" class="ll-popup__btn">Visitar Sitio Web</a>';
      html += '<a href="/agregar-lugar-interes.html" class="ll-popup__claim">¿Eres el propietario? Reclama esta ficha</a>';
    } else {
      html += '<a href="' + PATH_PREFIX + '/lugar/' + p.slug + '" class="ll-popup__btn">Ver ficha →</a>';
      if (p.web) html += '<a href="' + p.web + '" target="_blank" rel="noopener" class="ll-popup__web">🌐 Sitio web</a>';
    }
    html += '</div></div>';
    return html;
  }

  // ── Icono circular con emoji y color de categoría ─────────────────────────
  function buildIcon(hi) {
    var bg = hi ? '#e65c00' : CAT_COLOR;
    return L.divIcon({
      className: '',
      html: '<div style="background:' + bg + ';width:32px;height:32px;border-radius:50%;'
        + 'border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35);'
        + 'display:flex;align-items:center;justify-content:center;'
        + 'color:#fff;font-size:16px">' + CAT_ICON + '</div>',
      iconSize: [32,32], iconAnchor: [16,16], popupAnchor: [0,-18]
    });
  }

  function renderMarkers(mi, ms, data, clusterGroup) {
    if (!data.features || !data.features.length) {
      if (mapLoading) mapLoading.innerHTML = '<span>ℹ️ No hay lugares con coordenadas todavía.</span>';
      return;
    }
    if (mapLoading) mapLoading.style.display = 'none';
    var bds = [];
    data.features.forEach(function(f){
      var p=f.properties, lat=f.geometry.coordinates[1], lng=f.geometry.coordinates[0];
      var m = L.marker([lat,lng],{icon:buildIcon(false)}).bindPopup(buildPopup(p),{maxWidth:280});
      ms[p.slug]=m; bds.push([lat,lng]);
      if (clusterGroup) {
        clusterGroup.addLayer(m);
      } else {
        m.addTo(mi);
      }
    });
    if (clusterGroup) mi.addLayer(clusterGroup);
    if (pinCount) pinCount.textContent = data.features.length+' en el mapa';
    if (bds.length) { try{ mi.fitBounds(bds,{padding:[30,30],maxZoom:12}); }catch(e){} }
  }

  function bindHover(ms, mi, container) {
    var items = container ? container.querySelectorAll('[data-slug]') : [];
    items.forEach(function(li){
      var slug=li.dataset.slug;
      li.addEventListener('mouseenter',function(){
        var m=ms[slug];
        if(m){m.setIcon(buildIcon(true));mi.panTo(m.getLatLng(),{animate:true,duration:0.4});}
        var c=li.querySelector('.lnd-card'); if(c)c.classList.add('ll-card--highlighted');
      });
      li.addEventListener('mouseleave',function(){
        var m=ms[slug]; if(m)m.setIcon(buildIcon(false));
        var c=li.querySelector('.lnd-card'); if(c)c.classList.remove('ll-card--highlighted');
      });
    });
    Object.keys(ms).forEach(function(slug){
      ms[slug].on('click',function(){
        var li=splitList?splitList.querySelector('[data-slug="'+slug+'"]'):null;
        if(li)li.scrollIntoView({behavior:'smooth',block:'nearest'});
      });
    });
  }

  function initMapMain() {
    if (mapMain){mapMain.invalidateSize();return;}
    mapMain = L.map('ll-map',{scrollWheelZoom:false}).setView([40.4167,-3.7037],6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
      maxZoom:19,attribution:'© <a href="https://openstreetmap.org">OSM</a> | rutasrurales.io'
    }).addTo(mapMain);
    // Crear grupo de clustering si está disponible
    clusterMain = (typeof L.markerClusterGroup === 'function')
      ? L.markerClusterGroup({maxClusterRadius:60, showCoverageOnHover:false})
      : null;
    fetchGeoData(function(d){
      renderMarkers(mapMain, markersMain, d, clusterMain);
      bindHover(markersMain, mapMain, listGrid);
    });
  }

  function initMapSplit() {
    if (!splitList.children.length) {
      listGrid.querySelectorAll('li').forEach(function(li){
        splitList.appendChild(li.cloneNode(true));
      });
    }
    if (mapSplit){mapSplit.invalidateSize();return;}
    mapSplit = L.map('ll-map-split',{scrollWheelZoom:true}).setView([40.4167,-3.7037],6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
      maxZoom:19,attribution:'© <a href="https://openstreetmap.org">OSM</a> | rutasrurales.io'
    }).addTo(mapSplit);
    clusterSplit = (typeof L.markerClusterGroup === 'function')
      ? L.markerClusterGroup({maxClusterRadius:60, showCoverageOnHover:false})
      : null;
    fetchGeoData(function(d){
      renderMarkers(mapSplit, markersSplit, d, clusterSplit);
      bindHover(markersSplit, mapSplit, splitList);
    });
  }

  function switchView(view) {
    currentView = view;
    try{localStorage.setItem('ll-view',view);}catch(e){}
    [btnList,btnMap,btnSplit].forEach(function(b){
      if(!b)return;
      var a=b.dataset.view===view;
      b.classList.toggle('ll-active',a);
      b.setAttribute('aria-pressed',a?'true':'false');
    });
    try{
      if(view==='list') history.replaceState(null,'',location.pathname+location.search);
      else history.replaceState(null,'',location.pathname+location.search+'#'+view);
    }catch(e){}

    if(view==='list'){
      listGrid.style.display='';
      mapWrap.style.display='none'; mapWrap.setAttribute('aria-hidden','true');
      splitWrap.style.display='none'; splitWrap.setAttribute('aria-hidden','true');
      if(pinCount)pinCount.textContent='';
    } else if(view==='map'){
      listGrid.style.display='none';
      mapWrap.style.display='block'; mapWrap.setAttribute('aria-hidden','false');
      splitWrap.style.display='none'; splitWrap.setAttribute('aria-hidden','true');
      loadLeaflet(function(){
        initMapMain();
        setTimeout(function(){if(mapMain)mapMain.invalidateSize();},200);
      });
    } else if(view==='split'){
      if(window.innerWidth<900){switchView('map');return;}
      listGrid.style.display='none';
      mapWrap.style.display='none'; mapWrap.setAttribute('aria-hidden','true');
      splitWrap.style.display='block'; splitWrap.setAttribute('aria-hidden','false');
      loadLeaflet(function(){
        initMapSplit();
        setTimeout(function(){if(mapSplit)mapSplit.invalidateSize();},200);
      });
    }
  }

  [btnList,btnMap,btnSplit].forEach(function(b){
    if(!b)return;
    b.addEventListener('click',function(){switchView(b.dataset.view);});
    b.addEventListener('keydown',function(e){
      if(e.key==='Enter'||e.key===' '){e.preventDefault();switchView(b.dataset.view);}
    });
  });

  // Aplicar vista inicial desde hash o localStorage
  var hash=location.hash.replace('#','');
  if(hash==='mapa'||hash==='map') currentView='map';
  else if(hash==='split') currentView=(window.innerWidth>=900)?'split':'map';
  if(currentView!=='list') switchView(currentView);

})();

