<?php

declare(strict_types=1);

namespace App\Service;

use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class WebSocketHandler implements MessageComponentInterface
{
    private const ACTION_SUBSCRIBE = 'subscribe';
    private const ACTION_ISSUE = 'issue';

    public function __construct(private WebSocketMessageHandler $webSocketMessageHandler)
    {
    }


    function onOpen(ConnectionInterface $conn)
    {
        echo "Connected\n";
    }

    function onClose(ConnectionInterface $conn)
    {
        $this->webSocketMessageHandler->unsubscribe($conn);
        $conn->close();

        echo "Disconnected\n";
    }

    function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    function onMessage(ConnectionInterface $conn, $msg)
    {
        $data = json_decode($msg->getPayload(), true);
        var_dump($data);
        $actionData = $data['data'];

        match ($data['action']) {
            static::ACTION_SUBSCRIBE => $this->webSocketMessageHandler->subscribe(
                $actionData['modelId'],
                $conn,
            ),

            static::ACTION_ISSUE => $this->webSocketMessageHandler->issue(
                $actionData['modelId'],
                $actionData['state'],
            ),
        };
    }
}
