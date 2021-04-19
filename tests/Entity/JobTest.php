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

namespace JMS\JobQueueTests\Entity;

use JMS\JobQueueBundle\Entity\Job;
use JMS\JobQueueBundle\Exception\InvalidStateTransitionException;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    public function testConstruct()
    {
        $job = new Job('a:b', ['a', 'b', 'c']);

        $this->assertSame('a:b', $job->getCommand());
        $this->assertSame(['a', 'b', 'c'], $job->getArgs());
        $this->assertNotNull($job->getCreatedAt());
        $this->assertSame('pending', $job->getState());
        $this->assertNull($job->getStartedAt());

        return $job;
    }

    /**
     * @depends testConstruct
     */
    public function testInvalidTransition(Job $job)
    {
        $this->expectException(InvalidStateTransitionException::class);
        $job->setState('failed');
    }

    /**
     * @depends testConstruct
     */
    public function testStateToRunning(Job $job)
    {
        $job->setState('running');
        $this->assertSame('running', $job->getState());
        $this->assertNotNull($startedAt = $job->getStartedAt());
        $job->setState('running');
        $this->assertSame($startedAt, $job->getStartedAt());

        return $job;
    }

    /**
     * @depends testStateToRunning
     */
    public function testStateToFailed(Job $job)
    {
        $job = clone $job;
        $job->setState('running');
        $job->setState('failed');
        $this->assertSame('failed', $job->getState());
    }

    /**
     * @depends testStateToRunning
     */
    public function testStateToTerminated(Job $job)
    {
        $job = clone $job;
        $job->setState('running');
        $job->setState('terminated');
        $this->assertSame('terminated', $job->getState());
    }

    /**
     * @depends testStateToRunning
     */
    public function testStateToFinished(Job $job)
    {
        $job = clone $job;
        $job->setState('running');
        $job->setState('finished');
        $this->assertSame('finished', $job->getState());
    }

    public function testAddOutput()
    {
        $job = new Job('foo');
        $this->assertNull($job->getOutput());
        $job->addOutput('foo');
        $this->assertSame('foo', $job->getOutput());
        $job->addOutput('bar');
        $this->assertSame('foobar', $job->getOutput());
    }

    public function testAddErrorOutput()
    {
        $job = new Job('foo');
        $this->assertNull($job->getErrorOutput());
        $job->addErrorOutput('foo');
        $this->assertSame('foo', $job->getErrorOutput());
        $job->addErrorOutput('bar');
        $this->assertSame('foobar', $job->getErrorOutput());
    }

    public function testSetOutput()
    {
        $job = new Job('foo');
        $this->assertNull($job->getOutput());
        $job->setOutput('foo');
        $this->assertSame('foo', $job->getOutput());
        $job->setOutput('bar');
        $this->assertSame('bar', $job->getOutput());
    }

    public function testSetErrorOutput()
    {
        $job = new Job('foo');
        $this->assertNull($job->getErrorOutput());
        $job->setErrorOutput('foo');
        $this->assertSame('foo', $job->getErrorOutput());
        $job->setErrorOutput('bar');
        $this->assertSame('bar', $job->getErrorOutput());
    }

    public function testAddDependency()
    {
        $a = new Job('a');
        $b = new Job('b');
        $this->assertCount(0, $a->getDependencies());
        $this->assertCount(0, $b->getDependencies());

        $a->addDependency($b);
        $this->assertCount(1, $a->getDependencies());
        $this->assertCount(0, $b->getDependencies());
        $this->assertSame($b, $a->getDependencies()->first());
    }

    public function testAddDependencyToRunningJob()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You cannot add dependencies to a job which might have been started already.');
        $job = new Job('a');
        $job->setState(Job::STATE_RUNNING);
        $this->setField($job, 'id', 1);
        $job->addDependency(new Job('b'));
    }

    public function testAddRetryJob()
    {
        $a = new Job('a');
        $a->setState(Job::STATE_RUNNING);
        $b = new Job('b');
        $a->addRetryJob($b);

        $this->assertCount(1, $a->getRetryJobs());
        $this->assertSame($b, $a->getRetryJobs()->get(0));

        return $a;
    }

    /**
     * @depends testAddRetryJob
     */
    public function testIsRetryJob(Job $a)
    {
        $this->assertFalse($a->isRetryJob());
        $this->assertTrue($a->getRetryJobs()->get(0)->isRetryJob());
    }

    /**
     * @depends testAddRetryJob
     */
    public function testGetOriginalJob(Job $a)
    {
        $this->assertSame($a, $a->getOriginalJob());
        $this->assertSame($a, $a->getRetryJobs()->get(0)->getOriginalJob());
    }

    public function testCheckedAt()
    {
        $job = new Job('a');
        $this->assertNull($job->getCheckedAt());

        $job->checked();
        $this->assertInstanceOf('DateTime', $checkedAtA = $job->getCheckedAt());

        $job->checked();
        $this->assertInstanceOf('DateTime', $checkedAtB = $job->getCheckedAt());
        $this->assertNotSame($checkedAtA, $checkedAtB);
    }

    public function testSameDependencyIsNotAddedTwice()
    {
        $a = new Job('a');
        $b = new Job('b');

        $this->assertCount(0, $a->getDependencies());
        $a->addDependency($b);
        $this->assertCount(1, $a->getDependencies());
        $a->addDependency($b);
        $this->assertCount(1, $a->getDependencies());
    }

    public function testHasDependency()
    {
        $a = new Job('a');
        $b = new Job('b');

        $this->assertFalse($a->hasDependency($b));
        $a->addDependency($b);
        $this->assertTrue($a->hasDependency($b));
    }

    public function testIsRetryAllowed()
    {
        $job = new Job('a');
        $this->assertFalse($job->isRetryAllowed());

        $job->setMaxRetries(1);
        $this->assertTrue($job->isRetryAllowed());

        $job->setState('running');
        $retry = new Job('a');
        $job->addRetryJob($retry);
        $this->assertFalse($job->isRetryAllowed());
    }

    public function testCloneDoesNotChangeQueue()
    {
        $job = new Job('a', [], true, 'foo');
        $clonedJob = clone $job;

        $this->assertSame('foo', $clonedJob->getQueue());
    }

    private function setField($obj, $field, $value)
    {
        $ref = new \ReflectionProperty($obj, $field);
        $ref->setAccessible(true);
        $ref->setValue($obj, $value);
    }
}
