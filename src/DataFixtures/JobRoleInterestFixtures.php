<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\JobRole;
use App\Repository\JobRoleRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory;

class JobRoleInterestFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly JobRoleRepository $jobRoleRepository
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en_NZ');
        
        $jobRoles = $this->jobRoleRepository->findAll();

        $studentPersonas = [
            'tech' => [
                'Engineering',
                'Architecture, Technical Design and Mapping',
                'Information Technology',
                'Telecommunications',
                'Science',
                'Manufacturing',
                'Infrastructure',
                'Construction',
                'Automotive'
            ],
            'creative' => [
                'Creative Design',
                'Entertainment',
                'Writing and Publishing',
                'Culture and Heritage',
                'Advertising and Marketing',
                'Sport and Recreation',
                'Languages'
            ],
            'business' => [
                'Administration',
                'Finance',
                'Management and Consulting',
                'Market and Social Research',
                'Property Services',
                'Retail',
                'Government'
            ],
            'service' => [
                'Community Services',
                'Education',
                'Health',
                'Hospitality',
                'Public Order and Safety',
                'Hair and Beauty',
                'Cleaning and Gardening',
                'Animal Care'
            ]
        ];

        
        $popularIndustries = [
            'Engineering',
            'Creative Design',
            'Finance',
            'Education',
            'Information Technology',
            'Health',
            'Construction',
            'Hospitality',
            'Science',
            'Retail',
            'Advertising and Marketing',
            'Management and Consulting',
            'Community Services',
            'Automotive',
            'Telecommunications'
        ];
        

        for ($i = 0; $i < 25; $i++) {
            $student = $this->getReference('student_' . $i);
            
            
            $personaKeys = array_keys($studentPersonas);
            $studentPersona = $faker->randomElement($personaKeys);
            $preferredIndustries = $studentPersonas[$studentPersona];
            
            $interestCount = $faker->numberBetween(1, 3);

            
            
            $availableRoles = [];
            
            if ($faker->boolean(70)) {
                // 70% chance: Pick from student's preferred industries
                $availableRoles = $this->filterJobRolesByIndustries($jobRoles, $preferredIndustries);
            } else {
                // 30% chance: Pick from any industry for variety
                $availableRoles = $jobRoles;
            }
            
            // Apply popular industry weighting
            $weightedRoles = $this->applyPopularityWeighting($availableRoles, $popularIndustries);
            
            // Select random roles from the weighted pool
            $selectedJobRoles = $this->selectRandomRoles($weightedRoles, $interestCount, $faker);

            foreach ($selectedJobRoles as $jobRole) {
                $student->addJobRoleInterest($jobRole);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            \App\DataFixtures\UserFixtures::class,
        ];
    }

    private function filterJobRolesByIndustries(array $jobRoles, array $preferredIndustries): array
    {
        $filteredRoles = [];
        foreach ($jobRoles as $jobRole) {
            if (in_array($jobRole->getIndustry(), $preferredIndustries)) {
                $filteredRoles[] = $jobRole;
            }
        }
        return $filteredRoles;
    }

    private function applyPopularityWeighting(array $roles, array $popularIndustries): array
    {
        $weightedRoles = [];
        foreach ($roles as $role) {
            // Add role once normally
            $weightedRoles[] = $role;
            
            // Add popular roles again for double weight
            if (in_array($role->getIndustry(), $popularIndustries)) {
                $weightedRoles[] = $role;
            }
        }
        return $weightedRoles;
    }

    private function selectRandomRoles(array $roles, int $count, $faker): array
    {
        if (empty($roles)) {
            return [];
        }
        
        $selectedRoles = [];
        $totalRoles = count($roles);
        
        for ($i = 0; $i < $count; $i++) {
            $randomIndex = $faker->numberBetween(0, $totalRoles - 1);
            $selectedRoles[] = $roles[$randomIndex];
        }
        return $selectedRoles;
    }
}
