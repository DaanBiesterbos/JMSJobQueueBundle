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

interface RetryScheduler
{
    /**
     * Schedules the next retry of a job.
     *
     * When this method is called, it has already been decided that a retry should be attempted. The implementation
     * should needs to decide when that should happen.
     *
     * @return \DateTime
     */
    public function scheduleNextRetry(Job $originalJob);
}
