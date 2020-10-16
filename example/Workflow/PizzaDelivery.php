<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Workflow;

use Temporal\Client\Activity\ActivityOptions;
use Temporal\Client\Workflow\Meta\QueryMethod;
use Temporal\Client\Workflow\Meta\SignalMethod;
use Temporal\Client\Workflow\Meta\WorkflowMethod;
use Temporal\Client\Workflow\Runtime\WorkflowContextInterface;

use function React\Promise\all;

class PizzaDelivery
{
    protected $status;

    /**
     * @WorkflowMethod(name="PizzaDelivery")
     */
    #[WorkflowMethod(name: 'PizzaDelivery')]
    public function handler(WorkflowContextInterface $context)
    {
        yield all([
            $context->executeActivity('A'),
            $context->executeActivity('B')
        ]);

        yield $context->executeActivity('C', []);

        return 32;
    }

    /**
     * @QueryMethod()
     */
    #[QueryMethod]
    public function getStatus(): string
    {
        return 'I\'m Ok';
    }

    /**
     * @SignalMethod()
     */
    #[SignalMethod]
    public function retryNow(): void
    {
        //
    }
}
