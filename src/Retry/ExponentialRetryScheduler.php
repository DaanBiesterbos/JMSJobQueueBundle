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

namespace JMS\JobQueueBundle\Retry;

use JMS\JobQueueBundle\Entity\Job;

class ExponentialRetryScheduler implements RetryScheduler
{
    private $base;

    public function __construct($base = 5)
    {
        $this->base = $base;
    }

    public function scheduleNextRetry(Job $originalJob)
    {
        return new \DateTime('+'.(pow($this->base, count($originalJob->getRetryJobs()))).' seconds');
    }
}
