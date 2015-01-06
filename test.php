<?php
header('Content-Type: text/plain');

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

/******************************************/

define('ORMLIKE_DATABASE_USER', 'root');
define('ORMLIKE_DATABASE_PASS', '11111111');
define('ORMLIKE_DATABASE_HOST', 'localhost');
define('ORMLIKE_DATABASE_NAME', 'test');
define('ORMLIKE_DATABASE_CHARSET', 'utf8');
define('ORMLIKE_DATABASE_TIMEZONE', '+00:00');

require(__dir__.'/ORMLike/ORMLikeException.php');
require(__dir__.'/ORMLike/ORMLikeHelper.php');
require(__dir__.'/ORMLike/ORMLikeDatabaseAbstract.php');
require(__dir__.'/ORMLike/ORMLikeDatabase.php');
require(__dir__.'/ORMLike/ORMLikeSql.php');
require(__dir__.'/ORMLike/ORMLikeEntity.php');
require(__dir__.'/ORMLike/ORMLike.php');
require(__dir__.'/ORMLike/ORMLikeQuery.php');

$query = new ORMLikeQuery('users u');
$query->select('id,name');
// $query->select('id,name')->where('id=?', 1);
// $query->select('id,name')->where('id=?', 1)->whereLike('AND name=?', 'Kerem');
// $query->select('id,name')->whereLike('id=? AND name=?', ['1%', '%Ke_rem%']);
// $query->select('id,name')->where('id=?', 1)->whereLike('(id LIKE ? OR name LIKE ?)', ['2%', '%Ke_rem%'], 'OR');

// $query->select('id,name')
//     ->where('id=?', 1)
//     ->where('(name=? OR name=? OR old BETWEEN %d AND %d)', ['Kerem', 'Murat', 30, 40], $query::OP_AND)
// ;

// $query->select('u.*, up.point')
//     ->aggregate('sum', 'up.point', 'sum_point')
//     ->joinLeft('users_point up', 'up.user_id=u.id')
//     ->groupBy('u.id')
//     ->orderBy('old')
//     ->having('sum_point > 30')
//     ->limit(0,10)
// ;

pre($query->toString());

pre($query->execute());
// pre($query->get());
// pre($query->getAll());

// pre($query->execute(function($result) {
//     $data = array();
//     while ($row = mysqli_fetch_object($result)) {
//         $data[] = $row;
//     }
//     return $data;
// }));

// pre($query->getAll(function($result) {
//     $result = array_filter($result, function($row) {
//         return ($row->id > 1);
//     });
//     return $result;
// }));

pre('...');
pre($query);
