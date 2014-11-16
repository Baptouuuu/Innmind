<?php

namespace Innmind\AppBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RabbitMQPass implements CompilerPassInterface
{
    public function process (ContainerBuilder $container)
    {
        $rabbitmq = $container->getDefinition('rabbit');

        $services = $container->findTaggedServiceIds('old_sound_rabbit_mq.producer');

        foreach ($services as $id => $tags) {
            $rabbitmq->addMethodCall(
                'addProducer',
                [
                    substr($id, 20, -9),
                    new Reference($id)
                ]
            );
        }
    }
}
