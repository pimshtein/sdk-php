<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Client\Internal\Declaration\Instantiator;

use Temporal\Client\Internal\Declaration\InstanceInterface;
use Temporal\Client\Internal\Declaration\Prototype\PrototypeInterface;

/**
 * @template-covariant TPrototype of PrototypeInterface
 * @template-covariant TInstance of InstanceInterface
 */
interface InstantiatorInterface
{
    /**
     * @param TPrototype $prototype
     * @return TInstance
     */
    public function instantiate(PrototypeInterface $prototype): InstanceInterface;
}
