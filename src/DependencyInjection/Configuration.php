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

namespace JMS\JobQueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * JMSJobQueueBundle Configuration.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('jms_job_queue');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('statistics')->defaultTrue()->end();

        $defaultOptionsNode = $rootNode
            ->children()
                ->arrayNode('queue_options_defaults')
                    ->addDefaultsIfNotSet();
        $this->addQueueOptions($defaultOptionsNode);

        $queueOptionsNode = $rootNode
            ->children()
                ->arrayNode('queue_options')
                    ->useAttributeAsKey('queue')
                    ->prototype('array');
        $this->addQueueOptions($queueOptionsNode);

        return $treeBuilder;
    }

    private function addQueueOptions(ArrayNodeDefinition $def)
    {
        $def
            ->children()
                ->scalarNode('max_concurrent_jobs')->end()
        ;
    }
}
