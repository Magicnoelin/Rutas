<?php
/**
 * /de/evento/ → German Events Finder (Kulturelle Veranstaltungen suche)
 * 
 * Redirects to the main evento index with lang=de parameter
 */
$_GET['lang'] = 'de';
require_once dirname(__DIR__, 2) . '/evento/index.php';
