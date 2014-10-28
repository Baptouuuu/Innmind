<?php

namespace Innmind\AppBundle;

use Rhumsaa\Uuid\Uuid as RUuid;

/**
 * UUID generator
 */
class UUID
{
    /**
     * Generate a random UUID
     *
     * @return string
     */
    public function generate()
    {
        return RUuid::uuid4();
    }
}
