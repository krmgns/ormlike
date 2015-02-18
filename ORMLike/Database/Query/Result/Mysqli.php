<?php namespace ORMLike\Database\Query\Result;

use \ORMLike\Exception\Database as Exception;

final class Mysqli
    extends \ORMLike\Database\Query\Result
{

    final public function free() {
        if ($this->result instanceof \mysqli_result) {
            $this->result->free();
            $this->result = null;
        }
    }

    final public function reset() {
        $this->data = [];
        $this->id = null;
        $this->rowsCount = 0;
        $this->rowsAffected = 0;
    }

    final public function process($link, $result) {
        if (!$link instanceof \mysqli) {
            throw new Exception\ArgumentException('Process link must be instanceof mysqli!');
        }

        $i = 0;
        if ($result instanceof \mysqli_result && $result->num_rows) {
            $this->result = $result;
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
}
