<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Faker\Factory;
use PHPUnit\Framework\Constraint\IsTrue;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use function Symfony\Component\String\b;

class UserFixtures extends Fixture
{

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en_NZ');

        $admin = new User();
        $admin->setEmail('admin@pathway.edu.nz')
            ->setFirstName('Admin')
            ->setLastName('User')
            ->setRoles(['ROLE_ADMIN'])
            ->setIsActive(true)
            ->setStudentId(null);

        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);

        $manager->persist($admin);

        for ($i = 0; $i < 25; $i++) {
            $student = new User();
            $student->setEmail($faker->unique()->safeEmail())
                ->setFirstName($faker->firstName())
                ->setLastName($faker->lastName())
                ->setRoles(['ROLE_STUDENT'])
                ->setIsActive($faker->boolean(95))
                ->setStudentId($faker->regexify('STU[0-9]{6}'));

          
            $avatarUrl = $faker->optional(0.7)->imageUrl(150, 150, 'people');
            if ($avatarUrl) {
                $student->setAvatarUrl($avatarUrl);
            }

            if ($faker->boolean(60)) {
                $student->setLastLoginAt(new \DateTimeImmutable($faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d H:i:s')));
            }

            $hashedPassword = $this->passwordHasher->hashPassword($student, 'student123');
            $student->setPassword($hashedPassword);

            $manager->persist($student);
            
            $this->addReference('student_' . $i, $student);
        }

        $manager->flush();
    }
}
