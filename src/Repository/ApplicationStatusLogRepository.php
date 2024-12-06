<?php

namespace App\Repository;

use App\Entity\ApplicationStatusLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApplicationStatusLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApplicationStatusLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApplicationStatusLog[]    findAll()
 * @method ApplicationStatusLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicationStatusLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationStatusLog::class);
    }

    // /**
    //  * @return ApplicationStatusLog[] Returns an array of ApplicationStatusLog objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ApplicationStatusLog
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
