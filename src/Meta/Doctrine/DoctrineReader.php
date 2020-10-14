<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Client\Meta\Doctrine;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Temporal\Client\Meta\ReaderInterface;

class DoctrineReader implements ReaderInterface
{
    /**
     * @var Reader|null
     */
    private ?Reader $reader;

    /**
     * @param Reader|null $reader
     */
    public function __construct(Reader $reader = null)
    {
        $this->reader = $reader ?? new AnnotationReader();
    }

    /**
     * @param string|null $name
     * @param iterable|object[] $annotations
     * @return object[]
     */
    private function filter(?string $name, iterable $annotations): iterable
    {
        if ($name === null) {
            yield from $annotations;

            return;
        }

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $name) {
                yield $annotation;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getClassMetadata(\ReflectionClass $class, string $name = null): iterable
    {
        $result = $this->reader->getClassAnnotations($class);

        return $this->filter($name, $result);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodMetadata(\ReflectionMethod $method, string $name = null): iterable
    {
        $result = $this->reader->getMethodAnnotations($method);

        return $this->filter($name, $result);
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyMetadata(\ReflectionProperty $property, string $name = null): iterable
    {
        $result = $this->reader->getPropertyAnnotations($property);

        return $this->filter($name, $result);
    }
}