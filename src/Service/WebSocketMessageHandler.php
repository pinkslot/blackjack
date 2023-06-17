<?php

declare(strict_types=1);

namespace App\Service;

use Ratchet\ConnectionInterface;

class WebSocketMessageHandler
{
    /**
     * @var ConnectionInterface[][]
     */
    private array $subscriptionByModel = [];

    public function subscribe(int $modelId, ConnectionInterface $connection): void
    {
        $this->subscriptionByModel[$modelId] ??= [];
        $this->subscriptionByModel[$modelId][] = $connection;
    }

    public function unsubscribe(ConnectionInterface $connection): void
    {
        foreach ($this->subscriptionByModel as $model => &$modelConnections) {
            foreach ($modelConnections as $key => $modelConnection) {
                if ($modelConnection === $connection) {
                    unset($modelConnections[$key]);
                }
            }
        }
    }

    public function issue(int $modelId, array $state): void
    {
        foreach ($this->subscriptionByModel[$modelId] ?? [] as $conn) {
            $conn->send(json_encode($state));
        }
    }
}
