<?php
/**
 * /zh/index.php — Chinese Hub (delegates to main index.php with lang=zh)
 * This file exists so Google can crawl /zh/ as a real URL with its own canonical.
 */
$_GET['lang'] = 'zh';
require_once dirname(__DIR__) . '/index.php';
