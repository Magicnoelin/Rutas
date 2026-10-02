<?php
/**
 * /en/evento/ → English Events Finder (Cultural Events Search)
 * 
 * Redirects to the main evento index with lang=en parameter
 */
$_GET['lang'] = 'en';
require_once dirname(__DIR__, 2) . '/evento/index.php';
