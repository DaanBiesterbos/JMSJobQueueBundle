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

namespace JMS\JobQueueTests\Functional\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name = "wagons")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Wagon
{
    /** @ORM\Id @ORM\GeneratedValue(strategy = "AUTO") @ORM\Column(type = "integer") */
    public $id;

    /** @ORM\ManyToOne(targetEntity = "Train") */
    public $train;

    /** @ORM\Column(type = "string") */
    public $state = 'new';
}
