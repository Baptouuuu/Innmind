<?php

namespace Innmind\AppBundle;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Helper to generate appropriate graph node label based on published data
 */
class LabelGuesser
{
    /**
     * Return all the labels corresponding to the crawled data
     * @param  ParameterBag $request
     *
     * @return array
     */
    public function guess(ParameterBag $request)
    {
        $labels = [];

        switch (true) {
            case $request->has('title') && $request->has('content'):
                $labels = ['Document'];
                break;
        }

        array_unshift($labels, 'Resource');
        return $labels;
    }
}
