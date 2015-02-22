<?php
include('inc.php');

$autoload = require('./../ORMLike/Autoload.php');
$autoload->register();

use \ORMLike\Logger as Logger;
use \ORMLike\Database as Database;
use \ORMLike\Configuration as Configuration;

$cfg = [
    'agent' => 'mysqli',
    'query_log' => true,
    'query_log_level' => Logger::ALL,
    'query_log_directory' => __dir__.'/../.logs/db',
    'query_log_filename_format' => 'Y-m-d',
    'database' => [
        'host' => 'localhost',
        'name' => 'test',
        'username' => 'test',
        'password' => '********',
    ],
];

$db = Database\Factory::build(new Configuration($cfg));
$db->connect();

$agent = $db->getConnection()->getAgent();

$result = $agent->query('select * from nonexists');

// pre($result);

pre($agent);
// pre($db);
