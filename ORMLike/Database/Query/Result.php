<?php namespace ORMLike\Database\Query;

use \ORMLike\Exception\Database as Exception;

class Result
    extends \ORMLike\Shablon\Database\Query\Result
{
    const FETCH_OBJECT       = 1;
    const FETCH_ARRAY_ASSOC  = 2;
    const FETCH_ARRAY_NUM    = 3;
    const FETCH_ARRAY_BOTH   = 4;

    protected $data = [];

    protected $result;
    protected $fetchType;

    protected $id = null; // last insert id
    protected $rowsCount = 0;
    protected $rowsAffected = 0;

    public function free() {
        $this->result = null;
    }

    public function reset() {
        $this->data = [];
    }

    public function setFetchType($fetchType) {
        $fetchTypeConst = 'self::FETCH_'. strtoupper($fetchType);
        if (!defined($fetchTypeConst)) {
            throw new Exception\ArgumentException(
                "Given `{$fetchType}` fetch type is not implemented!");
        }

        $this->fetchType = constant($fetchTypeConst);
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

    protected function setId($id) {
        if ($id === 0) {
            $id = null;
        }
        $this->id = $id;
    }

    protected function getId($id) {
        return $this->id;
    }

    protected function setRowsCount($count) {
        $this->rowsCount = $count;
    }

    protected function getRowsCount() {
        return $this->rowsCount;
    }

    protected function setRowsAffected($count) {
        $this->rowsAffected = $count;
    }

    protected function getRowsAffected() {
        return $this->rowsAffected;
    }
}
