<?php

namespace App\Repository;

use App\Entity\VacationRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VacationRequest>
 *
 * @method VacationRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method VacationRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method VacationRequest[]    findAll()
 * @method VacationRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VacationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VacationRequest::class);
    }

    public function findApprovedAndOfCurrentYearByUserId($userId): array
    {
        return $this->createQueryBuilder('v')
           ->where('v.user = :userId')
           ->andWhere('v.status = :status')
           ->andWhere('v.startingDate BETWEEN :yearStart AND :yearEnd')
           ->setParameter('userId', $userId)
           ->setParameter('status', 'APPROVED')
           ->setParameter('yearStart', date('Y-01-01'))
           ->setParameter('yearEnd', date('Y-12-31'))
           ->orderBy('v.startingDate', 'DESC')
           ->getQuery()
           ->getResult()
        ;
    }

    public function findOfCurrentYearByStatusesAndTeam($statuses, $team): array
    {
        $queryBuilder = $this->createQueryBuilder('v');
        return  $this->createQueryBuilder('v')
            ->join('v.user', 'u')
            ->where('u.team = :team')
            ->andWhere('v.startingDate BETWEEN :yearStart AND :yearEnd')
            ->andWhere($queryBuilder->expr()->in('v.status', '?1'))
            ->setParameter('team', $team)
            ->setParameter('yearStart', date('Y-01-01'))
            ->setParameter('yearEnd', date('Y-12-31'))
            ->setParameter(1, $statuses)
            ->orderBy('v.startingDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return VacationRequest[] Returns an array of VacationRequest objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?VacationRequest
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
