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
use JMS\JobQueueBundle\Entity\Job;
use JMS\JobQueueBundle\Entity\Repository\JobManager;
use JMS\JobQueueBundle\Event\StateChangeEvent;
use JMS\JobQueueBundle\Retry\ExponentialRetryScheduler;
use JMS\JobQueueTests\Functional\TestBundle\Entity\Train;
use JMS\JobQueueTests\Functional\TestBundle\Entity\Wagon;
use PHPUnit\Framework\Constraint\LogicalNot;
use Symfony\Component\EventDispatcher\EventDispatcher;

class JobManagerTest extends BaseTestCase
{
    /** @var EntityManager */
    private $em;

    /** @var JobManager */
    private $jobManager;

    /** @var EventDispatcher */
    private $dispatcher;

    public function testGetOne()
    {
        $a = new Job('a', ['foo']);
        $a2 = new Job('a');
        $this->em->persist($a);
        $this->em->persist($a2);
        $this->em->flush();

        $this->assertSame($a, $this->jobManager->getJob('a', ['foo']));
        $this->assertSame($a2, $this->jobManager->getJob('a'));
    }

    public function testGetOneThrowsWhenNotFound()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Found no job for command');
        $this->jobManager->getJob('foo');
    }

    public function getOrCreateIfNotExists()
    {
        $a = $this->jobManager->getOrCreateIfNotExists('a');
        $this->assertSame($a, $this->jobManager->getOrCreateIfNotExists('a'));
        $this->assertNotSame($a, $this->jobManager->getOrCreateIfNotExists('a', ['foo']));
    }

    public function testFindPendingJobReturnsAllDependencies()
    {
        $a = new Job('a');
        $b = new Job('b');

        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->flush();

        $c = new Job('c');
        $c->addDependency($a);
        $c->addDependency($b);
        $this->em->persist($c);
        $this->em->flush();
        $this->em->clear();

        $cReloaded = $this->jobManager->findPendingJob([$a->getId(), $b->getId()]);
        $this->assertNotNull($cReloaded);
        $this->assertSame($c->getId(), $cReloaded->getId());
        $this->assertCount(2, $cReloaded->getDependencies());
    }

    public function testFindPendingJob()
    {
        $this->assertNull($this->jobManager->findPendingJob());

        $a = new Job('a');
        $a->setState('running');
        $b = new Job('b');
        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->flush();

        $this->assertSame($b, $this->jobManager->findPendingJob());
        $this->assertNull($this->jobManager->findPendingJob([$b->getId()]));
    }

    public function testFindPendingJobInRestrictedQueue()
    {
        $this->assertNull($this->jobManager->findPendingJob());

        $a = new Job('a');
        $b = new Job('b', [], true, 'other_queue');
        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->flush();

        $this->assertSame($a, $this->jobManager->findPendingJob());
        $this->assertSame($b, $this->jobManager->findPendingJob([], [], ['other_queue']));
    }

    public function testFindStartableJob()
    {
        $this->assertNull($this->jobManager->findStartableJob('my-name'));

        $a = new Job('a');
        $a->setState('running');
        $b = new Job('b');
        $c = new Job('c');
        $b->addDependency($c);
        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->persist($c);
        $this->em->flush();

        $excludedIds = [];

        $this->assertSame($c, $this->jobManager->findStartableJob('my-name', $excludedIds));
        $this->assertSame([$b->getId()], $excludedIds);
    }

    public function testFindJobByRelatedEntity()
    {
        $a = new Job('a');
        $b = new Job('b');
        $b->addRelatedEntity($a);
        $b2 = new Job('b');
        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->persist($b2);
        $this->em->flush();
        $this->em->clear();

        $this->assertFalse($this->em->contains($b));

        $reloadedB = $this->jobManager->findJobForRelatedEntity('b', $a);
        $this->assertNotNull($reloadedB);
        $this->assertSame($b->getId(), $reloadedB->getId());
        $this->assertCount(1, $reloadedB->getRelatedEntities());
        $this->assertSame($a->getId(), $reloadedB->getRelatedEntities()->first()->getId());
    }

    public function testFindStartableJobDetachesNonStartableJobs()
    {
        $a = new Job('a');
        $b = new Job('b');
        $a->addDependency($b);
        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->flush();

        $this->assertTrue($this->em->contains($a));
        $this->assertTrue($this->em->contains($b));

        $excludedIds = [];
        $startableJob = $this->jobManager->findStartableJob('my-name', $excludedIds);
        $this->assertNotNull($startableJob);
        $this->assertSame($b->getId(), $startableJob->getId());
        $this->assertSame([$a->getId()], $excludedIds);
        $this->assertFalse($this->em->contains($a));
        $this->assertTrue($this->em->contains($b));
    }

    public function testCloseJob()
    {
        $a = new Job('a');
        $a->setState('running');
        $b = new Job('b');
        $b->addDependency($a);
        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->flush();

        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [new StateChangeEvent($a, 'terminated'), 'jms_job_queue.job_state_change'],
                [new StateChangeEvent($b, 'canceled'), 'jms_job_queue.job_state_change'],
            );

        $this->assertSame('running', $a->getState());
        $this->assertSame('pending', $b->getState());
        $this->jobManager->closeJob($a, 'terminated');
        $this->assertSame('terminated', $a->getState());
        $this->assertSame('canceled', $b->getState());
    }

    public function testCloseJobDoesNotCreateRetryJobsWhenCanceled()
    {
        $a = new Job('a');
        $a->setMaxRetries(4);
        $b = new Job('b');
        $b->setMaxRetries(4);
        $b->addDependency($a);
        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->flush();

        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [new StateChangeEvent($a, 'canceled'), 'jms_job_queue.job_state_change'],
                [new StateChangeEvent($b, 'canceled'), 'jms_job_queue.job_state_change'],
            );

        $this->jobManager->closeJob($a, 'canceled');
        $this->assertSame('canceled', $a->getState());
        $this->assertCount(0, $a->getRetryJobs());
        $this->assertSame('canceled', $b->getState());
        $this->assertCount(0, $b->getRetryJobs());
    }

    public function testCloseJobDoesNotCreateMoreThanAllowedRetries()
    {
        $a = new Job('a');
        $a->setMaxRetries(2);
        $a->setState('running');
        $this->em->persist($a);
        $this->em->flush();

        $this->dispatcher->expects(self::exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [new StateChangeEvent($a, 'failed'), 'jms_job_queue.job_state_change'],
                [new LogicalNot($this->equalTo(new StateChangeEvent($a, 'failed'))), 'jms_job_queue.job_state_change'],
                [new LogicalNot($this->equalTo(new StateChangeEvent($a, 'failed'))), 'jms_job_queue.job_state_change'],
            );

        $this->assertCount(0, $a->getRetryJobs());
        $this->jobManager->closeJob($a, 'failed');
        $this->assertSame('running', $a->getState());
        $this->assertCount(1, $a->getRetryJobs());

        $a->getRetryJobs()->first()->setState('running');
        $this->jobManager->closeJob($a->getRetryJobs()->first(), 'failed');
        $this->assertCount(2, $a->getRetryJobs());
        $this->assertSame('failed', $a->getRetryJobs()->first()->getState());
        $this->assertSame('running', $a->getState());

        $a->getRetryJobs()->last()->setState('running');
        $this->jobManager->closeJob($a->getRetryJobs()->last(), 'terminated');
        $this->assertCount(2, $a->getRetryJobs());
        $this->assertSame('terminated', $a->getRetryJobs()->last()->getState());
        $this->assertSame('terminated', $a->getState());

        $this->em->clear();
        $reloadedA = $this->em->find('JMSJobQueueBundle:Job', $a->getId());
        $this->assertCount(2, $reloadedA->getRetryJobs());
    }

    public function testModifyingRelatedEntity()
    {
        $wagon = new Wagon();
        $train = new Train();
        $wagon->train = $train;

        $defEm = self::$kernel->getContainer()->get('doctrine')->getManager('default');
        $defEm->persist($wagon);
        $defEm->persist($train);
        $defEm->flush();

        $j = new Job('j');
        $j->addRelatedEntity($wagon);
        $this->em->persist($j);
        $this->em->flush();

        $defEm->clear();
        $this->em->clear();
        $this->assertNotSame($defEm, $this->em);

        $reloadedJ = $this->em->find('JMSJobQueueBundle:Job', $j->getId());

        $reloadedWagon = $reloadedJ->findRelatedEntity('JMS\JobQueueTests\Functional\TestBundle\Entity\Wagon');
        $reloadedWagon->state = 'broken';
        $defEm->persist($reloadedWagon);
        $defEm->flush();

        $this->assertTrue($defEm->contains($reloadedWagon->train));
    }

    protected function setUp(): void
    {
        $this->createClient();
        $this->importDatabaseSchema();

        $this->dispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->em = self::$kernel->getContainer()->get('doctrine')->getManagerForClass(Job::class);
        $this->jobManager = new JobManager(
            self::$kernel->getContainer()->get('doctrine'),
            $this->dispatcher,
            new ExponentialRetryScheduler()
        );
    }
}
