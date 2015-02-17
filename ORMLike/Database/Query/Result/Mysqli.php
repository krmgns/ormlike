<?php namespace ORMLike\Database\Query\Result;

use \ORMLike\Exception\Database as Exception;

final class Mysqli
    extends \ORMLike\Database\Query\Result
{
    final public function process(\mysqli $link, \mysqli_result $result) {
        $this->result = $result;

        $i = 0;
        if ($this->result->num_rows) {
            switch ($this->fetchType) {
                case self::FETCH_OBJECT:
                    while ($row = $this->result->fetch_object()) {
                        $this->data[$i++] = $row;
                    }
                    $this->free();
                    break;
                case self::FETCH_ARRAY_ASSOC:
                    while ($row = $this->result->fetch_assoc()) {
                        $this->data[$i++] = $row;
                    }
                    $this->free();
                    break;
                case self::FETCH_ARRAY_NUM:
                    while ($row = $this->result->fetch_array(MYSQLI_NUM)) {
                        $this->data[$i++] = $row;
                    }
                    $this->free();
                    break;
                case self::FETCH_ARRAY_BOTH:
                    while ($row = $this->result->fetch_array()) {
                        $this->data[$i++] = $row;
                    }
                    $this->free();
                    break;
                default:
                    throw new Exception\ResultException(
                        "Could not implement given `{$this->fetchType}` fetch type!");
            }
        }

        // set properties
        $this->setId($link->insert_id);
        $this->setRowsCount($i);
        $this->setRowsAffected($link->affected_rows);
    }

    final public function free() {
        if ($this->result instanceof \mysqli_result) {
            $this->result->free();
            $this->result = null;
        }
    }
}
