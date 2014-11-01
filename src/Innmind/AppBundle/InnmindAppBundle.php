<?php

namespace Innmind\AppBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Innmind\AppBundle\DependencyInjection\Security\Factory\ServerFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InnmindAppBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new ServerFactory);
    }
}
