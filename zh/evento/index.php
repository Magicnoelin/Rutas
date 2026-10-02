<?php
/**
 * /zh/evento/ → Chinese Events Finder (文化活动搜索)
 * 
 * Redirects to the main evento index with lang=zh parameter
 */
$_GET['lang'] = 'zh';
require_once dirname(__DIR__, 2) . '/evento/index.php';
