<?php namespace ORMLike\Database;

use \ORMLike\Configuration;

final class Factory
{
    final static public function build(Configuration $configuration) {
        return \ORMLike\Factory::build('\ORMLike\Database', [$configuration]);
    }
}
