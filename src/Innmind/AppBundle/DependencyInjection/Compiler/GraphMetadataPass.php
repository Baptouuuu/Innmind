<?php

namespace Innmind\AppBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GraphMetadataPass implements CompilerPassInterface
{
    public function process (ContainerBuilder $container)
    {
        $metadata = $container->getDefinition('graph.metadata');

        $services = $container->findTaggedServiceIds('graph.metadata');

        foreach ($services as $id => $tags) {
            $metadata->addMethodCall(
                'addPass',
                [new Reference($id)]
            );
        }
    }
}
