<?php
header('content-type: text/plain');
function prd($s, $e=0) {print("\n"); var_dump($s); print("\n"); $e && exit;}
function pre($s, $e=0) {printf("%s\n", preg_replace('[(\w+):.*?\:private]', '\\1:private', print_r($s, 1))); $e && exit;}
#################################################

final class Database
{
    private $connector;

    final public function __construct($configuration) {
        $this->connector = new Connector($configuration);
    }

    final public function __call($method, $arguments) {
        return call_user_func_array([$this->connector, $method], $arguments);
    }
}

final class Connector
{
    private $agent;
    private $connections = [];

    final public function __construct($configuration) {
        $this->bootAgent($configuration);
    }

    final public function connect($server = null) {
        $server = $server ?: 0;
        if (!isset($this->connections[$server])) {
            $this->connections[$server] = $this->agent->connect($server);
        }
        return $this->connections[$server];
    }

    final private function bootAgent($configuration) {
        switch ($configuration['agent']) {
            case 'mysqli':
                $this->agent = new Agent_Mysqli($configuration);
                break;
            default:
                throw new Exception("..");
                break;
        }
    }
}

final class Agent_Mysqli
{
    private $link;
    private $configuration;

    final public function __construct($configuration) {
        $this->configuration = $configuration;
    }

    final public function connect() {
        if ($this->isConnected()) {
            return;
        }

        return 'Agent_Mysqli->link';
    }
    final public function disconnect() {
        if ($this->isConnected()) {
            mysqli_close($this->link);
            unset($this->link);
        }
    }
    final public function isConnected() {
        return (isset($this->link) && $this->link instanceof \mysqli && $this->link->connect_errno === 0);
    }
}


$db = new Database([
    'agent' => 'mysqli',
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
]);
// $db->connect();
$db->connect();
pre($db);
