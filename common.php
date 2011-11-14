<?php
function __autoload($className) {
    require_once './classes/'.implode('/', explode('_', $className)) . '.class.php';
}

mb_internal_encoding('utf-8');

define("PROJECT_ROOT", '/home/psi/workspace/datamining');