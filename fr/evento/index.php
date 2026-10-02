<?php
/**
 * /fr/evento/ → French Events Finder (Recherche d'événements culturels)
 * 
 * Redirects to the main evento index with lang=fr parameter
 */
$_GET['lang'] = 'fr';
require_once dirname(__DIR__, 2) . '/evento/index.php';
