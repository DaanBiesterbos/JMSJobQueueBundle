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

namespace JMS\JobQueueBundle\View;

use Symfony\Component\HttpFoundation\Request;

class JobFilter
{
    public $page;
    public $command;
    public $state;

    public static function fromRequest(Request $request)
    {
        $filter = new self();
        $filter->page = $request->query->getInt('page', 1);
        $filter->command = $request->query->get('command');
        $filter->state = $request->query->get('state');

        return $filter;
    }

    public function isDefaultPage()
    {
        return 1 === $this->page && empty($this->command) && empty($this->state);
    }

    public function toArray()
    {
        return [
            'page' => $this->page,
            'command' => $this->command,
            'state' => $this->state,
        ];
    }
}
