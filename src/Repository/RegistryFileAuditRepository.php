<?php

namespace App\Repository;

use App\Entity\RegistryFileAudit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RegistryFileAudit|null find($id, $lockMode = null, $lockVersion = null)
 * @method RegistryFileAudit|null findOneBy(array $criteria, array $orderBy = null)
 * @method RegistryFileAudit[]    findAll()
 * @method RegistryFileAudit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegistryFileAuditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegistryFileAudit::class);
    }

    // /**
    //  * @return Periodicity[] Returns an array of Periodicity objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Periodicity
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
