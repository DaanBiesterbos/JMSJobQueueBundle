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

namespace JMS\JobQueueTests\Functional\TestBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ThrowsExceptionCommand extends Command
{
    protected function configure()
    {
        $this->setName('jms-job-queue:throws-exception-cmd');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        var_dump('Throwing exception');
        throw new \RuntimeException('Something went wrong.');
    }
}
