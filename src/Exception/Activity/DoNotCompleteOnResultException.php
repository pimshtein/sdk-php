<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Temporal\Client\Exception\Activity;

use Temporal\Client\Exception\NonThrowableExceptionInterface;
use Temporal\Client\Exception\TemporalException;

class DoNotCompleteOnResultException extends TemporalException implements NonThrowableExceptionInterface
{
    /**
     * @var string
     */
    protected const DEFAULT_ERROR_MESSAGE = 'doNotCompleteOnReturn';

    /**
     * @return static
     */
    public static function create(): self
    {
        return new static(static::DEFAULT_ERROR_MESSAGE);
    }
}
