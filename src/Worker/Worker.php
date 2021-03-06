<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Client\Worker;

use Carbon\Carbon;
use React\Promise\PromiseInterface;
use Temporal\Client\Activity\ActivityWorker;
use Temporal\Client\Internal\Declaration\Prototype\ActivityPrototype;
use Temporal\Client\Internal\Declaration\Prototype\Collection;
use Temporal\Client\Internal\Declaration\Prototype\WorkflowPrototype;
use Temporal\Client\Internal\Declaration\Reader\ActivityReader;
use Temporal\Client\Internal\Declaration\Reader\WorkflowReader;
use Temporal\Client\Internal\Events\EventEmitterTrait;
use Temporal\Client\Transport\ClientInterface;
use Temporal\Client\Transport\Protocol\Command\RequestInterface;
use Temporal\Client\Worker\Env\EnvironmentInterface;
use Temporal\Client\WorkerFactory;
use Temporal\Client\Workflow\WorkflowWorker;

class Worker implements WorkerInterface
{
    use EventEmitterTrait;

    /**
     * @var WorkflowWorker
     */
    private WorkflowWorker $workflowWorker;

    /**
     * @var ActivityWorker
     */
    private ActivityWorker $activityWorker;

    /**
     * @var string
     */
    private string $taskQueue;

    /**
     * @var \DateTimeInterface
     */
    private \DateTimeInterface $now;

    /**
     * @var WorkerFactory
     */
    private WorkerFactory $factory;

    /**
     * @var \Closure
     */
    private \Closure $factoryEventListener;

    /**
     * @var Collection<ActivityPrototype>
     */
    private Collection $activities;

    /**
     * @var ActivityReader
     */
    private ActivityReader $activityReader;

    /**
     * @var Collection<WorkflowPrototype>
     */
    private Collection $workflows;

    /**
     * @var WorkflowReader
     */
    private WorkflowReader $workflowReader;

    /**
     * @param WorkerFactory $factory
     * @param EnvironmentInterface $env
     * @param string $queue
     * @throws \Exception
     */
    public function __construct(WorkerFactory $factory, string $queue)
    {
        $this->taskQueue = $queue;
        $this->factory = $factory;
        $this->now = new \DateTimeImmutable('now', $this->factory->getDateTimeZone());

        $this->workflows = new Collection();
        $this->workflowReader = new WorkflowReader($this->factory->getReader());
        $this->workflowWorker = new WorkflowWorker($this->workflows, $this);

        $this->activities = new Collection();
        $this->activityReader = new ActivityReader($this->factory->getReader());
        $this->activityWorker = new ActivityWorker($this->activities, $this);

        $this->factoryEventListener = function () {
            $this->emit(self::ON_SIGNAL);
            $this->emit(self::ON_CALLBACK);
            $this->emit(self::ON_QUERY);
            $this->emit(self::ON_TICK);
        };

        $this->boot();
    }

    /**
     * @param class-string $class
     * @param bool $overwrite
     * @return $this
     */
    public function registerWorkflow(string $class, bool $overwrite = false): self
    {
        foreach ($this->workflowReader->fromClass($class) as $workflow) {
            $this->workflows->add($workflow, $overwrite);
        }

        return $this;
    }

    /**
     * @return Collection<WorkflowPrototype>
     */
    public function getWorkflows(): Collection
    {
        return $this->workflows;
    }

    /**
     * @param class-string $class
     * @param bool $overwrite
     * @return $this
     */
    public function registerActivity(string $class, bool $overwrite = false): self
    {
        foreach ($this->activityReader->fromClass($class) as $activity) {
            $this->activities->add($activity, $overwrite);
        }

        return $this;
    }

    /**
     * @return Collection<WorkflowPrototype>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    /**
     * @return void
     */
    private function boot(): void
    {
        $this->attachFactoryListener();
    }

    /**
     * @return void
     */
    private function attachFactoryListener(): void
    {
        $this->factory->on(LoopInterface::ON_TICK, $this->factoryEventListener);
    }

    /**
     * @return void
     */
    private function detachFactoryListener(): void
    {
        $this->factory->removeListener(LoopInterface::ON_TICK, $this->factoryEventListener);
    }

    /**
     * @return WorkflowWorker
     */
    public function getWorkflowWorker(): WorkflowWorker
    {
        return $this->workflowWorker;
    }

    /**
     * @return ActivityWorker
     */
    public function getActivityWorker(): ActivityWorker
    {
        return $this->activityWorker;
    }

    /**
     * @return \DateTimeInterface
     */
    public function now(): \DateTimeInterface
    {
        return $this->now;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getTickTime(): \DateTimeInterface
    {
        return $this->now;
    }

    /**
     * @return ClientInterface
     */
    public function getClient(): ClientInterface
    {
        return $this->factory->getClient();
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch(RequestInterface $request, array $headers = []): PromiseInterface
    {
        // Intercept headers
        if (isset($headers['tickTime'])) {
            $this->now = Carbon::parse($headers['tickTime'], $this->factory->getDateTimeZone());
        }

        $environment = $this->factory->getEnvironment();

        switch ($environment->get()) {
            case EnvironmentInterface::ENV_WORKFLOW:
                return $this->workflowWorker->dispatch($request, $headers);

            case EnvironmentInterface::ENV_ACTIVITY:
                return $this->activityWorker->dispatch($request, $headers);

            default:
                throw new \LogicException('Unsupported environment type');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTaskQueue(): string
    {
        return $this->taskQueue;
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->detachFactoryListener();
        $this->removeAllListeners();
    }
}
