<?php namespace ORMLike\Shablon\Database\Connector;

abstract class Connector
{
    protected $configuration;
    protected $connections = [];

    abstract public function connect($host = null);
    abstract public function disconnect($host = null);
    abstract public function isConnected($host = null);

    abstract public function setConnection($host, \ORMLike\Database\Connector\Connection $connection);
    abstract public function getConnection($host = null);
}
