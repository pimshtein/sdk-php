<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Client\Internal\Declaration\Prototype;

interface PrototypeInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return \ReflectionClass|null
     */
    public function getClass(): ?\ReflectionClass;

    /**
     * @return \ReflectionFunctionAbstract
     */
    public function getHandler(): \ReflectionFunctionAbstract;
}
