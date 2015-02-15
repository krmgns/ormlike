<?php namespace ORMLike\Database\Query\Result;

final class Mysqli
    extends \ORMLike\Database\Query\Result
{
    final public function process(\mysqli_result $result) {
        $this->result = $result;
        if ($this->result->num_rows) {
            $fetchFunction = $this->getFetchFunction($this->getFetchType());
            $i = 0;
            while ($row = $fetchFunction($this->result)) {
                $this->data[$i++] = $row;
            }
            // $this->free();
            $this->setRowsCount($i);
        }
    }

    final public function free() {
        if ($this->result instanceof \mysqli_result) {
            mysqli_free_result($this->result);
            $this->result = null;
        }
    }

    final protected function getFetchFunction($fetchType) {
        switch ($fetchType) {
            case self::FETCH_ASSOC:
                return 'mysqli_fetch_assoc';
            case self::FETCH_ARRAY:
                return 'mysqli_fetch_array';
            case self::FETCH_OBJECT:
            default:
                return 'mysqli_fetch_object';
        }
    }
}
