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

use JMS\JobQueueBundle\Entity\Job;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\Output;

class RunCommandTest extends BaseTestCase
{
    private $app;
    private $em;

    public function testRun()
    {
        $a = new Job('adoigjaoisdjfijasodifjoiajsdf');
        $b = new Job('b', ['foo']);
        $b->addDependency($a);
        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->flush();

        $output = $this->runConsoleCommand(['--max-runtime' => 5, '--worker-name' => 'test']);
        $expectedOutput = "Started Job(id = 1, command = \"adoigjaoisdjfijasodifjoiajsdf\").\n"
                         ."Job(id = 1, command = \"adoigjaoisdjfijasodifjoiajsdf\") finished with exit code 1.\n";
        $this->assertSame($expectedOutput, $output);
        $this->assertSame('failed', $a->getState());
        $this->assertSame('', $a->getOutput());
        $this->assertStringContainsString('Command "adoigjaoisdjfijasodifjoiajsdf" is not defined.', $a->getErrorOutput());
        $this->assertSame('canceled', $b->getState());
    }

    public function testExitsAfterMaxRuntime()
    {
        $time = time();
        $output = $this->runConsoleCommand(['--max-runtime' => 1, '--worker-name' => 'test']);
        $this->assertSame('', $output);

        $runtime = time() - $time;
        $this->assertTrue($runtime >= 2 && $runtime < 8);
    }

    public function testSuccessfulCommand()
    {
        $job = new Job('jms-job-queue:successful-cmd');
        $this->em->persist($job);
        $this->em->flush($job);

        $this->runConsoleCommand(['--max-runtime' => 1, '--worker-name' => 'test']);
        $this->assertSame('finished', $job->getState());
    }

    /**
     * @group queues
     */
    public function testQueueWithLimitedConcurrentJobs()
    {
        $outputFile = tempnam(sys_get_temp_dir(), 'job-output');
        for ($i = 0; $i < 4; ++$i) {
            $job = new Job('jms-job-queue:logging-cmd', ['Job'.$i, $outputFile, '--runtime=1']);
            $this->em->persist($job);
        }

        $this->em->flush();

        $this->runConsoleCommand(['--max-runtime' => 15, '--worker-name' => 'test']);

        $output = file_get_contents($outputFile);
        unlink($outputFile);

        $this->assertSame(<<<OUTPUT
Job0 started
Job0 stopped
Job1 started
Job1 stopped
Job2 started
Job2 stopped
Job3 started
Job3 stopped

OUTPUT
            ,
            $output
        );
    }

    /**
     * @group queues
     */
    public function testQueueWithMoreThanOneConcurrentJob()
    {
        $outputFile = tempnam(sys_get_temp_dir(), 'job-output');
        for ($i = 0; $i < 3; ++$i) {
            $job = new Job('jms-job-queue:logging-cmd', ['Job'.$i, $outputFile, '--runtime=4'], true, 'foo');
            $this->em->persist($job);
        }
        $this->em->flush();

        $output = $this->runConsoleCommand(['--max-runtime' => 15, '--worker-name' => 'test']);
        unlink($outputFile);

        $this->assertStringStartsWith(<<<OUTPUT
Started Job(id = 1, command = "jms-job-queue:logging-cmd").
Started Job(id = 2, command = "jms-job-queue:logging-cmd").
OUTPUT
            ,
            $output
        );

        $this->assertStringStartsNotWith(<<<OUTPUT
Started Job(id = 1, command = "jms-job-queue:logging-cmd").
Started Job(id = 2, command = "jms-job-queue:logging-cmd").
Started Job(id = 3, command = "jms-job-queue:logging-cmd").
OUTPUT
            ,
            $output
        );
    }

    /**
     * @group queues
     */
    public function testSingleRestrictedQueue()
    {
        $a = new Job('jms-job-queue:successful-cmd');
        $b = new Job('jms-job-queue:successful-cmd', [], true, 'other_queue');
        $c = new Job('jms-job-queue:successful-cmd', [], true, 'yet_another_queue');
        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->persist($c);
        $this->em->flush();

        $this->runConsoleCommand(['--max-runtime' => 1, '--queue' => ['other_queue'], '--worker-name' => 'test']);
        $this->assertSame(Job::STATE_PENDING, $a->getState());
        $this->assertSame(Job::STATE_FINISHED, $b->getState());
        $this->assertSame(Job::STATE_PENDING, $c->getState());
    }

    /**
     * @group queues
     */
    public function testMultipleRestrictedQueues()
    {
        $a = new Job('jms-job-queue:successful-cmd');
        $b = new Job('jms-job-queue:successful-cmd', [], true, 'other_queue');
        $c = new Job('jms-job-queue:successful-cmd', [], true, 'yet_another_queue');
        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->persist($c);
        $this->em->flush();

        $this->runConsoleCommand(['--max-runtime' => 1, '--queue' => ['other_queue', 'yet_another_queue'], '--worker-name' => 'test']);
        $this->assertSame(Job::STATE_PENDING, $a->getState());
        $this->assertSame(Job::STATE_FINISHED, $b->getState());
        $this->assertSame(Job::STATE_FINISHED, $c->getState());
    }

