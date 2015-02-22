<?php
include('inc.php');

$autoload = require('./../ORMLike/Autoload.php');
$autoload->register();

use \ORMLike\Database as Database;
use \ORMLike\Configuration as Configuration;

$cfg = [
    'agent' => 'mysqli',
    'query_logging' => true,
    'query_logging_path' => '/.logs/db',
    'query_logging_format' => 'Y-m-d',
    'query_error_log' => true,
    // 'query_error_handler' => function($exception, $query, $queryParams) {},
    'database' => [
        'host'       => 'localhost',
        'name'       => 'test',
        'username'   => 'test',
        'password'   => '********',
    ]
];

$db = Database\Factory::build(new Configuration($cfg));
$db->connect();

$agent = $db->getConnection()->getAgent();

// $result = $agent->query('select * from nonexists');

// pre($result);

pre($agent);
// pre($db);
