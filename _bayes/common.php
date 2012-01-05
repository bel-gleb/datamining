<?php
mb_internal_encoding('utf-8');
define("PROJECT_ROOT", __DIR__);

function __autoload($className) {
	require_once __DIR__.'/classes/'.implode('/', explode('_', $className)) . '.class.php';
}