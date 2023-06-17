<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Entity\Player;

class CardService
{
    public function getSum(Player $player): int
    {
        $values = $this->getValues($player->getCards());
        while (array_sum($values) > 21) {
            // Try to replace Ace value from 11 to 1
            if (!$this->replaceValues($values, 11, 1)) {
                break;
            }
        }

        return array_sum($values);
    }

    private function replaceValues(array &$array, $from, $to): bool
    {
        foreach ($array as $key => $value) {
            if ($value === $from) {
                $array[$key] = $to;

                return true;
            }
        }

        return false;
    }

    private function getValues(array $cards): array
    {
        return array_map(fn ($card) => $this->getValue($card), $cards);
    }

    private function getValue(int $card): int
    {
        $number = $card % 13;

        if ($number <= 8) {
            return $number + 2;
        }

        // Ace
        if ($number === 12) {
            return 11;
        }

        return 10;
    }

    public function getWinner(Game $game): ?int
    {
        if ($game->getStatus() !== Game::STATUS_FINISHED) {
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
