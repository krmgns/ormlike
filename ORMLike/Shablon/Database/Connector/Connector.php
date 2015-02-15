<?php namespace ORMLike\Shablon\Database\Connector;

abstract class Connector
{
    protected $configuration;
    protected $connections = [];

    // abstract public function connect();
    // abstract public function disconnect();
    // abstract public function isConnected();

    // abstract public function setConnection($host = null, $hostDetection = false, Connection $connection);
    // abstract public function getConnection($host = null);

    // abstract public function detectHost($host);
}
