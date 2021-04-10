<?php

namespace Symfony\Component\VarDumper\Tests\Fixtures;

use stdClass;

interface FooInterface
{
    /**
     * Hello.
     */
    public function foo(?stdClass $a, stdClass $b = null);
}
