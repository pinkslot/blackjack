<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Entity\Player;

class CardService
{
    public function getSum(Player $player): int
    {
        return array_sum($this->getValues($player->getCards()));
    }

    private function getValues(array $cards): array
    {
        return array_map(fn ($card) => $this->getValue($card), $cards);
    }

    private function getValue(int $card): int
    {
        $number = $card % 13 + 2;

        if ($number <= 10) {
            return $number;
        }

        // Ace
        if ($number === 13) {
            return 11;
        }

        return 10;
    }

    public function getWinner(Game $game): ?int
    {
        $player = $game->getPlayers()[0];
        if ($player->getStatus() !== Player::STATUS_STOOD) {
            return null;
        }

        $winner = null;
        $winnerSum = -1;
        foreach ($game->getPlayers() as $player) {
            $sum = $this->getSum($player);
            if ($sum > 21) {
                continue;
            }

            if ($sum > $winnerSum) {
                $winner = $player->getUserId();
                $winnerSum = $sum;
            }
        }

        return $winner;
    }
}