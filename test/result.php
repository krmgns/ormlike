<?php
include('inc.php');

$autoload = require('./../ORMLike/Autoload.php');
$autoload->register();

use \ORMLike\Database as Database;
use \ORMLike\Configuration as Configuration;

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

$agent = $db->getConnection()->getAgent();

// $result = $agent->query("select * from `users`");
// $result = $agent->query("select * from `users`");
// $result = $agent->query("select * from `users`");
// $result = $agent->query("select * from `users` where `id` = ?", [1]);
// $result = $agent->query("select * from `users` where `id` IN(?)", [[1,2]]);
// $result = $agent->query("select * from `users` where `id` IN(?,?)", [1,2]);
// pre($result,1);

// pre($result->count());
// foreach ($result as $user) {
//     pre($user->name);
// }

// $result = $agent->query("update `users` set `old` = 30 where `id`=?", [1]);
// pre($agent->rowsAffected());
// pre($result);

// $result = $agent->get("select * from `users` where `id` = ?", [1]);
// pre($result);
// $result = $agent->get("select * from `users`");
// pre($result);

// $result = $agent->getAll("select * from `users`");
// pre($result);
// $result = $agent->getAll("select * from `users` where `id` in(?,?)", [1,2]);
// pre($result);

// $agent->get("select * from `users`");
// $result = $agent->rowsCount();
// prd($result);

// pre($agent);
// pre($db);
