<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\StudentProgress;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;

class StudentProgressFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en_NZ');

        $statuses = ['Completed', 'In Progress', 'Verified', 'Under Review'];
        $verifiers = [
            'University of Auckland',
            'Victoria University of Wellington',
            'Massey University',
            'Canterbury University',
            'Industry Certification Board'
        ];

        for ($i = 1; $i <= 25; $i++) {
            $student = $thi
        }

        $manager->flush();
    }
}
