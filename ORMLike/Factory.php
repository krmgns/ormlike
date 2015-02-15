<?php namespace ORMLike;

final class Factory
    implements \ORMLike\Shablon\FactoryInterface
{
    final public static function build($className, array $arguments = null) {
        if ($className[0] != '\\') {
            $className = '\\'. $className;
        }

        if (strpos($className, '\ORMLike') !== 0) {
            $className = '\ORMLike' . $className;
        }

        if (!class_exists($className, true)) {
            throw new \RuntimeException(sprintf(
                '`%s` class does not exists!', $className));
        }

        switch (count($arguments)) {
            case 0: return new $className();
            case 1: return new $className($arguments[0]);
        }

        return (new \ReflectionClass($className))->newInstanceArgs($arguments);
    }
}
