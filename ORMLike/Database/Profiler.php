<?php namespace ORMLike\Database;

use \ORMLike\Exception;

final class Profiler
    extends \ORMLike\Shablon\Database\Profiler\Profiler
{
    public function __construct() {}

    public function start($name) {
        $this->profiles[$name] = [
            'start' => microtime(true),
            'stop'  => 0,
            'total' => 0
        ];
    }

    public function stop($name) {
        if (isset($this->profiles[$name])) {
            $this->profiles[$name]['stop'] = microtime(true);
            $this->profiles[$name]['total'] = number_format(
                (float) $this->profiles[$name]['stop'] - $this->profiles[$name]['start'], 10);
            return $this;
        }

        throw new Exception\ArgumentException("Could not find a `{$name}` profile name.");
    }

    public function setProperty($name, $value = null) {
        if ($name === self::PROP_QUERY_COUNT) {
            ++$this->properties[$name];
        } else {
            $this->properties[$name] = $value;
        }
    }

    public function getProperty($name) {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        throw new Exception\ArgumentException('Undefined property name given!');
    }
}
