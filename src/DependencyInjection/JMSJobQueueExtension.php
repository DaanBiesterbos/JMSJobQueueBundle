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

use JMS\JobQueueBundle\Console\CronCommand;
use JMS\JobQueueBundle\Cron\JobScheduler;
use JMS\JobQueueBundle\Entity\Type\SafeObjectType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class JMSJobQueueExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('console.xml');

        $container->setParameter('jms_job_queue.statistics', $config['statistics']);
        if ($config['statistics']) {
            $loader->load('statistics.xml');
        }

        $container->registerForAutoconfiguration(JobScheduler::class)
            ->addTag('jms_job_queue.scheduler');
        $container->registerForAutoconfiguration(CronCommand::class)
            ->addTag('jms_job_queue.cron_command');

        $container->setParameter('jms_job_queue.queue_options_defaults', $config['queue_options_defaults']);
        $container->setParameter('jms_job_queue.queue_options', $config['queue_options']);
    }

    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    'jms_job_safe_object' => [
                        'class' => SafeObjectType::class,
                        'commented' => true,
                    ],
                ],
            ],
        ]);
    }
}
