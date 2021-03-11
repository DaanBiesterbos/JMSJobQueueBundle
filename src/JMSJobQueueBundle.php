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

namespace JMS\JobQueueBundle;

use JMS\JobQueueBundle\DependencyInjection\CompilerPass\LinkGeneratorsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JMSJobQueueBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new LinkGeneratorsPass());
    }
}
