<?php
/**
 * /en/alojamientos/ → English Accommodation Hub
 * Redirects to main hub with lang detection
 */
$_GET['lang'] = 'en';
require_once dirname(__DIR__, 2) . '/alojamientos/index.php';
