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

namespace JMS\JobQueueBundle\Entity\Type;

use Doctrine\DBAL\Types\ObjectType;

class SafeObjectType extends ObjectType
{
    public function getSQLDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getBlobTypeDeclarationSQL($fieldDeclaration);
    }

    public function getName()
    {
        return 'jms_job_safe_object';
    }
}
