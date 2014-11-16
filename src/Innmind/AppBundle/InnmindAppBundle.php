<?php

namespace Innmind\AppBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Innmind\AppBundle\DependencyInjection\Security\Factory\ServerFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Innmind\AppBundle\DependencyInjection\Compiler\RabbitMQPass;

class InnmindAppBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new ServerFactory);

        $container->addCompilerPass(new RabbitMQPass);
    }
}
