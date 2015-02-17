<?php namespace ORMLike\Database\Connector\Agent;

use \ORMLike\Helper;
use \ORMLike\Logger;
use \ORMLike\Database\Profiler;
use \ORMLike\Database\Query\Result;
use \ORMLike\Exception\Database as Exception;

final class Mysqli
    extends \ORMLike\Shablon\Database\Connector\Agent\Agent
{
    final public function __construct(array $configuration) {
        if (!extension_loaded('mysqli')) {
            throw new \RuntimeException('Mysqli extension is not loaded.');
        }
        $this->result = new Result\Mysqli();
        $this->result->setFetchType(
            isset($configuration['fetch_type'])
                ? $configuration['fetch_type'] : Result::FETCH_OBJECT
        );
        $this->configuration = $configuration;

        $this->logger = new Logger();
        if (isset($configuration['profiling']) && $configuration['profiling'] == true) {
            $this->profiler = new Profiler();
        }
    }
    final public function __destruct() { $this->disconnect(); }
    // final public function __call($method, $arguments) {
    //     if (!method_exists($this, $method)) {
    //         throw new \BadMethodCallException(sprintf(
    //             '`%s::%s()` method does not exists!', __class__, $method));
    //     }
    // }

    /*** connection interface ***/
    final public function connect() {
        list($host, $name, $username, $password) = [
            $this->configuration['host'],
            $this->configuration['name'],
            $this->configuration['username'],
            $this->configuration['password'],
        ];
        $port = Helper::getArrayValue('port', $this->configuration);
        $socket = Helper::getArrayValue('socket', $this->configuration);

        $this->link = mysqli_init();

        // supported constants: http://php.net/mysqli.real_connect
        if (isset($this->configuration['connect_options'])) {
            foreach ($this->configuration['connect_options'] as $option => $value) {
                if (!is_string($option)) {
                    throw new Exception\ArgumentException(
                        'Please set all connection option constant names as string to track any setting error!');
                }
                $option = strtoupper($option);
                if (!defined($option)) {
                    throw new Exception\ArgumentException("`{$option}` option constant is not defined!");
                }
                if (!$this->link->options(constant($option), $value)) {
                    throw new Exception\ErrorException("Setting {$option} option failed!");
                }
            }
        }

        $this->profiler && $this->profiler->start(Profiler::CONNECTION);

        if (!$this->link->real_connect($host, $username, $password, $name, intval($port), $socket)) {
            throw new Exception\ConnectionException(sprintf(
                'Connection error! errno[%d] errmsg[%s]', $this->link->connect_errno, $this->link->connect_error));
        }

        $this->profiler && $this->profiler->stop(Profiler::CONNECTION);

        if (isset($this->configuration['charset'])) {
            $run = (bool) $this->link->set_charset($this->configuration['charset']);
            if ($run === false) {
                throw new Exception\ErrorException(sprintf(
                    'Failed setting charset as `%s`! errno[%d] errmsg[%s]',
                        $this->configuration['charset'], $this->link->errno, $this->link->error));
            }
        }

        if (isset($this->configuration['timezone'])) {
            $run = (bool) $this->link->query("SET time_zone='{$this->configuration['timezone']}'");
            if ($run === false) {
                throw new Exception\QueryException(sprintf(
                    'Query error! errmsg[%s]', $this->link->error));
            }
        }

        return $this->link;
    }
    final public function disconnect() {
        if ($this->link instanceof \mysqli) {
            $this->link->close();
            $this->link = null;
        }
    }
    final public function isConnected() {
        return ($this->link instanceof \mysqli && $this->link->connect_errno === 0);
    }

    /*** stream wrapper interface ***/
    final public function query($query, array $params = null) {
        $this->result->reset();

        $query = trim($query);
        if ($query == '') {
            throw new Exception\QueryException('Query cannot be empty!');
        }

        if (!empty($params)) {
            $query = $this->prepare($query, $params);
        }

        if ($this->profiler) {
            $this->profiler->setProperty(Profiler::PROP_QUERY_COUNT);
            $this->profiler->setProperty(Profiler::PROP_LAST_QUERY, $query);
        }

        $this->profiler && $this->profiler->start(Profiler::LAST_QUERY);
        $result = $this->link->query($query);
        $this->profiler && $this->profiler->stop(Profiler::LAST_QUERY);

        if (!$result) {
            try {
                throw new Exception\QueryException(sprintf(
                    'Query error: query[%s], error[%s], errno[%s]',
                        $query, $this->link->error, $this->link->errno
                ), $this->link->errno);
            } catch (\Exception $e) {
                $errorHandler = Helper::getArrayValue('query_error_handler', $this->configuration);
                if (is_callable($errorHandler)) {
                    return $errorHandler($e, $query, $params);
                }
                throw $e;
            }
        }

        $this->result->process($this->link, $result);

        return $this->result;
    }

    final public function find($query, array $params = null, $fetchType = null) {}
    final public function findAll($query, array $params = null, $fetchType = null) {}
    final public function select($table, $fields, $where = '1=1', array $params = null, $limit = null) {}
    final public function insert($table, array $data = null) {}
    final public function update($table, array $data = null, $where = '1=1', array $params = null, $limit = null) {}
    final public function delete($table, $where = '1=1', array $params = null, $limit = null) {}
    final public function id() {}
    final public function rowsCount() {}
    final public function rowsAffected() {}

    /*** stream filter interface ***/
    final public function prepare($input, array $params = null) {
        return $input;
    }
    final public function escape($input, $type = null) {}
    final public function escapeIdentifier($input) {}
}
