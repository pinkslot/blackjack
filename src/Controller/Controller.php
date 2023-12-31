<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Player;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Service\CardService;
use App\Service\GameSerializer;
use App\Service\WebSocketClient;
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
        private WebSocketClient $webSocketClient,
    ) {
    }

    #[Route('/games', methods: ['POST'])]
    public function createGame(Request $request): Response
    {
        $requestContent = json_decode($request->getContent(), true);

        $activeGame = $this->gameRepository->getModelActiveGame($requestContent['modelId']);

        if ($activeGame !== null) {
            return new Response('Model already has active game', Response::HTTP_BAD_REQUEST);
        }

        $game = new Game(rand());
        $this->gameRepository->save($game);

        $user = new Player($game, $requestContent['userId']);
        $model = new Player($game, $requestContent['modelId']);
        $this->playerRepository->save($user);
        $this->playerRepository->save($model);

        $this->entityManager->flush();

        $state = $this->gameSerializer->serialize($game);
        $this->sendWebsocketData($model, $state);

        return new JsonResponse($state);
    }

    #[Route('/games/{gameId}/hit', methods: ['POST'])]
    public function hit(Request $request): Response
    {
        $game = $this->gameRepository->find($request->get('gameId'));
        if ($game === null) {
            return new Response('Game not found', Response::HTTP_NOT_FOUND);
        }

        if ($game->getStatus() === Game::STATUS_FINISHED) {
            return new Response('Game finished', Response::HTTP_BAD_REQUEST);
        }

        $player = $game->getPlayers()[0];

        $player->incrementCardCount();
        if ($this->cardService->getSum($player) >= 21) {
            $this->doStand($game);
        }
        $game->updated();

        $this->entityManager->flush();

        $model = $game->getPlayers()[1];

        $state = $this->gameSerializer->serialize($game);
        $this->sendWebsocketData($model, $state);

        return new JsonResponse($state);
    }

    #[Route('/games/{gameId}/stand', methods: ['POST'])]
    public function stand(Request $request): Response
    {
        $game = $this->gameRepository->find($request->get('gameId'));
        if ($game === null) {
            return new Response('Game not found', Response::HTTP_NOT_FOUND);
        }

        if ($game->getStatus() === Game::STATUS_FINISHED) {
            return new Response('Game finished', Response::HTTP_BAD_REQUEST);
        }

        $this->doStand($game);

        $this->entityManager->flush();

        $model = $game->getPlayers()[1];

        $state = $this->gameSerializer->serialize($game);
        $this->sendWebsocketData($model, $state);

        return new JsonResponse($state);
    }

    private function doStand(Game $game): void
    {
        $player = $game->getPlayers()[0];
        $game->finish();
        $playerSum = $this->cardService->getSum($player);

        if ($playerSum >= 21) {
            return;
        }

        $model = $game->getPlayers()[1];
        while ($this->cardService->getSum($model) <= $playerSum) {
            $model->incrementCardCount();
        }
    }

    #[Route('/games', methods: ['GET'])]
    public function getGame(Request $request): Response
    {
        $modelId = (int)$request->get('modelId');

        $expiredModelGames = $this->gameRepository->getExpiredModelGames($modelId);

        if (count($expiredModelGames) > 0) {
            foreach ($expiredModelGames as $expiredModelGame) {
                $this->doStand($expiredModelGame);
            }

            $this->entityManager->flush();
        }

        $game = $this->gameRepository->getModelActiveGame($modelId);
        if ($game === null) {
            return new JsonResponse(null);
        }

        return new JsonResponse($this->gameSerializer->serialize($game));
    }

    /**
     * @param mixed $model
     * @param array $state
     * @return void
     */
    public function sendWebsocketData(mixed $model, array $state): void
    {
        $this->webSocketClient->sendData([
            'action' => 'issue',
            'data' => [
                'modelId' => $model->getUserId(),
                'state' => $state,
            ],
        ]);
    }
}
