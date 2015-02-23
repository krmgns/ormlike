<?php namespace ORMLike\Database\Query;

final class Builder
{
    // Operators
    const OP_OR   = 'OR',
          OP_AND  = 'AND',
          OP_ASC  = 'ASC',
          OP_DESC = 'DESC';


    private $query = [];
    private $queryString = '';

    private $table;

    private $connection;

    public function __construct(\ORMLike\Database\Connector\Connection $connection) {
        $this->connection = $connection;
    }

    public function __toString() {
        return $this->toString();
    }

    public function setTable($table) {
        $this->table = $table;
    }
    public function getTable() {
        return $this->table;
    }

    protected function push($key, $value) {
        // Set query sub array
        if (!isset($this->query[$key])) {
            $this->query[$key] = [];
        }
        $this->query[$key][] = $value;

        return $this;
    }

    public function reset() {
        $this->query = [];
        $this->queryString = '';
        return $this;
    }

    public function select($field = null) {
        $this->reset();
        // pass for aggregate method, e.g select().aggregate('count', 'id')
        if (empty($field)) {
            $field = 1;
        }
        return $this->push('select', $field);
    }

    public function insert(array $data) {
        $this->reset();
        // simply check is assoc?
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $data = [$data];
                break;
            }
        }
        $this->push('insert', $data);
    }

    public function update(array $data) {}
    public function delete() {}

    public function joinLeft($table, $on, array $params = null) {
        // Prepare params safely
        if (!empty($params)) {
            $on = $this->connection->getAgent()->prepare($on, $params);
        }

        return $this->push('join', sprintf('%s ON %s', $table, $on));
    }

    public function joinLeftUsing($table, $using, array $params = null) {
        // Prepare params safely
        if (!empty($params)) {
            $using = $this->connection->getAgent()->prepare($using, $params);
        }

        return $this->push('join', sprintf('%s USING (%s)', $table, $using));
    }

    public function where($query, array $params = null, $op = self::OP_AND) {
        if (!empty($params)) {
            $query = $this->connection->getAgent()->prepare($query, $params);
        }
        if (isset($this->query['where']) && !empty($this->query['where'])) {
            $query = sprintf('%s %s', $op, $query);
        }

        return $this->push('where', $query);
    }

    public function whereLike($query, array $params = null, $op = self::OP_AND) {
        if (!empty($params)) {
            foreach ($params as &$param) {
                $charFirst = strval($param[0]);
                $charLast  = substr($param, -1);
                // both appended
                if ($charFirst == '%' && $charLast == '%') {
                    $param = $charFirst . addcslashes(substr($param, 1, -1), '%_') . $charLast;
                }
                // left appended
                elseif ($charFirst == '%') {
                    $param = $charFirst . addcslashes(substr($param, 1), '%_');
                }
                // right appended
                elseif ($charLast == '%') {
                    $param = addcslashes(substr($param, 0, -1), '%_') . $charLast;
                }
            }
        }

        return $this->where($query, $params, $op);
    }

    public function whereNull($field) {
        return $this->push('where', sprintf('%s IS NULL', $field));
    }

    public function whereNotNull($field) {
        return $this->push('where', sprintf('%s IS NOT NULL', $field));
    }

    public function having($query, array $params = null) {
        if (!empty($params)) {
            $query = $this->connection->getAgent()->prepare($query, $params);
        }

        return $this->push('having', $query);
    }

    public function groupBy($field) {
        return $this->push('groupBy', $field);
    }

    public function orderBy($field, $op = self::OP_ASC) {
        return $this->push('orderBy', $field .' '. $op);
    }

    public function limit($start, $stop = null) {
        return ($stop === null)
            ? $this->push('limit', $start)
            : $this->push('limit', $start)->push('limit', $stop);
    }

    public function aggregate($aggr, $field = '*', $fieldAlias = null) {
        if (empty($fieldAlias)) {
            $fieldAlias = ($field && $field != '*')
                // aggregate('count', 'id') count_id
                // aggregate('count', 'u.id') count_uid
                ? preg_replace('~[^\w]~', '', $aggr .'_'. $field) : $aggr;
        }
        return $this->push('aggregate', sprintf('%s(%s) %s',
            $aggr, $field, $fieldAlias
        ));
    }

    public function execute(callable $callback = null) {
        $result = $this->connection->getAgent()->query($this->toString());
        // Render result if callback provided
        if (is_callable($callback)) {
            $result = $callback($result);
        }

        return $result;
    }

    public function get(callable $callback = null) {
        $result = $this->connection->getAgent()->get($this->toString());
        if (is_callable($callback)) {
            $result = $callback($result);
        }

        return $result;
    }

    public function getAll(callable $callback = null) {
        $result = $this->connection->getAgent()->getAll($this->toString());
        if (is_callable($callback)) {
            $result = $callback($result);
        }

        return $result;
    }

    public function toString() {
        // Set once query string
        if (!empty($this->query) && empty($this->queryString)) {
            // Add select statement
            if (isset($this->query['select'])) {
                // Add aggregate statements
                $aggregate = isset($this->query['aggregate'])
                    ? ', '. join(', ', $this->query['aggregate'])
                    : '';
                $this->queryString .= sprintf('SELECT %s%s FROM %s',
                    join(', ', $this->query['select']), $aggregate, $this->table);

                // Add left join statement
                if (isset($this->query['join'])) {
                    $this->queryString .= sprintf(' LEFT JOIN %s', join(' ', $this->query['join']));
                }

                // Add where statement
                if (isset($this->query['where'])) {
                    $this->queryString .= sprintf(' WHERE %s', join(' ', $this->query['where']));
                }

                // Add group by statement
                if (isset($this->query['groupBy'])) {
                    $this->queryString .= sprintf(' GROUP BY %s', join(', ', $this->query['groupBy']));
                }

                // Add having statement
                if (isset($this->query['having'])) {
                    // Use only first element of having for now..
                    $this->queryString .= sprintf(' HAVING %s', $this->query['having'][0]);
                }

                // Add order by statement
                if (isset($this->query['orderBy'])) {
                    $this->queryString .= sprintf(' ORDER BY %s', join(', ', $this->query['orderBy']));
                }

                // Add limit statement
                if (isset($this->query['limit'])) {
                    $this->queryString .= !isset($this->query['limit'][1])
                        ? sprintf(' LIMIT %d', $this->query['limit'][0])
                        : sprintf(' LIMIT %d,%d', $this->query['limit'][0], $this->query['limit'][1]);
                }
            } elseif (isset($this->query['insert']) && !empty($this->query['insert'])) {
                $agent = $this->connection->getAgent();

                $keys = $values = [];
                foreach (current($this->query['insert']) as $insert) {
                    $keys = $agent->escapeIdentifier(array_keys($insert));
                    foreach ($insert as $key => $value) {
                        $values[] = '('. $agent->escape($value) .')';
                    }
                }
                // pre($keys,1);

                $this->queryString = sprintf("INSERT INTO {$this->table} (%s) VALUES %s",
                    join(',', $keys), join(', ', $values));
            }

            $this->queryString = trim($this->queryString);
        }

        return $this->queryString;
    }
}
