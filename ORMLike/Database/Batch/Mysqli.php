<?php namespace ORMLike\Database\Batch;

use \ORMLike\Database\Connector\Agent;

final class Mysqli
    extends \ORMLike\Shablon\Database\Batch\Batch
{
    final public function __construct(Agent\Mysqli $agent) {
        $this->agent = $agent;
    }

    final public function lock() {
        $this->agent->getLink()->autocommit(false);
    }

    final public function unlock() {
        $this->agent->getLink()->autocommit(true);
    }

    final public function queue($query, array $params = null) {
        $this->queue[] = $this->agent->prepare($query, $params);
    }

    final public function run() {
        $link = $this->agent->getLink();

        $start = microtime(true);

        foreach ($this->queue as $query) {
            $result = clone $this->agent->query($query);
            if ($result->getRowsAffected()) {
                $result->setId($link->insert_id);
                $this->result[] = $result;
            }
            unset($result);
        }

        $link->commit();

        $stop = microtime(true);

        $this->totalTime = number_format((float) ($stop - $start), 10);

        $this->agent->getResult()->reset();

        $link->autocommit(true);
    }

    final public function cancel() {
        $this->reset();

        $link = $this->agent->getLink();
        $link->rollback(true);
        $link->autocommit(true);
    }
}
