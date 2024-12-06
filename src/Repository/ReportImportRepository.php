<?php

namespace App\Repository;

use App\Entity\ReportImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ReportImport|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReportImport|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReportImport[]    findAll()
 * @method ReportImport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReportImport::class);
    }

    // /**
    //  * @return ReportImport[] Returns an array of ReportImport objects
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
    public function findOneBySomeField($value): ?ReportImport
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
