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
            $student = $this->getReference('student' . $i);

            $credentialCount = $faker->numberBetween(1, 5);

            for ($j = 0; $j <= $credentialCount; $j++) {
                $credentialIndex = $faker->numberBetween(1, 49);
                $credential = $this->getReference('credential_' . $credentialIndex);

                $progress = new StudentProgress();
                $progress->setStudent($student)
                    ->setMicroCredential($credential)
                    ->setDateEarned($faker->dateTimeBetween('-1 year', 'now'))
                    ->setStatus($faker->randomElement($statuses))
                    ->setVerifiedBy($faker->randomElement($verifiers))
                    ->setNote($faker->optional(0.4)->sentence());

                $manager->persist($progress);
            }
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            MicroCredentialFixtures::class,
        ];
    }
}
