<?php

namespace App\Repository;

use App\Entity\FinancialDestination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FinancialDestination|null find($id, $lockMode = null, $lockVersion = null)
 * @method FinancialDestination|null findOneBy(array $criteria, array $orderBy = null)
 * @method FinancialDestination[]    findAll()
 * @method FinancialDestination[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FinancialDestinationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinancialDestination::class);
    }

    // /**
    //  * @return FinancialDestination[] Returns an array of FinancialDestination objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FinancialDestination
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
