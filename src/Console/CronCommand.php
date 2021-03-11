<?php

declare(strict_types=1);

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

namespace JMS\JobQueueBundle\Console;

use JMS\JobQueueBundle\Entity\Job;

interface CronCommand
{
    /**
     * Returns the job when this command is scheduled.
     *
     * @return Job
     */
    public function createCronJob(\DateTime $lastRunAt): Job;

    /**
     * Returns whether this command should be scheduled.
     *
     * @return bool
     */
    public function shouldBeScheduled(\DateTime $lastRunAt): bool;
}
