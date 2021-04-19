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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoggingCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('jms-job-queue:logging-cmd')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of this job.')
            ->addArgument('file', InputArgument::REQUIRED, 'The file to log to.')
            ->addOption('runtime', null, InputOption::VALUE_REQUIRED, 'The runtime of this command', 3)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        file_put_contents($input->getArgument('file'), $input->getArgument('name').' started'.PHP_EOL, FILE_APPEND);
        sleep($input->getOption('runtime'));
        file_put_contents($input->getArgument('file'), $input->getArgument('name').' stopped'.PHP_EOL, FILE_APPEND);
    }
}
