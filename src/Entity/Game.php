<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: 'game', fetch: 'LAZY')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $players;

    private ?array $deckCache = null;

    public function __construct(
        #[ORM\Column(type: Types::BIGINT)]
        private int $seed,
    ) {
        $this->players = new ArrayCollection();
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

    public function getCards(Game $game): array
    {
        $deck = $game->getDeck();
        $result = [];
        foreach ($game->getPlayers() as $player) {
            $result[$player->getUserId()] = array_splice($deck, 0, $player->getCardCount());
        }

        return $result;
    }
}
