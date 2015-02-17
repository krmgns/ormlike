<?php
include('inc.php');

$autoload = require('./../ORMLike/Autoload.php');
$autoload->register();

use \ORMLike\Database as Database;
use \ORMLike\Configuration as Configuration;

/*** single ***/

$cfg = [
    'agent' => 'mysqli',
    'profiling' => true,
    // 'query_logging' => true,
    // 'query_logging_path' => '/.logs/db',
    // 'query_logging_format' => 'Y-m-d',
    // 'query_error_log' => true,
    // 'query_error_handler' => function($exception, $query, $queryParams) {},
    'database' => [
        'fetch_type' => 'object',
        'charset'    => 'utf8',
        'timezone'   => '+00:00',
        'host'       => 'localhost',
        'name'       => 'test',
        'username'   => 'test',
        'password'   => '********',
        // 'connect_options' => ['mysqli_opt_connect_timeout' => 3],
    ]
];

$db = Database\Factory::build(new Configuration($cfg));
$db->connect();

$result = $db->getConnection()->getAgent()->query("select * from users", [1]);
// $result = $db->getConnection()->getAgent()->query("select * from users", [1]);
// $result = $db->getConnection()->getAgent()->query("select * from users", [1]);
// $result = $db->getConnection()->getAgent()->query("select * from users", [1]);
// $result = $db->getConnection()->getAgent()->query("select * from users where id=1", [1]);
pre($result);

pre($db);

// pre($result->count());

foreach ($result as $user) {
    pre($user->name);
}
