<?php

namespace Innmind\AppBundle\Graph;

/**
 * Contains all the relations possible in the graph
 */

class NodeRelations
{
    const REFERER = 'REFER';
    const TRANSLATE = 'TRANSLATE';
    const CONTAINS = 'CONTAINS';
    const BELONGS_TO = 'BELONGS_TO';
    const CANONICAL = 'CANONICAL';
}
