<?php

namespace App\Entity;

use App\Repository\GameRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    public const STATUS_IN_PROGRESS = 'in-progress';
    public const STATUS_FINISHED = 'finished';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: 'game', fetch: 'LAZY')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $players;

    #[ORM\Column(type: Types::STRING, options: ['default' => self::STATUS_IN_PROGRESS])]
    private string $status = self::STATUS_IN_PROGRESS;

    private ?array $deckCache = null;

    #[ORM\Column(type: Types::STRING)]
    private string $updatedAt;

    public function __construct(
        #[ORM\Column(type: Types::BIGINT)]
        private int $seed,
    ) {
        $this->players = new ArrayCollection();
        $this->updated();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Player[]
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function getSeed(): int
    {
        return $this->seed;
    }

    public function finish(): void
    {
        $this->status = self::STATUS_FINISHED;
        $this->updated();
    }

    public function getDeck(): array
    {
        if ($this->deckCache === null) {
            $deck = range(0, 51);
            srand($this->getSeed());

            shuffle($deck);

            $this->deckCache = $deck;
        }

        return $this->deckCache;
    }

    public function getCards(): array
    {
        $deck = $this->getDeck();
        $result = [];
        foreach ($this->getPlayers() as $player) {
            $result[$player->getUserId()] = array_splice($deck, 0, $player->getCardCount());
        }

        return $result;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function updated(): void
    {
        $this->updatedAt = (new DateTimeImmutable())->format(DATE_W3C);
    }
}
