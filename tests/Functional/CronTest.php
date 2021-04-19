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

namespace JMS\JobQueueTests\Functional;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

class CronTest extends BaseTestCase
{
    /** @var Application */
    private $app;

    /** @var EntityManager */
    private $em;

    public function testSchedulesCommands()
    {
        $output = $this->doRun(['--min-job-interval' => 1, '--max-runtime' => 12]);
        $this->assertSame(2, mb_substr_count($output, 'Scheduling command scheduled-every-few-seconds'), $output);
    }

    protected function setUp(): void
    {
        $this->createClient(['config' => 'persistent_db.yml']);

        if (is_file($databaseFile = self::$kernel->getCacheDir().'/database.sqlite')) {
            unlink($databaseFile);
        }

        $this->importDatabaseSchema();

        $this->app = new Application(self::$kernel);
        $this->app->setAutoExit(false);
        $this->app->setCatchExceptions(false);

        $this->em = self::$kernel->getContainer()->get('doctrine')->getManagerForClass('JMSJobQueueBundle:Job');
    }

    private function doRun(array $args = [])
    {
        array_unshift($args, 'jms-job-queue:schedule');
        $output = new MemoryOutput();
        $this->app->run(new ArrayInput($args), $output);

        return $output->getOutput();
    }
}
