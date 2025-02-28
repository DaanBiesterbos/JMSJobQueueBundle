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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class SignalTest extends TestCase
{
    public function testControlledExit()
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('PCNTL extension is not loaded.');
        }

        $proc = new Process('exec '.PHP_BINARY.' '.escapeshellarg(__DIR__.'/console').' jms-job-queue:run --worker-name=test --verbose --max-runtime=999999');
        $proc->start();

        usleep(5E5);

        $this->assertTrue($proc->isRunning(), 'Process exited prematurely: '.$proc->getOutput().$proc->getErrorOutput());
        $this->assertTrueWithin(
            3,
            function () use ($proc) { return false !== mb_strpos($proc->getOutput(), 'Signal Handlers have been installed'); },
            function () use ($proc) {
                $this->fail('Signal handlers were not installed: '.$proc->getOutput().$proc->getErrorOutput());
            }
        );

        $proc->signal(SIGTERM);

        $this->assertTrueWithin(
            3,
            function () use ($proc) { return false !== mb_strpos($proc->getOutput(), 'Received SIGTERM'); },
            function () use ($proc) {
                $this->fail('Signal was not received by process within 3 seconds: '.$proc->getOutput().$proc->getErrorOutput());
            }
        );

        $this->assertTrueWithin(
            3,
            function () use ($proc) { return !$proc->isRunning(); },
            function () use ($proc) {
                $this->fail('Process did not terminate within 3 seconds: '.$proc->getOutput().$proc->getErrorOutput());
            }
        );

        $this->assertStringContainsString('All jobs finished, exiting.', $proc->getOutput());
    }

    private function assertTrueWithin($seconds, callable $block, callable $failureHandler)
    {
        $start = microtime(true);
        while (true) {
            if ($block()) {
                break;
            }

            if (microtime(true) - $start >= $seconds) {
                $failureHandler();
                $this->fail('Failure handler did not raise an exception.');
            }

            usleep(2E5);
        }
    }
}
