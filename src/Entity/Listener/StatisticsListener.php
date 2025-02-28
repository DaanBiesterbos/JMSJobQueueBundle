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

namespace JMS\JobQueueBundle\Entity\Listener;

use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

class StatisticsListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $event)
    {
        $schema = $event->getSchema();

        // When using multiple entity managers ignore events that are triggered by other entity managers.
        if ($event->getEntityManager()->getMetadataFactory()->isTransient('JMS\JobQueueBundle\Entity\Job')) {
            return;
        }

        $table = $schema->createTable('jms_job_statistics');
        $table->addColumn('job_id', 'bigint', ['notnull' => true, 'unsigned' => true]);
        $table->addColumn('characteristic', 'string', ['length' => 30, 'notnull' => true]);
        $table->addColumn('createdAt', 'datetime', ['notnull' => true]);
        $table->addColumn('charValue', 'float', ['notnull' => true]);
        $table->setPrimaryKey(['job_id', 'characteristic', 'createdAt']);
    }
}
