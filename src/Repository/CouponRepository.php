<?php

namespace App\Repository;

use App\Entity\Coupon;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Coupon|null find($id, $lockMode = null, $lockVersion = null)
 * @method Coupon|null findOneBy(array $criteria, array $orderBy = null)
 * @method Coupon[]    findAll()
 * @method Coupon[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CouponRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coupon::class);
    }

    /**
     * @return Coupon[] Returns an array of Coupon objects
     */
    public function getCoupon($code)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.code = :code')
            ->andWhere('c.active = :active')
            ->andWhere('c.expire > :date')
            ->setParameter('code', $code)
            ->setParameter('active', '1')
            ->setParameter('date', new DateTimeImmutable())
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    // /**
    //  * @return Coupon[] Returns an array of Coupon objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Coupon
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
