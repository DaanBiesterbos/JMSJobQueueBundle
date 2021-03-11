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

namespace JMS\JobQueueBundle\Cron;

use JMS\JobQueueBundle\Entity\Job;

interface JobScheduler
{
    /**
     * Returns an array of commands managed by this scheduler.
     *
     * @return string[]
     */
    public function getCommands(): array;

    /**
     * Returns whether to schedule the given command again.
     *
     * @return bool
     */
    public function shouldSchedule(string $command, \DateTime $lastRunAt): bool;

    /**
     * Creates the given command when it is scheduled.
     *
     * @param string    $command
     * @param \DateTime $lastRunAt
     *
     * @return Job
     */
    public function createJob(string $command, \DateTime $lastRunAt): Job;
}
