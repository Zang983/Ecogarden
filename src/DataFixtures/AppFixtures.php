<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Factory\AdviceFactory;
use App\Factory\MonthFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Générer les mois
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $month = MonthFactory::createSpecificMonth($i);
            $months[$i] = $month; // Stocker chaque mois dans un tableau indexé par son numéro
        }

        // Générer les utilisateurs
        $users = UserFactory::createMany(30, fn() => [
            'password' => $this->passwordHasher->hashPassword(new User(), 'password'),
        ]);

        // Générer les conseils
        $advices = AdviceFactory::createMany(100, function () use ($months) {
            $randomMonths = array_rand($months, random_int(1, 12));
            if (!is_array($randomMonths)) {
                $randomMonths = [$randomMonths];
            }

            return [
                'months' => array_map(fn($monthNumber) => $months[$monthNumber], $randomMonths)
            ];
        });

        $manager->flush();
    }
}
