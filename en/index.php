<?php
/**
 * /en/index.php — English Hub (delegates to main index.php with lang=en)
 * This file exists so Google can crawl /en/ as a real URL with its own canonical.
 */
$_GET['lang'] = 'en';
require_once dirname(__DIR__) . '/index.php';
