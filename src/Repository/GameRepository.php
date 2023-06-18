<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\Player;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 *
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function save(Game $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Game $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getExpiredModelGames(int $modelId): array
    {
        $expiredAt = (new DateTimeImmutable())->modify('-1 minutes');

        return $this->getModelActiveGameQueryBuilder($modelId)
            ->select('g')
            ->andWhere('g.updatedAt < :updatedAt')
            ->setParameter('updatedAt', $expiredAt->format(DATE_W3C))
            ->getQuery()
            ->getResult()
        ;
    }

    public function getModelActiveGame(int $modelId): ?Game
    {
        return $this->getModelActiveGameQueryBuilder($modelId)
            ->select('g')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    private function getModelActiveGameQueryBuilder(int $modelId): QueryBuilder
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.status = :status')
            ->setParameter('status', Game::STATUS_IN_PROGRESS)
            ->join('g.players', 'p')
            ->andWhere('p.userId = :modelId')
            ->setParameter('modelId', $modelId)
        ;
    }

//    public function findOneBySomeField($value): ?Game
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
