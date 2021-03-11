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

use JMS\JobQueueBundle\Console\CronCommand;
use JMS\JobQueueBundle\Entity\Job;

class CommandScheduler implements JobScheduler
{
    private $name;
    private $command;

    public function __construct(string $name, CronCommand $command)
    {
        $this->name = $name;
        $this->command = $command;
    }

    public function getCommands(): array
    {
        return [$this->name];
    }

    public function shouldSchedule(string $_, \DateTime $lastRunAt): bool
    {
        return $this->command->shouldBeScheduled($lastRunAt);
    }

    public function createJob(string $_, \DateTime $lastRunAt): Job
    {
        return $this->command->createCronJob($lastRunAt);
    }
}
