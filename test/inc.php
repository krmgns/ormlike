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

define('ORMLIKE_DATABASE_USER', 'root');
define('ORMLIKE_DATABASE_PASS', '11111111');
define('ORMLIKE_DATABASE_HOST', 'localhost');
define('ORMLIKE_DATABASE_NAME', 'test');
define('ORMLIKE_DATABASE_CHARSET', 'utf8');
define('ORMLIKE_DATABASE_TIMEZONE', '+00:00');

require(__dir__.'/../ORMLike/ORMLikeException.php');
require(__dir__.'/../ORMLike/ORMLikeHelper.php');
require(__dir__.'/../ORMLike/ORMLikeDatabaseAbstract.php');
require(__dir__.'/../ORMLike/ORMLikeDatabase.php');
require(__dir__.'/../ORMLike/ORMLikeSql.php');
require(__dir__.'/../ORMLike/ORMLikeEntity.php');
require(__dir__.'/../ORMLike/ORMLike.php');
require(__dir__.'/../ORMLike/ORMLikeQuery.php');