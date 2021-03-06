<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Client\Activity\Meta;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({ "METHOD" })
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class ActivityMethod
{
    /**
     * Name of the activity type.
     *
     * Be careful about names that contain special characters. These names can
     * be used as metric tags. And systems like prometheus ignore metrics which
     * have tags with unsupported characters.
     */
    public ?string $name = null;
}
