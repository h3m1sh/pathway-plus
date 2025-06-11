<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\MicroCredential;
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

        for ($i = 0; $i < 25; $i++) {
            $student = $this->getReference('student_' . $i, User::class);
            $credentialCount = $faker->numberBetween(1, 5);

            $availableCredentials = range(0, 30);
            shuffle($availableCredentials);
            $selectedCredentials = array_slice($availableCredentials, 0, $credentialCount);

            foreach ($selectedCredentials as $credentialIndex) {
                $credential = $this->getReference('credential_' . $credentialIndex, MicroCredential::class);

                $progress = new StudentProgress();
                $progress->setStudent($student)
                    ->setMicroCredential($credential)
                    ->setDateEarned(new \DateTimeImmutable($faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s')))
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
