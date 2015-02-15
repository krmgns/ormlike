<?php namespace ORMLike\Database\Connector\Agent;

use \ORMLike\Helper;
use \ORMLike\Database\Profiler;
use \ORMLike\Exception\Database as Exception;

final class Mysqli
    extends \ORMLike\Shablon\Database\Connector\Agent\Agent
{
    final public function __construct(array $configuration) {
        if (!extension_loaded('mysqli')) {
            throw new \RuntimeException('Mysqli extension is not loaded.');
        }
        $this->profiler = new Profiler();
        $this->configuration = $configuration;
        // pre($configuration,1);
        // burdan gelicek fetch_type i result setlerde kullanırsın
        // eger
    }
    final public function __destruct()  {
        $this->disconnect();
    }
    final public function __call($method, $arguments) {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException(sprintf(
                '`%s::%s()` method does not exists!', __class__, $method));
        }
    }

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

        // Supported constants: http://php.net/mysqli.real_connect
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
                if (!mysqli_options($this->link, constant($option), $value)) {
                    throw new Exception\ErrorException("Setting {$option} option failed! ");
                }
            }
        }

        $this->profiler->start(Profiler::CONNECTION);
        mysqli_real_connect($this->link, $host, $username, $password, $name, (int) $port, $socket);
        if (mysqli_connect_error()) {
            throw new Exception\ConnectionException(sprintf(
                'Connection error! errno[%d] errmsg[%s]', mysqli_connect_errno(), mysqli_connect_error()));
        }
        $this->profiler->stop(Profiler::CONNECTION);

        if (isset($this->configuration['charset'])) {
            $run = (bool) @mysqli_set_charset($this->link, $this->configuration['charset']);
            if ($run === false) {
                throw new Exception\ErrorException(sprintf(
                    'Failed setting charset as `%s`! errno[%d] errmsg[%s]',
                        $this->configuration['charset'], mysqli_connect_errno(), mysqli_error($this->link)));
            }
        }

        if (isset($this->configuration['timezone'])) {
            $run = (bool) @mysqli_query($this->link, "SET time_zone='{$this->configuration['timezone']}'");
            if ($run === false) {
                throw new Exception\QueryException(sprintf('Query error! errmsg[%s]', mysqli_error($this->link)));
            }
        }

        return $this->link;
    }
    final public function disconnect() {
        if ($this->link instanceof \mysqli) {
            mysqli_close($this->link);
            $this->link = null;
        }
    }
    final public function isConnected() {
        return ($this->link instanceof \mysqli && $this->link->connect_errno === 0);
    }

    /*** stream wrapper interface ***/
    final public function query($query, array $params = null) {
        // $this->reset();
        // ++$this->_queryCount;
        // $this->_query = trim($query);
        // if ('' === $this->_query) {
        //     throw new ORMLikeException('Query cannot be empty.');
        // }

        // if (null !== $params) {
        //     $this->_query = $this->prepare($query, $params);
        // }

        // $this->_timerStart = microtime(true);

        if (!$result =@ mysqli_query($this->link, $query)) {
            throw new Exception\QueryException(sprintf(
                'Query error: query[%s], error[%s]', $query, mysqli_error($this->link)));
        }
        // $this->setResult($result); // burda result ile oynicak iste...

        // $this->_timerStop          = microtime(true);
        // $this->_timerProcess       = number_format(floatval($this->_timerStop - $this->_timerStart), 4);
        // $this->_timerProcessTotal += $this->_timerProcess;

        // return $this->getResult();
        return $result;
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
    final public function prepare($input, array $params = null) {}
    final public function escape($input, $type = null) {}
    final public function escapeIdentifier($input) {}
}
