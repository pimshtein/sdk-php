<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Spiral\Goridge\RPC;

/** @var RPC $rpc */
$rpc = require __DIR__ . '/connection.php';

$result = $rpc->call('temporal.QueryWorkflow', [
    'wid'        => 'WORKFLOW_ID',
    'rid'        => 'WORKFLOW_RUN_ID',
    'query_type' => 'QUERY_NAME',
    'args'       => [],
]);

dump($result);
