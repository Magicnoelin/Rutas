<?php
/**
 * MÓDULO: MAPA DE RUTA CON TRAZADO OSRM
 * Dibuja la polyline por carretera y marcadores por tipo de POI.
 * Llamar desde index.php: renderMapaRuta($ruta, $waypointsJson)
 * Leaflet.js debe estar cargado en el <head>.
 */

function renderMapaRuta(array $ruta, array $waypointsJson = []): void
{
if (empty($ruta) || (empty($ruta['polyline_osrm']) && empty($waypointsJson))) {
    return;
}

$polyline_enc  = addslashes($ruta['polyline_osrm'] ?? '');
$titulo_ruta   = htmlspecialchars($ruta['name']     ?? 'Ruta', ENT_QUOTES, 'UTF-8');
$provincia_h   = htmlspecialchars($ruta['province'] ?? '',     ENT_QUOTES, 'UTF-8');
$distancia_km  = $ruta['distancia_total_km'] ?? null;
$duracion_min  = intval($ruta['duracion_min'] ?? 0);
$total_paradas = intval($ruta['total_paradas'] ?? count($waypointsJson));

$duracion_txt = '';
if ($duracion_min > 0) {
    $h = floor($duracion_min / 60);
    $m = $duracion_min % 60;
    $duracion_txt = $h > 0 ? "{$h}h {$m}min" : "{$m} min";
}

$wps_js = json_encode(
    array_values(array_filter(
        array_map(function ($item) {
            $lat = floatval($item['latitude']  ?? $item['lat']  ?? 0);
            $lng = floatval($item['longitude'] ?? $item['lng']  ?? 0);
            if ($lat === 0.0 && $lng === 0.0) return null;
            return [
                'lat'          => $lat,
                'lng'          => $lng,
                'nombre'       => htmlspecialchars($item['title']     ?? $item['nombre']    ?? '', ENT_QUOTES, 'UTF-8'),
                'municipio'    => htmlspecialchars($item['address']   ?? $item['municipio'] ?? '', ENT_QUOTES, 'UTF-8'),
                'tipo'         => $item['item_type'] ?? $item['poi_tipo'] ?? 'stop',
                'orden'        => intval($item['display_order'] ?? $item['item_order'] ?? 0),
                'dia'          => intval($item['day_number'] ?? 1),
                'descripcion'  => htmlspecialchars(substr(strip_tags(
                    $item['editorial_note'] ?? $item['descripcion'] ?? $item['description'] ?? ''
                ), 0, 180), ENT_QUOTES, 'UTF-8'),
                'es_highlight' => (bool)($item['is_highlight'] ?? false),
            ];
        }, $waypointsJson ?? [])
    )),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
?>

<!-- ── SECCIÓN MAPA DE RUTA ─────────────────────────────────── -->
<section class="rt-mapa-ruta rt-section" id="mapa-ruta"
         aria-label="Mapa del recorrido de la ruta">
    <div class="rt-container">

        <div class="rt-section__header">
            <h2 class="rt-section__title">
                <span class="rt-section__icon" aria-hidden="true">🗺️</span>
                Recorrido por carretera
            </h2>
            <?php if ($provincia_h): ?>
            <p class="rt-section__subtitle">Trazado optimizado por OSRM en <?= $provincia_h ?></p>
            <?php endif; ?>

            <?php if ($distancia_km || $duracion_txt || $total_paradas): ?>
            <div class="rt-mapa-ruta__stats" role="list" aria-label="Estadísticas del recorrido">
                <?php if ($distancia_km): ?>
                <span class="rt-mapa-stat" role="listitem">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                    <?= number_format(floatval($distancia_km), 1, ',', '.') ?>&nbsp;km
                </span>
                <?php endif; ?>
                <?php if ($duracion_txt): ?>
                <span class="rt-mapa-stat rt-mapa-stat--brown" role="listitem">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?= htmlspecialchars($duracion_txt) ?>&nbsp;en coche
                </span>
                <?php endif; ?>
                <?php if ($total_paradas > 0): ?>
                <span class="rt-mapa-stat rt-mapa-stat--purple" role="listitem">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?= $total_paradas ?>&nbsp;parada<?= $total_paradas !== 1 ? 's' : '' ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="rt-mapa-ruta__layout">

            <div class="rt-mapa-ruta__map-wrap">
                <div id="rt-mapa-leaflet"
                     class="rt-mapa-ruta__map"
                     role="application"
                     aria-label="Mapa interactivo del recorrido de <?= $titulo_ruta ?>">
                </div>
                <p class="rt-mapa-ruta__attrib">
                    Cartografía &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors
                    &middot; Rutas por <a href="https://project-osrm.org" target="_blank" rel="noopener noreferrer">OSRM</a>
                </p>
            </div>

            <aside class="rt-mapa-ruta__leyenda" aria-label="Lista de paradas de la ruta">
                <h3 class="rt-mapa-ruta__leyenda-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Paradas
                </h3>
                <ol class="rt-mapa-ruta__paradas" id="rt-lista-paradas"></ol>
            </aside>

        </div>
    </div>
</section>
<script>
(function () {
'use strict';
var POLYLINE_ENC = '<?= $polyline_enc ?>';
var WAYPOINTS    = <?= $wps_js ?>;
var TIPO = {
    accommodation: { color: '#B8956A', emoji: '🏡', label: 'Alojamiento' },
    place:         { color: '#2F5233', emoji: '🏛️',  label: 'Lugar'       },
    activity:      { color: '#1565C0', emoji: '🥾',  label: 'Actividad'   },
    event:         { color: '#6A1B9A', emoji: '🎭',  label: 'Evento'      },
    restaurant:    { color: '#BF360C', emoji: '🍽️',  label: 'Restaurante' },
    stop:          { color: '#5A6B5A', emoji: '📍',  label: 'Parada'      },
};
function cfg(t) { return TIPO[t] || TIPO.stop; }
function decodePoly(enc) {
    if (!enc) return [];
    var c=[], i=0, a=0, n=0;
    while (i < enc.length) {
        var s=0,r=0,b;
        do{b=enc.charCodeAt(i++)-63;r|=(b&0x1f)<<s;s+=5;}while(b>=0x20);
        a+=(r&1)?~(r>>1):(r>>1); s=0;r=0;
        do{b=enc.charCodeAt(i++)-63;r|=(b&0x1f)<<s;s+=5;}while(b>=0x20);
        n+=(r&1)?~(r>>1):(r>>1);
        c.push([a/1e5,n/1e5]);
    }
    return c;
}
function mkIcon(tipo, orden, hl) {
    var c=cfg(tipo), sz=hl?42:36;
    return L.divIcon({
        html:'<div style="position:relative;width:'+sz+'px;height:'+sz+'px;">'+
             '<div style="width:'+sz+'px;height:'+sz+'px;background:'+c.color+';border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;border:2px solid rgba(255,255,255,.85);box-shadow:0 3px 10px rgba(0,0,0,.25);">'+
             '<span style="transform:rotate(45deg);font-size:'+(hl?18:15)+'px;line-height:1;">'+c.emoji+'</span></div>'+
             '<div style="position:absolute;top:-5px;right:-5px;background:#fff;color:'+c.color+';border:2px solid '+c.color+';border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;">'+orden+'</div></div>',
        iconSize:[sz,sz],iconAnchor:[sz/2,sz],popupAnchor:[0,-(sz+4)],className:'rt-leaflet-icon',
    });
}
function initMapa() {
    var el=document.getElementById('rt-mapa-leaflet');
    if (!el||typeof L==='undefined') return;
    var wps=WAYPOINTS.filter(function(w){ return w.lat&&w.lng&&parseFloat(w.lat)!==0; });
    if (!wps.length) { el.innerHTML='<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#888;padding:20px;">Sin coordenadas para el mapa.</div>'; return; }
    var sl=0,sn=0;
    wps.forEach(function(w){ sl+=parseFloat(w.lat); sn+=parseFloat(w.lng); });
    var map=L.map('rt-mapa-leaflet',{center:[sl/wps.length,sn/wps.length],zoom:10,scrollWheelZoom:false});
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
        attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',maxZoom:18,
    }).addTo(map);
    if (POLYLINE_ENC) {
        var pts=decodePoly(POLYLINE_ENC);
        if (pts.length>1) {
            var pl=L.polyline(pts,{color:'#2F5233',weight:5,opacity:.85,lineJoin:'round',lineCap:'round'}).addTo(map);
            map.fitBounds(pl.getBounds(),{padding:[32,32]});
        }
    } else {
        map.fitBounds(L.latLngBounds(wps.map(function(w){return[w.lat,w.lng];})),{padding:[40,40]});
    }
    var lista=document.getElementById('rt-lista-paradas');
    wps.forEach(function(wp,idx){
        var orden=wp.orden||(idx+1),c=cfg(wp.tipo);
        var lat=parseFloat(wp.lat),lng=parseFloat(wp.lng);
        var popup='<div style="min-width:190px;max-width:255px;font-family:inherit;">'+
            '<div style="background:'+c.color+';color:#fff;padding:6px 12px;margin:-14px -20px 10px;border-radius:8px 8px 0 0;font-size:.7rem;font-weight:700;text-transform:uppercase;">'+
            c.emoji+' Parada '+orden+' &middot; D\u00eda '+wp.dia+'</div>'+
            '<strong style="font-size:.92rem;color:#1A2E1A;display:block;margin-bottom:3px;">'+wp.nombre+'</strong>'+
            (wp.municipio?'<span style="font-size:.77rem;color:#5A6B5A;">\ud83d\udccd '+wp.municipio+'</span>':'')+
            (wp.descripcion?'<p style="font-size:.79rem;color:#444;margin:7px 0 0;line-height:1.5;">'+wp.descripcion+'</p>':'')+
            '<div style="margin-top:8px;padding-top:6px;border-top:1px solid #eee;font-size:.7rem;color:'+c.color+';font-weight:700;">'+c.label+'</div></div>';
        var marker=L.marker([lat,lng],{icon:mkIcon(wp.tipo,orden,wp.es_highlight)})
            .addTo(map).bindPopup(popup,{maxWidth:275,className:'rt-leaflet-popup'});
        if (lista) {
            var li=document.createElement('li');
            li.className='rt-mapa-ruta__parada-item';
            li.setAttribute('role','button');li.setAttribute('tabindex','0');
            li.setAttribute('aria-label','Ver en el mapa: '+wp.nombre);
            li.innerHTML='<div class="rt-mapa-parada__num" style="background:'+c.color+';">'+orden+'</div>'+
                '<div class="rt-mapa-parada__info"><div class="rt-mapa-parada__nombre">'+wp.nombre+'</div>'+
                '<div class="rt-mapa-parada__meta">'+c.emoji+' '+c.label+' &middot; D\u00eda '+wp.dia+'</div></div>';
            var fly=(function(m,la,ln){return function(){
                map.flyTo([la,ln],13,{animate:true,duration:.8});
                setTimeout(function(){m.openPopup();},850);
            };})(marker,lat,lng);
            li.addEventListener('click',fly);
            li.addEventListener('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();fly();}});
            lista.appendChild(li);
        }
    });
    setTimeout(function(){map.invalidateSize();},400);
    window.addEventListener('resize',function(){map.invalidateSize();});
}
typeof L!=='undefined'
    ?(document.readyState==='loading'?document.addEventListener('DOMContentLoaded',initMapa):initMapa())
    :window.addEventListener('load',initMapa);
})();
</script>
<?php
} // end function renderMapaRuta()
