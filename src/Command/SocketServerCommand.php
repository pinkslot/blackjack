<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\WebSocketHandler;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SocketServerCommand extends Command
{
    public function __construct(
        private WebSocketHandler $webSocketHandler,
        private int $port
    ) {
        parent::__construct('socket:run');
    }


    protected function configure()
    {
        $this->setName('socket:run');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Starting server on port " . $this->port);
        $server = IoServer::factory(
            new HttpServer(
                new WsServer($this->webSocketHandler)
            ),
            $this->port,
        );
        $server->run();
        return 0;
    }
}
