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
use Temporal\Client\Transport\Protocol\Command\ErrorResponse;
use Temporal\Client\Transport\Protocol\Command\RequestInterface;
use Temporal\Client\Transport\Protocol\Command\SuccessResponse;
use Temporal\Client\Transport\Queue\QueueInterface;

/**
 * @psalm-import-type OnMessageHandler from ServerInterface
 */
final class Server implements ServerInterface
{
    /**
     * @var string
     */
    private const ERROR_INVALID_RETURN_TYPE = 'Request handler must return an instance of \%s, but returned %s';

    /**
     * @var string
     */
    private const ERROR_INVALID_REJECTION_TYPE =
        'An internal error has occurred: ' .
        'Promise rejection must contain an instance of \Throwable, however %s is given';

    /**
     * @var \Closure
     */
    private \Closure $onMessage;

    /**
     * @var QueueInterface
     */
    private QueueInterface $queue;

    /**
     * @psalm-param OnMessageHandler $onMessage
     *
     * @param QueueInterface $queue
     * @param \Closure $onMessage
     */
    public function __construct(QueueInterface $queue, callable $onMessage)
    {
        $this->queue = $queue;

        $this->onMessage($onMessage);
    }

    /**
     * {@inheritDoc}
     */
    public function onMessage(callable $then): void
    {
        $this->onMessage = \Closure::fromCallable($then);
    }

    /**
     * @param RequestInterface $request
     * @param array $headers
     */
    public function dispatch(RequestInterface $request, array $headers): void
    {
        try {
            $result = ($this->onMessage)($request, $headers);
        } catch (\Throwable $e) {
            $this->queue->push(ErrorResponse::fromException($e, $request->getId()));

            return;
        }

        if (! $result instanceof PromiseInterface) {
            $error = \sprintf(self::ERROR_INVALID_RETURN_TYPE, PromiseInterface::class, \get_debug_type($result));
            throw new \BadMethodCallException($error);
        }

        $result->then($this->onFulfilled($request), $this->onRejected($request));
    }

    /**
     * @param RequestInterface $request
     * @return \Closure
     */
    private function onFulfilled(RequestInterface $request): \Closure
    {
        return function ($result) use ($request) {
            $response = new SuccessResponse($result, $request->getId());

            $this->queue->push($response);

            return $response;
        };
    }

    /**
     * @param RequestInterface $request
     * @return \Closure
     */
    private function onRejected(RequestInterface $request): \Closure
    {
        return function ($result) use ($request) {
            if (! $result instanceof \Throwable) {
                $result = new \InvalidArgumentException(
                    \sprintf(self::ERROR_INVALID_REJECTION_TYPE, \get_debug_type($result))
                );
            }

            $response = ErrorResponse::fromException($result, $request->getId());

            $this->queue->push($response);

            return $response;
        };
    }
}
