<?php namespace ORMLike\Shablon\Database\Connector\Agent;

use \ORMLike\Exception\Database as Exception;

abstract class Agent
    implements ConnectionInterface, StreamFilterInterface, StreamWrapperInterface
{
    protected $link;
    protected $result;
    protected $logger;
    protected $profiler;
    protected $configuration;

    public function getLink() {
        return $this->link;
    }

    public function getResult() {
        return $this->result;
    }

    public function getLogger() {
        return $this->logger;
    }

    public function getProfiler() {
        if (!$this->profiler) {
            throw new \ErrorException(
                'Profiler is not found, did you set `profiling` option as true?');
        }
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

    final public function id() {
        return $this->result->getId();
    }

    final public function rowsCount() {
        return $this->result->getRowsCount();
    }

    final public function rowsAffected() {
        return $this->result->getRowsAffected();
    }

    final public function prepare($input, array $params = null) {
        if (!empty($params)) {
            preg_match_all('~%[sdfF]|\?|:[a-zA-Z0-9_]+~', $input, $match);
            if (isset($match[0])) {
                if (count($match[0]) != count($params)) {
                    throw new Exception\ArgumentException(
                        "Both modifiers and params count must be same, e.g: prepare('id = ?', [1]) or ".
                        "prepare('id IN(?,?)', [1,2]). If you have no prepare modifiers, then pass NULL or [] as \$params."
                    );
                }
                $i = 0; // Indexes could be string, e.g: array(':id' => 1, ...)
                foreach ($params as $key => $val) {
                    $key = $match[0][$i++];
                    $val = $this->escape($val, $key);
                    if (false !== ($pos = strpos($input, $key))) {
                        $input = substr_replace($input, $val, $pos, strlen($key));
                    }
                }
            }
        }

        return $input;
    }
}
