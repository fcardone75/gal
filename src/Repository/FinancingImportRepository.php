<?php

namespace App\Repository;

use App\Entity\FinancingImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FinancingImport|null find($id, $lockMode = null, $lockVersion = null)
 * @method FinancingImport|null findOneBy(array $criteria, array $orderBy = null)
 * @method FinancingImport[]    findAll()
 * @method FinancingImport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FinancingImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinancingImport::class);
    }

    // /**
    //  * @return FinancingImport[] Returns an array of FinancingImport objects
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
    public function findOneBySomeField($value): ?FinancingImport
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
