<?php namespace ORMLike\Shablon\Database\Connector\Agent;

abstract class Agent
    implements ConnectionInterface, StreamFilterInterface, StreamWrapperInterface
{
    const FETCH_ASSOC  = 'assoc';
    const FETCH_ARRAY  = 'array';
    const FETCH_OBJECT = 'object';

    protected $link;
    protected $result;
    protected $profiler;
    protected $configuration;

    public function getLink() {
        return $this->link;
    }

    public function getResult() {
        return $this->result;
    }

    public function getProfiler() {
        return $this->profiler;
    }

    public function getConfiguration() {
        return $this->configuration;
    }

    // auto dedect agent class name
    final public function getName() {
        $className = get_called_class();
        return strtolower(substr($className, strrpos($className, '\\') + 1));
    }
}
