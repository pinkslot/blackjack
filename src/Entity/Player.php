<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_STOOD = 'stood';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $cardCount = 0;

    #[ORM\Column(type: Types::STRING)]
    private string $status = self::STATUS_ACTIVE;

    private ?array $cardsCache = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'players')]
        private Game $game,

        #[ORM\Column(type: Types::BIGINT)]
        private int $userId,
    ) {
        $this->game->getPlayers()->add($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCardCount(): int
    {
        return $this->cardCount;
    }

    public function incrementCardCount(): static
    {
        $this->cardCount++;

        return $this;
    }

    public function stand(): static
    {
        $this->status = static::STATUS_STOOD;

        return $this;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCards(): array
    {
        return [];
    }
}
