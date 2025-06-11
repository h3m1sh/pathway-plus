<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\MicroCredential;
use Faker\Factory;

class MicroCredentialFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en_NZ');

        $credentialData = [
            ['name' => 'Web Development Fundamentals', 'category' => 'Technology', 'level' => 'Foundation'],
            ['name' => 'Data Analysis with Python', 'category' => 'Technology', 'level' => 'Intermediate'],
            ['name' => 'Digital Marketing Essentials', 'category' => 'Business', 'level' => 'Foundation'],
            ['name' => 'Project Management Basics', 'category' => 'Business', 'level' => 'Foundation'],
            ['name' => 'UI/UX Design Principles', 'category' => 'Creative', 'level' => 'Intermediate'],
            ['name' => 'Database Design & SQL', 'category' => 'Technology', 'level' => 'Intermediate'],
            ['name' => 'Content Writing & Strategy', 'category' => 'Creative', 'level' => 'Foundation'],
            ['name' => 'Leadership & Team Management', 'category' => 'Business', 'level' => 'Advanced'],
            ['name' => 'Mobile App Development', 'category' => 'Technology', 'level' => 'Advanced'],
            ['name' => 'Financial Literacy', 'category' => 'Business', 'level' => 'Foundation'],
            ['name' => 'Cloud Computing Basics', 'category' => 'Technology', 'level' => 'Foundation'],
            ['name' => 'Cybersecurity Awareness', 'category' => 'Technology', 'level' => 'Foundation'],
            ['name' => 'Agile Methodologies', 'category' => 'Business', 'level' => 'Intermediate'],
            ['name' => 'Graphic Design Fundamentals', 'category' => 'Creative', 'level' => 'Foundation'],
            ['name' => 'Public Speaking & Communication', 'category' => 'Business', 'level' => 'Intermediate'],
            ['name' => 'Machine Learning Introduction', 'category' => 'Technology', 'level' => 'Advanced'],
            ['name' => 'Social Media Strategy', 'category' => 'Business', 'level' => 'Intermediate'],
            ['name' => 'Photography Basics', 'category' => 'Creative', 'level' => 'Foundation'],
            ['name' => 'Business Analytics', 'category' => 'Business', 'level' => 'Advanced'],
            ['name' => 'Ethical Hacking Essentials', 'category' => 'Technology', 'level' => 'Advanced'],
            ['name' => 'Creative Writing Workshop', 'category' => 'Creative', 'level' => 'Intermediate'],
            ['name' => 'Entrepreneurship 101', 'category' => 'Business', 'level' => 'Foundation'],
            ['name' => 'Data Visualization with Tableau', 'category' => 'Technology', 'level' => 'Intermediate'],
            ['name' => 'Digital Illustration', 'category' => 'Creative', 'level' => 'Intermediate'],
            ['name' => 'Negotiation Skills', 'category' => 'Business', 'level' => 'Advanced'],
            ['name' => 'Blockchain Fundamentals', 'category' => 'Technology', 'level' => 'Foundation'],
            ['name' => 'Video Editing Basics', 'category' => 'Creative', 'level' => 'Foundation'],
            ['name' => 'Customer Service Excellence', 'category' => 'Business', 'level' => 'Foundation'],
            ['name' => 'Advanced Excel Techniques', 'category' => 'Business', 'level' => 'Advanced'],
            ['name' => 'Game Development with Unity', 'category' => 'Technology', 'level' => 'Advanced'],
            ['name' => 'Branding & Identity Design', 'category' => 'Creative', 'level' => 'Advanced'],
        ];

        foreach ($credentialData as $index => $data) {
            $credential = new MicroCredential();
            $credential->setName($data['name'])
                ->setCategory($data['category'])
                ->setLevel($data['level'])
                ->setDescription($faker->paragraph(3))
                ->setBadgeUrl(null);

            $manager->persist($credential);
            $this->addReference('credential_' . $index, $credential);
        }
            $manager->flush();
    }
}
