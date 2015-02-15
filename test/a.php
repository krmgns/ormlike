<?php
header('content-type: text/plain');

function pre($s, $e=0) {
    printf("%s\n", preg_replace('[(\w+):.*?\:private]', '\\1:private', print_r($s, 1))); $e && exit;
}
function prd($s, $e=0) {
    print("\n"); var_dump($s); print("\n"); $e && exit;
    // printf("%s\n", var_export($s, 1)); $e && exit;
}

// http://php.net/manual/en/mysqli.real-query.php
//  - http://php.net/manual/en/mysqli.use-result.php
// http://php.net/manual/en/class.mysqli-result.php
#################################################
$autoload = require('./../ORMLike/Autoload.php');
$autoload->register();

use \ORMLike\Configuration as Configuration;
use \ORMLike\DatabaseFactory as DatabaseFactory;

// $db = ORMLike\Database::initialize(new ORMLike\Configuration([
//     'agent' => 'mysqli',
//     'fetch_type' => 'object',
//     // 'connect_options' => ['mysqli_opt_connect_timeout' => 3],
//     'db.host' => 'localhost',
//     'db.name' => 'test',
//     'db.username' => 'test',
//     'db.password' => '********',
//     'db.charset' => 'utf8',
//     'db.timezone' => '+00:00',
//     // 'db.port' => '2222',
//     // 'db.socket' => '/path/to/a.sock',
// ]));
// $db->connect();

// pre($db);

// $result = $db->connector()->query("select * from `users`");
// pre($result);

// use ORMLike\Factory as Factory;
// use ORMLike\Configuration as Configuration;
// $db = Factory::build('Database', [new Configuration()]);

// $db = DatabaseFactory::initialize(new Configuration([
    // 'agent' => 'mysqli',
    // 'db' => [
        // 'master' => ['host' => 'localhost', 'name' => 'test', 'username' => 'test', 'password' => '********']
        // 'slaves' => [
        //     ['host' => 'localhost', 'name' => 'test', 'username' => 'test', 'password' => '********']
        // ]
    // ]
// ]));
// $db->using()->connect();

// $db->connectTo('master')->getAgent(); // gibi gibi
// pre($db);

// no sharding
// $cfg = [
//     'agent' => 'mysqli',
//     'database' => [
//         'host' => '127.0.0.1', 'name' => 'test', 'username' => 'test', 'password' => '********',
//         'charset' => 'utf8', 'timezone' => '+00:00'
//     ]
// ];
// $db = DatabaseFactory::build(new Configuration($cfg));
// pre($db);
// $result = $db->connect()->query('select * from `users`');

// sharding with master/slaves
$cfg = [
    'agent' => 'mysqli',
    'sharding' => true,
    'database' => [
        'username' => 'test',
        'password' => '********',
        'host' => '127.0.0.1', 'name' => 'test',
        // 'master' => ['host' => '127.0.0.1', 'name' => 'test'],
        // 'slaves' => [
        //     ['host' => 'serv1.mysql.local', 'name' => 'test'],
        //     ['host' => 'serv2.mysql.local', 'name' => 'test'],
        //     ['host' => 'serv3.mysql.local', 'name' => 'test'],
        // ]
    ]
];
$db = DatabaseFactory::build(new Configuration($cfg));
pre($db);
$db = $db->connect('master');
pre($db);
// $result = $db->connectTo('master')->query('select * from `users`');
