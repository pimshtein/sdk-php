<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Client\Transport;

use React\Promise\PromiseInterface;
use Temporal\Client\Transport\Protocol\Command\RequestInterface;

/**
 * @psalm-type OnMessageHandler = callable(RequestInterface, array): PromiseInterface
 *
 * @see RequestInterface
 * @see PromiseInterface
 */
interface ServerInterface
{
    /**
     * @psalm-param OnMessageHandler $then
     * @param callable $then
     */
    public function onMessage(callable $then): void;
}
