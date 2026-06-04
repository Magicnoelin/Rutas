<?php
/**
 * /de/index.php — German Hub (delegates to main index.php with lang=de)
 * This file exists so Google can crawl /de/ as a real URL with its own canonical.
 */
$_GET['lang'] = 'de';
require_once dirname(__DIR__) . '/index.php';
