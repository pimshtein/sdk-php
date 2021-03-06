<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Client\Transport;

use React\Promise\CancellablePromiseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Temporal\Client\Worker\WorkerInterface;

class Future implements FutureInterface
{
    /**
     * @var bool
     */
    private bool $resolved = false;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var CancellablePromiseInterface
     */
    private CancellablePromiseInterface $promise;

    /**
     * @var Deferred
     */
    private Deferred $deferred;

    /**
     * @var WorkerInterface
     */
    private WorkerInterface $worker;

    /**
     * Future constructor.
     *
     * @param CancellablePromiseInterface $promise
     * @param WorkerInterface             $worker
     */
    public function __construct(CancellablePromiseInterface $promise, WorkerInterface $worker)
    {
        $this->worker = $worker;
        $this->deferred = new Deferred(function () use ($promise) {
            $promise->cancel();
        });

        /** @var CancellablePromiseInterface $current */
        $current = $promise->then(
            \Closure::fromCallable([$this, 'onFulfilled']),
            \Closure::fromCallable([$this, 'onRejected']),
        );

        $this->promise = $current;
    }

    /**
     * {@inheritDoc}
     */
    public function isComplete(): bool
    {
        return $this->resolved;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function cancel(): void
    {
        $this->promise->cancel();
    }

    /**
     * {@inheritDoc}
     */
    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null,
        callable $onProgress = null
    ): PromiseInterface {
        /** @var CancellablePromiseInterface $promise */
        $promise = $this->promise()
                        ->then($onFulfilled, $onRejected, $onProgress);

        return $promise;
        //return new Future($promise, $this->worker);
    }

    /**
     * @return PromiseInterface
     */
    public function promise(): PromiseInterface
    {
        return $this->deferred->promise();
    }

    /**
     * @param mixed $result
     */
    private function onFulfilled($result): void
    {
        $this->resolved = true;
        $this->value = $result;

        $this->worker->once(WorkerInterface::ON_CALLBACK, function () {
            $this->deferred->resolve($this->value);
        });
    }

    /**
     * @param \Throwable $e
     */
    private function onRejected(\Throwable $e): void
    {
        $this->resolved = true;

        $this->worker->once(WorkerInterface::ON_CALLBACK, function () use ($e) {
            $this->deferred->reject($e);
        });
    }
}
