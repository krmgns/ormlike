<?php namespace ORMLike\Shablon\Database\Query;

abstract class Result
    implements \IteratorAggregate, \Countable
{
    abstract public function free();
}
