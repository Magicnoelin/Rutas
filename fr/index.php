<?php
/**
 * /fr/index.php — French Hub (delegates to main index.php with lang=fr)
 * This file exists so Google can crawl /fr/ as a real URL with its own canonical.
 */
$_GET['lang'] = 'fr';
require_once dirname(__DIR__) . '/index.php';
