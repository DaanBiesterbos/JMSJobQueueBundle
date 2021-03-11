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

namespace JMS\JobQueueBundle\Exception;

use JMS\JobQueueBundle\Entity\Job;

class InvalidStateTransitionException extends \InvalidArgumentException
{
    private $job;
    private $newState;
    private $allowedStates;

    public function __construct(Job $job, $newState, array $allowedStates = [])
    {
        $msg = sprintf('The Job(id = %d) cannot change from "%s" to "%s". Allowed transitions: ', $job->getId(), $job->getState(), $newState);
        $msg .= count($allowedStates) > 0 ? '"'.implode('", "', $allowedStates).'"' : '#none#';
        parent::__construct($msg);

        $this->job = $job;
        $this->newState = $newState;
        $this->allowedStates = $allowedStates;
    }

    public function getJob()
    {
        return $this->job;
    }

    public function getNewState()
    {
        return $this->newState;
    }

    public function getAllowedStates()
    {
        return $this->allowedStates;
    }
}
