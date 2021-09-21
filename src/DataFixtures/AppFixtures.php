<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\BookCategory;
use App\Entity\Category;
use App\Entity\Coupon;
use App\Entity\Discount;
use App\Repository\CategoryRepository;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function load(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);

        $faker = Factory::create();

        // Add Categories
        $categories = ['Children', 'Fiction', 'Comic', 'Art', 'History'];
        foreach ($categories as $categoryName) {
            $category = new Category();
            $category->setName($categoryName);
            $manager->persist($category);
        }
        $manager->flush();

        $categories = $this->categoryRepository->findAll();

        // Add Books
        for ($i=0; $i < 20; $i++) {
            $book = new Book();
            $book->setTitle($faker->sentence(3));
            $book->setDescription($faker->paragraph(3));
            $book->setCover($faker->imageUrl(300, 600));
            $book->setPrice($faker->randomFloat(2, 300, 5000));
            $book->setCreated(new DateTimeImmutable());
            $manager->persist($book);
            $manager->flush();

            $categoryUnitLength = rand(1, count($categories));
            for ($j=0; $j < $categoryUnitLength; $j++) { 
                $category = $categories[$j];
                $bookCategory = new BookCategory();
                $bookCategory->setBook($book);
                $bookCategory->setCategory($category);
                $manager->persist($bookCategory);
            }
        }

        // Add Coupons
        $coupons = json_decode('[
            { 
                "code": "PER15",
                "discount_type": "PERCENTAGE",
                "amount": "15"
            },
            { 
                "code": "PER50",
                "discount_type": "PERCENTAGE",
                "amount": "50"
            },
            { 
                "code": "FEE100",
                "discount_type": "AMOUNT",
                "amount": "100"
            },
            { 
                "code": "FEE300",
                "discount_type": "AMOUNT",
                "amount": "300"
            }
        ]');
        foreach ($coupons as $couponData) {
            $coupon = new Coupon();
            $coupon->setCode($couponData->code);
            $coupon->setDiscountType($couponData->discount_type);
            $coupon->setAmount($couponData->amount);
            $coupon->setActive(1);
            $coupon->setExpire(new DateTimeImmutable("+1year"));
            $coupon->setCreated(new DateTimeImmutable());
            $manager->persist($coupon);
        }

        // Add Discount
        $category = $this->categoryRepository->findOneBy(['name' => 'Children']);
        $manager->flush();

        if ($category){
            $discount = new Discount();
            $discount->setRelateId($category->getId());
            $discount->setRelateType('CATEGORY');
            $discount->setQty(5);
            $discount->setDiscountType('PERCENTAGE');
            $discount->setAmount(10);
            $discount->setActive(1);
            $discount->setExpire(new DateTimeImmutable("+1year"));
            $discount->setCreated(new DateTimeImmutable());
            $manager->persist($discount);
        }

        $discount = new Discount();
        $discount->setRelateType('CATEGORY');
        $discount->setQty(10);
        $discount->setDiscountType('PERCENTAGE');
        $discount->setAmount(5);
        $discount->setActive(1);
        $discount->setExpire(new DateTimeImmutable("+1year"));
        $discount->setCreated(new DateTimeImmutable());
        $manager->persist($discount);

        $manager->flush();
    }
}
