<?php

namespace App\Repository;

use App\Entity\LeasingDestination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LeasingDestination|null find($id, $lockMode = null, $lockVersion = null)
 * @method LeasingDestination|null findOneBy(array $criteria, array $orderBy = null)
 * @method LeasingDestination[]    findAll()
 * @method LeasingDestination[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LeasingDestinationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LeasingDestination::class);
    }

    // /**
    //  * @return LeasingDestination[] Returns an array of LeasingDestination objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LeasingDestination
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