    /**
     * @group queues
     */
    public function testNoRestrictedQueue()
    {
        $a = new Job('jms-job-queue:successful-cmd');
        $b = new Job('jms-job-queue:successful-cmd', [], true, 'other_queue');
        $c = new Job('jms-job-queue:successful-cmd', [], true, 'yet_another_queue');
        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->persist($c);
        $this->em->flush();

        $this->runConsoleCommand(['--max-runtime' => 1, '--worker-name' => 'test']);
        $this->assertSame(Job::STATE_FINISHED, $a->getState());
        $this->assertSame(Job::STATE_FINISHED, $b->getState());
        $this->assertSame(Job::STATE_FINISHED, $c->getState());
    }

    /**
     * @group retry
     */
    public function testRetry()
    {
        $job = new Job('jms-job-queue:sometimes-failing-cmd', [time()]);
        $job->setMaxRetries(5);
        $this->em->persist($job);
        $this->em->flush($job);

        $this->runConsoleCommand(['--max-runtime' => 12, '--verbose' => null, '--worker-name' => 'test']);

        $this->assertSame('finished', $job->getState());
        $this->assertGreaterThan(0, count($job->getRetryJobs()));
        $this->assertSame(1, $job->getExitCode());
    }

    public function testJobIsTerminatedIfMaxRuntimeIsExceeded()
    {
        $job = new Job('jms-job-queue:never-ending');
        $job->setMaxRuntime(1);
        $this->em->persist($job);
        $this->em->flush($job);

        $this->runConsoleCommand(['--max-runtime' => 1, '--worker-name' => 'test']);
        $this->assertSame('terminated', $job->getState());
    }

    /**
     * @group priority
     */
    public function testJobsWithHigherPriorityAreStartedFirst()
    {
        $job = new Job('jms-job-queue:successful-cmd');
        $this->em->persist($job);

        $job = new Job('jms-job-queue:successful-cmd', [], true, Job::DEFAULT_QUEUE, Job::PRIORITY_HIGH);
        $this->em->persist($job);
        $this->em->flush();

        $output = $this->runConsoleCommand(['--max-runtime' => 4, '--worker-name' => 'test']);

        $this->assertSame(<<<OUTPUT
Started Job(id = 2, command = "jms-job-queue:successful-cmd").
Job(id = 2, command = "jms-job-queue:successful-cmd") finished with exit code 0.
Started Job(id = 1, command = "jms-job-queue:successful-cmd").
Job(id = 1, command = "jms-job-queue:successful-cmd") finished with exit code 0.

OUTPUT
            ,
            $output
        );
    }

    /**
     * @group priority
     */
    public function testJobsAreStartedInCreationOrderWhenPriorityIsEqual()
    {
        $job = new Job('jms-job-queue:successful-cmd', [], true, Job::DEFAULT_QUEUE, Job::PRIORITY_HIGH);
        $this->em->persist($job);

        $job = new Job('jms-job-queue:successful-cmd', [], true, Job::DEFAULT_QUEUE, Job::PRIORITY_HIGH);
        $this->em->persist($job);
        $this->em->flush();

        $output = $this->runConsoleCommand(['--max-runtime' => 4, '--worker-name' => 'test']);

        $this->assertSame(<<<OUTPUT
Started Job(id = 1, command = "jms-job-queue:successful-cmd").
Job(id = 1, command = "jms-job-queue:successful-cmd") finished with exit code 0.
Started Job(id = 2, command = "jms-job-queue:successful-cmd").
Job(id = 2, command = "jms-job-queue:successful-cmd") finished with exit code 0.

OUTPUT
            ,
            $output
        );
    }

    /**
     * @group exception
     */
    public function testExceptionStackTraceIsSaved()
    {
        $job = new Job('jms-job-queue:throws-exception-cmd');
        $this->em->persist($job);
        $this->em->flush($job);

        $this->assertNull($job->getStackTrace());
        $this->assertNull($job->getMemoryUsage());
        $this->assertNull($job->getMemoryUsageReal());

        $this->runConsoleCommand(['--max-runtime' => 1, '--worker-name' => 'test']);

        $this->assertNotNull($job->getStackTrace());
        $this->assertNotNull($job->getMemoryUsage());
        $this->assertNotNull($job->getMemoryUsageReal());
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

    private function runConsoleCommand(array $args = [])
    {
        array_unshift($args, 'jms-job-queue:run');
        $output = new MemoryOutput();

        $_SERVER['SYMFONY_CONSOLE_FILE'] = __DIR__.'/console';
        $this->app->run(new ArrayInput($args), $output);

        return $output->getOutput();
    }
}

class MemoryOutput extends Output
{
    private $output = '';

    public function getOutput(): string
    {
        return $this->output;
    }

    protected function doWrite($message, $newline)
    {
        $this->output .= $message;

        if ($newline) {
            $this->output .= "\n";
        }
    }
}
