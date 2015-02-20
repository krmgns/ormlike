<?php namespace ORMLike\Database\Connector\Agent;

use \ORMLike\Helper;
use \ORMLike\Logger;
use \ORMLike\Database\Profiler;
use \ORMLike\Database\Query\Sql;
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
        return ($this->link instanceof \mysqli &&
                $this->link->connect_errno === 0);
    }

    /*** stream wrapper interface ***/
    final public function query($query, array $params = null, $limit = null, $fetchType = null) {
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
            } catch (Exception\QueryException $e) {
                $errorHandler = Helper::getArrayValue('query_error_handler', $this->configuration);
                if (is_callable($errorHandler)) {
                    return $errorHandler($e, $query, $params);
                }
                throw $e;
            }
        }

        $this->result->process($this->link, $result, $limit, $fetchType);

        return $this->result;
    }

    // bunlari parent icine alalim?
    final public function get($query, array $params = null, $fetchType = null) {
        return $this->query($query, $params, 1, $fetchType)->getData();
    }
    final public function getAll($query, array $params = null, $fetchType = null) {
        return $this->query($query, $params, null, $fetchType)->getData();
    }

    final public function select($table, array $fields, $where = null, array $params = null, $limit = null) {
        return $this->query(sprintf('SELECT %s FROM %s %s %s',
                $this->escapeIdentifier($fields),
                $this->escapeIdentifier($table),
                $this->where($where, $params),
                $this->limit($limit)
        ))->getData();
    }

    final public function insert($table, array $data = null) {}
    final public function update($table, array $data = null, $where = null, array $params = null, $limit = null) {}
    final public function delete($table, $where = null, array $params = null, $limit = null) {}

    final public function escape($input, $type = null) {
        // excepting strings, for all formattable types like %d, %f and %F
        if (!is_array($input)) {
            if ($type && $type[0] == '%' && $type != '%s') {
                return sprintf($type, $input);
            }
        }

        if ($input instanceof Sql) {
            return $input->toString();
        }

        switch (gettype($input)) {
            case 'NULL'   : return 'NULL';
            case 'integer': return $input;
            // 1/0, afaik true/false not supported yet in mysql
            case 'boolean': return (int) $input;
            // %F = non-locale aware
            case 'double' : return sprintf('%F', $input);
            // in/not in statements
            case 'array'  : return join(', ', array_map([$this, 'escape'], $input));
            // i trust you baby..
            case 'string' : return "'". $this->link->real_escape_string($input) ."'";
            default:
                throw new Exception\ArgumentException(sprintf(
                    'Unimplemented type encountered! type[`%s`]', gettype($input)));
        }

        return $input;
    }
    final public function escapeIdentifier($input) {
        return !is_array($input)
            ? '`'. trim($input, '` ') .'`'
            : join(', ', array_map([$this, 'escapeIdentifier'], $input));
    }

    final public function where($where, array $params = null) {
        if (!empty($params)) {
            $where = 'WHERE '. $this->prepare($where, $params);
        }
        return $where;
    }

    final public function limit($limit) {
        if (is_array($limit)) {
            return sprintf('LIMIT %d, %d', $limit[0], $limit[1]);
        }
        return $limit ? sprintf('LIMIT %d', $limit) : '';
    }
}
