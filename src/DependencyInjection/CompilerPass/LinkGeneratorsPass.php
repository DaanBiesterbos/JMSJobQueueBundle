<?php

/*
 * This is a fork of the JMSQueueBundle.
 * See LICENSE file for license information.
 *
 * Issues can be submitted here:
 * https://github.com/daanbiesterbos/JMSJobQueueBundle/issues
 *
 * @author Johannes M. Schmitt (author original bundle)
 * @author Daan Biesterbos     (fork maintainer)
 */

namespace JMS\JobQueueBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LinkGeneratorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $generators = [];
        foreach ($container->findTaggedServiceIds('jms_job_queue.link_generator') as $id => $attrs) {
            $generators[] = new Reference($id);
        }

        $container->getDefinition('jms_job_queue.twig.extension')
                ->addArgument($generators);
    }
}
