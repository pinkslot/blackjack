<?php

declare(strict_types=1);

namespace App\Service;

use Exception;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

class WebSocketClient
{
    public function __construct(private int $port)
    {
    }

    public function sendData(array $data): void
    {
        $loop = Factory::create();
        $connector = $this->prepareConnection($loop);

        $connector->then(function (WebSocket $conn) use ($data) {
            $conn->send(json_encode($data));
            $conn->close();
        }, function (Exception $e) use ($loop) {
            echo "Could not connect: {$e->getMessage()}\n";
            $loop->stop();
        });

        $loop->run();
    }

    private function prepareConnection(LoopInterface $loop): Promise
    {
        $reactConnector = new \React\Socket\Connector(
            $loop,
            [
                'tls' => [
                    'allow_self_signed' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]
        );
        $connector = new Connector($loop, $reactConnector);
        $promise = $connector(
            'ws://localhost:' . $this->port,
            [],
            ['Origin' => 'http://localhost']
        );

        return $promise;
    }
}
