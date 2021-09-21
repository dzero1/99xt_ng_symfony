<?php

namespace App\DataFixtures;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        // Add a user
        $user = new User();
        $user->setUsername('user');
        // $user->setPassword('user123');
        $user->setFirstName($faker->firstName);
        $user->setLastName($faker->lastName);
        $user->setEmail($faker->email);
        $user->setAvatar($faker->imageUrl(200, 200, 'cats'));
        $user->setCreated(new DateTimeImmutable());

        $user->setPassword($this->passwordHasher->hashPassword( $user, 'user123'));
            
        $manager->persist($user);

        $manager->flush();
    }
}
