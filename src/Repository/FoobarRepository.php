<?php

namespace App\Repository;

use App\Entity\Foobar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Foobar|null find($id, $lockMode = null, $lockVersion = null)
 * @method Foobar|null findOneBy(array $criteria, array $orderBy = null)
 * @method Foobar[]    findAll()
 * @method Foobar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoobarRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Foobar::class);
    }

//    /**
//     * @return Foobar[] Returns an array of Foobar objects
//     */
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
    public function findOneBySomeField($value): ?Foobar
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
