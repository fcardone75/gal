<?php

namespace App\Repository;

use App\Entity\AtecoCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AtecoCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method AtecoCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method AtecoCode[]    findAll()
 * @method AtecoCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AtecoCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AtecoCode::class);
    }

    // /**
    //  * @return AtecoCode[] Returns an array of AtecoCode objects
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
    public function findOneBySomeField($value): ?AtecoCode
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
