<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Player;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Service\CardService;
use App\Service\GameSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Controller
{
    public function __construct(
        private GameRepository $gameRepository,
        private PlayerRepository $playerRepository,
        private EntityManagerInterface $entityManager,
        private GameSerializer $gameSerializer,
        private CardService $cardService,
    ) {
    }

    #[Route('/games', methods: ['POST'])]
    public function createGame(Request $request): Response
    {
        $requestContent = json_decode($request->getContent(), true);

        $game = new Game(rand());
        $this->gameRepository->save($game);

        $user = new Player($game, $requestContent['userId'], 0);
        $model = new Player($game, $requestContent['modelId'], 1);
        $this->playerRepository->save($user);
        $this->playerRepository->save($model);

        $this->entityManager->flush();

        return new JsonResponse($this->gameSerializer->serialize($game));
    }

    #[Route('/games/{gameId}/hit', methods: ['POST'])]
    public function hit(Request $request): Response
    {
        $game = $this->gameRepository->find($request->get('gameId'));
        if ($game === null) {
            return new Response('Game not found', Response::HTTP_NOT_FOUND);
        }

        $player = $game->getPlayers()[0];

        if ($player->getStatus() !== Player::STATUS_ACTIVE) {
            return new Response('Game finished', Response::HTTP_BAD_REQUEST);
        }

        $player->incrementCardCount();
        if ($this->cardService->getSum($player) >= 21) {
            $this->doStand($game);
        }

        $this->entityManager->flush();

        return new JsonResponse($this->gameSerializer->serialize($game));
    }

    #[Route('/games/{gameId}/stand', methods: ['POST'])]
    public function stand(Request $request): Response
    {
        $game = $this->gameRepository->find($request->get('gameId'));
        if ($game === null) {
            return new Response('Game not found', Response::HTTP_NOT_FOUND);
        }

        $player = $game->getPlayers()[0];

        if ($player->getStatus() !== Player::STATUS_ACTIVE) {
            return new Response('Game finished', Response::HTTP_BAD_REQUEST);
        }

        $this->doStand($game);

        $this->entityManager->flush();

        return new JsonResponse($this->gameSerializer->serialize($game));
    }

    private function doStand(Game $game): void
    {
        $player = $game->getPlayers()[0];
        $player->stand();
        $playerSum = $this->cardService->getSum($player);

        if ($playerSum >= 21) {
            return;
        }

        $model = $game->getPlayers()[1];
        while ($this->cardService->getSum($model) <= $playerSum) {
            $model->incrementCardCount();
        }
    }
}
