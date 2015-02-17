<?php namespace ORMLike\Database;

use \ORMLike\Exception;

final class Profiler
    extends \ORMLike\Shablon\Database\Profiler\Profiler
{
    public function __construct($profiling = true) {
        $this->profiling = $profiling;
    }

    public function start($name) {
        if (!$this->profiling) return;

        $this->profiles[$name] = [
            'start' => microtime(true),
            'stop'  => 0,
            'total' => 0
        ];
    }

    public function stop($name) {
        if (!$this->profiling) return;

        if (isset($this->profiles[$name])) {
            $this->profiles[$name]['stop'] = microtime(true);
            $this->profiles[$name]['total'] =
                (float) $this->profiles[$name]['stop'] - $this->profiles[$name]['start'];
            return $this;
        }

        throw new Exception\ErrorException("Could not find a `{$name}` profile name.");
    }
}
