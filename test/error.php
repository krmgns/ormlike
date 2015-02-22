<?php
include('inc.php');

$autoload = require('./../ORMLike/Autoload.php');
$autoload->register();

use \ORMLike\Database as Database;
use \ORMLike\Configuration as Configuration;

// $cfg = [
//     'agent' => 'mysqli',
//     'database' => [
//         'host' => 'localhost',
//         'name' => 'test',
//         'username' => 'test',
//         'password' => '********',
//     ],
// ];

// $db = Database\Factory::build(new Configuration($cfg));
// try {
//     $agent = $db->connect()->getConnection()->getAgent()->query('select * from nonexists');
// } catch (\Exception $e) {
//     print $e->getMessage();
// }

$cfg = [
    'agent' => 'mysqli',
    'database' => ['host' => 'localhost', 'name' => 'test', 'username' => 'test', 'password' => '********'],
];
$cfg['query_error_handler'] = function($exception, $query, $queryParams) {
    print $exception->getMessage();
};

$db = Database\Factory::build(new Configuration($cfg));

$db->connect()->getConnection()->getAgent()->query('select * from nonexists');
