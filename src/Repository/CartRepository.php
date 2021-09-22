<?php

namespace App\Repository;

use App\Entity\Cart;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

/**
 * @method Cart|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cart|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cart[]    findAll()
 * @method Cart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, Security $security)
    {
        $this->security = $security;
        parent::__construct($registry, Cart::class);
    }

    /**
    * @return Void
    */
    public function addToCart($book)
    {
        $cart = new Cart();
        $user = $this->security->getUser();
        $cart
            ->setUser($user)
            ->setBook($book)
            ->setCreated(new DateTimeImmutable());

        $this->_em->persist($cart);
        $this->_em->flush();
        return $cart;
    }


    /**
    * @return Void
    */
    public function removeFromCart($cartItem)
    {
        $this->_em->remove($cartItem);
        $this->_em->flush();
    }

    /**
    * @return Void
    */
    public function clearCart()
    {
        $user = $this->security->getUser();
        $cartItems = $this->findBy(['user' => $user]);
        foreach ($cartItems as $item) {
            $this->_em->remove($item);
        }
        $this->_em->flush();
    }

    // /**
    //  * @return Cart[] Returns an array of Cart objects
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
    public function findOneBySomeField($value): ?Cart
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
