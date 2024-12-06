<?php

namespace App\Repository;

use App\Entity\LeasingImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LeasingImport|null find($id, $lockMode = null, $lockVersion = null)
 * @method LeasingImport|null findOneBy(array $criteria, array $orderBy = null)
 * @method LeasingImport[]    findAll()
 * @method LeasingImport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LeasingImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LeasingImport::class);
    }

    // /**
    //  * @return LeasingImport[] Returns an array of LeasingImport objects
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
    public function findOneBySomeField($value): ?LeasingImport
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
