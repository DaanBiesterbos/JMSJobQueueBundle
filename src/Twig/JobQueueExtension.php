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

namespace JMS\JobQueueBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class JobQueueExtension extends AbstractExtension
{
    private $linkGenerators = [];

    public function __construct(array $generators = [])
    {
        $this->linkGenerators = $generators;
    }

    public function getTests()
    {
        return [
            new TwigTest('jms_job_queue_linkable', [$this, 'isLinkable']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('jms_job_queue_path', [$this, 'generatePath'], ['is_safe' => ['html' => true]]),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('jms_job_queue_linkname', [$this, 'getLinkname']),
            new TwigFilter('jms_job_queue_args', [$this, 'formatArgs']),
        ];
    }

    public function formatArgs(array $args, $maxLength = 60)
    {
        $str = '';
        $first = true;
        foreach ($args as $arg) {
            $argLength = mb_strlen($arg);

            if (!$first) {
                $str .= ' ';
            }
            $first = false;

            if (mb_strlen($str) + $argLength > $maxLength) {
                $str .= mb_substr($arg, 0, $maxLength - mb_strlen($str) - 4).'...';
                break;
            }

            $str .= escapeshellarg($arg);
        }

        return $str;
    }

    public function isLinkable($entity)
    {
        foreach ($this->linkGenerators as $generator) {
            if ($generator->supports($entity)) {
                return true;
            }
        }

        return false;
    }

    public function generatePath($entity)
    {
        foreach ($this->linkGenerators as $generator) {
            if ($generator->supports($entity)) {
                return $generator->generate($entity);
            }
        }

        throw new \RuntimeException(sprintf('The entity "%s" has no link generator.', get_class($entity)));
    }

    public function getLinkname($entity)
    {
        foreach ($this->linkGenerators as $generator) {
            if ($generator->supports($entity)) {
                return $generator->getLinkname($entity);
            }
        }

        throw new \RuntimeException(sprintf('The entity "%s" has no link generator.', get_class($entity)));
    }

    public function getName()
    {
        return 'jms_job_queue';
    }
}
