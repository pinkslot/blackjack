<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;

class GameSerializer
{
    public function __construct(private CardService $cardService)
    {
    }

    public function serialize(Game $game): array
    {
        $cards = $game->getCards();
        $players = [];
        foreach ($game->getPlayers() as $player) {
            $players[$player->getUserId()] = [
                'cards' => $cards[$player->getUserId()],
                'sum' => $this->cardService->getSum($player),
            ];
        }

        return [
            'id' => $game->getId(),
            'status' => $game->getStatus(),
            'playerId' => $game->getPlayers()[0]->getUserId(),
            'players' => $players,
            'winner' => $this->cardService->getWinner($game),
            'timestamp' => time(),
        ];
    }
}
