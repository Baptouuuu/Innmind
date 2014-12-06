<?php

namespace Innmind\AppBundle;

use Pdp\PublicSuffixListManager;
use Pdp\Parser;

class DomainParserFactory
{
    public static function make()
    {
        $manager = new PublicSuffixListManager();
        return new Parser($manager->getList());
    }
}
