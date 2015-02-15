<?php namespace ORMLike\Shablon\Database\Profiler;

use \ORMLike\Exception;

abstract class Profiler
    implements ProfilerInterface
{
    const QUERY = 'query';
    const CONNECTION = 'connection';

    protected $profiles = [];

    public function getProfile($name) {
        if (isset($this->profiles[$name])) {
            return $this->profiles[$name];
        }

        throw new Exception\ErrorException("Could not find a `{$name}` name to profile.");
    }
    public function getProfileAll() {
        return $this->profiles;
    }

    abstract public function start($name);
    abstract public function stop($name);
}
