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

namespace JMS\JobQueueBundle\Exception;

/**
 * UnexpectedValueException for the JobQueueBundle.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class UnexpectedValueException extends \UnexpectedValueException implements Exception
{
}
