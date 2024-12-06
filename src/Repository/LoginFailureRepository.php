<?php

namespace App\Repository;

use App\Entity\LoginFailure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LoginFailure|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoginFailure|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoginFailure[]    findAll()
 * @method LoginFailure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginFailureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginFailure::class);
    }

    // /**
    //  * @return LoginFailure[] Returns an array of LoginFailure objects
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
    public function findOneBySomeField($value): ?LoginFailure
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
