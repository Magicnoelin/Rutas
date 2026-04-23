<?php
/**
 * EVENTO MODULAR - Delegación al archivo principal
 * 
 * Este archivo delega toda la lógica a /evento-detalle.php
 * para evitar duplicación de código y mantener un único punto de verdad.
 * 
 * URL de prueba: /evento-modular/{slug}
 * URL de producción: /evento/{slug}
 */

// Pasar los parámetros al archivo principal
// (ya los tiene disponibles via $_GET)
require_once dirname(__DIR__) . '/evento-detalle.php';
