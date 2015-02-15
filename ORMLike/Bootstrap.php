<?php namespace ORMLike;

final class Bootstrap
{
    private static $cfg;

    final public static function initialize(Configuration $cfg) {
        self::$cfg = $cfg;
    }
}
