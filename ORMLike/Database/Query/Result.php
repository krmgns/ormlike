<?php namespace ORMLike\Database\Query;

use \ORMLike\Exception\Database as Exception;

abstract class Result
    extends \ORMLike\Shablon\Database\Query\Result
{
    const FETCH_OBJECT = 'objects';
    const FETCH_ASSOC  = '';
    const FETCH_ARRAY  = '';

    protected $data = [];

    protected $result;
    protected $fetchType;

    protected $rowsCount = 0;
    protected $rowsAffected = 0;

    public function reset() {
        $this->data = [];
    }

    public function setFetchType($fetchType) {
        if (!defined(strtoupper('self::FETCH_'. $fetchType))) {
            throw new Exception\ArgumentException(
                "Given `{$fetchType}` fetch type not implemented!");
        }
        $this->fetchType = $fetchType;
    }
    public function getFetchType() {
        return $this->fetchType;
    }

    public function count() {
        return count($this->data);
    }

    public function getIterator() {
        return new \ArrayIterator($this->data);
    }

    protected function setRowsCount($count) { $this->rowsCount = $count; }
    protected function setRowsAffected($count) { $this->rowsAffected = $count; }
}
