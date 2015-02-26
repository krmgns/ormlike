<?php
// Simple dump
function pre($input, $exit = false){
    printf("%s\n", print_r($input, 1));
    if ($exit) {
        exit;
    }
}
function prd($input, $exit = false){
    var_dump($input);
    if ($exit) {
        exit;
    }
}
function prr() {
    $args = func_get_args();
    foreach ($args as $arg) {
        pre($arg);
    }
}

define('ORMLIKE_DATABASE_USER', 'test');
define('ORMLIKE_DATABASE_PASS', '********');
define('ORMLIKE_DATABASE_HOST', 'localhost');
define('ORMLIKE_DATABASE_NAME', 'test');
define('ORMLIKE_DATABASE_CHARSET', 'gbk');
define('ORMLIKE_DATABASE_TIMEZONE', '+00:00');

require(__dir__.'/../ORMLikeException.php');
require(__dir__.'/../ORMLikeHelper.php');
require(__dir__.'/../ORMLikeDatabaseAbstract.php');
require(__dir__.'/../ORMLikeDatabase.php');
require(__dir__.'/../ORMLikeSql.php');
require(__dir__.'/../ORMLikeEntity.php');
require(__dir__.'/../ORMLike.php');
require(__dir__.'/../ORMLikeQuery.php');
