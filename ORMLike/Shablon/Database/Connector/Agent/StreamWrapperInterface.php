<?php namespace ORMLike\Shablon\Database\Connector\Agent;

interface StreamWrapperInterface
{
    public function query($query, array $params = null);

    public function find($query, array $params = null, $fetchType = null);
    public function findAll($query, array $params = null, $fetchType = null);

    public function select($table, $fields, $where = '1=1', array $params = null, $limit = null);
    public function insert($table, array $data = null);
    public function update($table, array $data = null, $where = '1=1', array $params = null, $limit = null);
    public function delete($table, $where = '1=1', array $params = null, $limit = null);

    public function id(); // uuid, guid, serial, sequence, identity, last_insert_id @wtf
    public function rowsCount();
    public function rowsAffected();
}
