<?php

namespace App\Repository;

use App\Entity\ApplicationImportTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApplicationImportTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApplicationImportTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApplicationImportTemplate[]    findAll()
 * @method ApplicationImportTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicationImportTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationImportTemplate::class);
    }

    // /**
    //  * @return ApplicationImportTemplate[] Returns an array of ApplicationImportTemplate objects
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
    public function findOneBySomeField($value): ?ApplicationImportTemplate
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
