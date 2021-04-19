JMSJobQueueBundle
=================
[![Build](https://github.com/DaanBiesterbos/JMSJobQueueBundle/actions/workflows/build.yaml/badge.svg)](https://github.com/DaanBiesterbos/JMSJobQueueBundle/actions/workflows/build.yaml)
[![Latest Stable Version](https://poser.pugx.org/daanbiesterbos/job-queue-bundle/v/stable.svg)](https://packagist.org/packages/daanbiesterbos/job-queue-bundle)
[![Total Downloads](https://poser.pugx.org/daanbiesterbos/job-queue-bundle/downloads.svg)](https://packagist.org/packages/daanbiesterbos/job-queue-bundle)

# Installation

```bash
composer require daanbiesterbos/job-queue-bundle
```

## Register Bundle

Add the bundle to bundles.php:
```php 
JMS\JobQueueBundle\JMSJobQueueBundle::class => ['all' => true],
```

## Prepare Console Executable

Copy `bin/console` to `bin/job-queue` and use the JMSJobQueueBundle application instead the standard Symfony application.

```bash 
#
# Copy the console executable
#

cp bin/console bin/job-queue

#
# Open bin/job-queue in a text editor
#
# Look for this line:
# use Symfony\Bundle\FrameworkBundle\Console\Application;
#
# And change it to:
# use JMS\JobQueueBundle\Console\Application;

vim bin/job-queue
```

Note that this is part of the original bundle. I would prefer a better solution that does not require an extra console. However, for now backward compatibility is more important considering the purpose of the fork.


# Usage

## Creating Jobs

Creating jobs is simple, you just need to persist an instance of `Job`:
```php
    $em->persist($job);
    $em->flush($job);
```


## Adding Dependencies Between Jobs

If you want to have a job run after another job finishes, you can also achieve this quite easily:
```php
    $job->addDependency($job);
    $em->persist($job);
    $em->persist($dependentJob);
    $em->flush();
```


## Schedule Job

If you want to schedule a job :
```php
    $job->add(new DateInterval('PT30M'));
    $job->setExecuteAfter($date);
    $em->persist($job);
    $em->flush();
```


## Fine-grained Concurrency Control through Queues

If you would like to better control the concurrency of a specific job type, you can use queues:
Queues allow you to enforce stricter limits as to how many jobs are running per queue. By default, the number of jobs per queue is not limited as such queues will have no effect (jobs would just be processed in the order that they were created in). To define a limit for a queue, you can use the bundle?s configuration:
```yaml
    jms_job_queue:
        queue_options_defaults:
            max_concurrent_jobs: 3 # This limit applies to all queues (including the default queue).
                                   # So each queue may only process 3 jobs simultaneously.
        queue_options:
            foo:
                max_concurrent_jobs: 2 # This limit applies only to the "foo" queue.
```


__ **Note: **Queue settings apply for each instance of the `jms-job-queue:run` command separately. There is no way to specify a global limit for all instances.

## Prioritizing Jobs

By default, all jobs are executed in the order in which they are scheduled (assuming they are in the same queue). If you would like to prioritize certain jobs in the same queue, you can set a priority:
```php
$job = new Job('a', array(), true, Job::DEFAULT_QUEUE, Job::PRIORITY_HIGH);
$em->persist($job);
$em->flush();
```


The priority is a simple integer - the higher the number, the sooner a job is executed.

# Scheduled Jobs - JMSJobQueueBundle Documentation

This bundle also allows you to have scheduled jobs which are executed in certain intervals. This can either be achieved by implementing `JMSJobQueueBundleCommandCronCommand` in your command, or implementing `JMSJobQueueBundleCronJobScheduler` in a service and tagging the service with `jms_job_queue.scheduler`.
The jobs are then scheduled with the `jms-job-queue:schedule` command that is run as an additional background process. You can also run multiple instances of this command to ensure high availability and avoid a single point of failure.

## Implement CronCommand
```php
    class MyScheduledCommand extends Command implements CronCommand
    {
        // configure, execute, etc. ...
    
        public function shouldBeScheduled(DateTime $lastRunAt)
        {
            return time() - $lastRunAt->getTimestamp() >= 60; // Executed at most every minute.
        }
    
        public function createCronJob(DateTime $lastRunAt)
        {
            return new Job('my-scheduled-command');
        }
    }
```

For common intervals, you can also use one of the provided traits:
```php 
    class MyScheduledCommand extends ContainerAwareCommand implements CronCommand
    {
        use ScheduleEveryMinute;
    
        // ...
    }
```


## Implement JobScheduler

This is useful if you want to run a third-party command or a Symfony command as a scheduled command via this bundle.
```php
    class MyJobScheduler implements JobScheduler
    {
        public function getCommands(): array
        {
            return ['my-command'];
        }
    
        public function shouldSchedule($commandName, DateTime $lastRunAt)
        {
            return time() - $lastRunAt->getTimestamp() >= 60; // Executed at most every minute.
        }
    
        public function createJob($commandName, DateTime $lastRunAt)
        {
            return new Job('my-command');
        }
    }
```
## Links
- Documentation: [Github Repository](https://github.com/DaanBiesterbos/JMSJobQueueBundle)
- License:  [LICENSE](https://raw.githubusercontent.com/DaanBiesterbos/JMSJobQueueBundle/master/LICENSE)
- Forked from:  [JMSJobQueueBundle](https://github.com/schmittjoh/JMSJobQueueBundle)
