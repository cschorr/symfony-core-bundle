<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\CompanyGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CompanyFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Available demo logos
        $demoLogos = [
            'images/demo-logos/atlas-square.svg',
            'images/demo-logos/aurora-square.svg',
            'images/demo-logos/bitwave-square.svg',
            'images/demo-logos/drift-square.svg',
            'images/demo-logos/echo-square.svg',
            'images/demo-logos/flux-square.svg',
            'images/demo-logos/forge-square.svg',
            'images/demo-logos/harbor-square.svg',
            'images/demo-logos/lumen-square.svg',
            'images/demo-logos/nimbus-square.svg',
            'images/demo-logos/nova-square.svg',
            'images/demo-logos/orbit-square.svg',
            'images/demo-logos/pulse-square.svg',
            'images/demo-logos/quantum-square.svg',
            'images/demo-logos/vertex-square.svg',
            'images/demo-logos/zephyr-square.svg',
        ];

        // Companies themed from comics/movies and additional diverse demo companies
        $companiesData = [
            ['display' => 'Cyberdyne Systems', 'email' => 'contact@cyberdyne.example', 'country' => 'US', 'category' => 'Technology', 'phone' => '+1 555 0100', 'url' => 'https://cyberdyne.example', 'street' => '101 Skynet Blvd', 'city' => 'Los Angeles', 'zipCode' => '90001', 'group' => 'Skynet Group'],
            ['display' => 'Stark Industries', 'email' => 'info@stark.example', 'country' => 'US', 'category' => 'Software Solutions', 'phone' => '+1 555 0101', 'url' => 'https://stark.example', 'street' => '1 Avengers Tower', 'city' => 'New York', 'zipCode' => '10001', 'group' => 'Marvel Group'],
            ['display' => 'Wayne Enterprises', 'email' => 'hello@wayne.example', 'country' => 'US', 'category' => 'Business Services', 'phone' => '+1 555 0102', 'url' => 'https://wayne.example', 'street' => '1007 Mountain Drive', 'city' => 'Gotham', 'zipCode' => '07001', 'group' => 'DC Group'],
            ['display' => 'Oscorp', 'email' => 'contact@oscorp.example', 'country' => 'US', 'category' => 'Web Development', 'phone' => '+1 555 0103', 'url' => 'https://oscorp.example', 'street' => '500 Spider Ave', 'city' => 'New York', 'zipCode' => '10002', 'group' => 'Marvel Group'],
            ['display' => 'Weyland-Yutani', 'email' => 'corp@weyland.example', 'country' => 'UK', 'category' => 'Consulting', 'phone' => '+44 20 7946 0000', 'url' => 'https://weyland.example', 'street' => '1 Offworld Park', 'city' => 'London', 'zipCode' => 'SW1A 1AA', 'group' => 'Weyland-Yutani Group'],
            ['display' => 'Umbrella Corporation', 'email' => 'hq@umbrella.example', 'country' => 'DE', 'category' => 'Marketing & Sales', 'phone' => '+49 30 123456', 'url' => 'https://umbrella.example', 'street' => '13 Hive Str.', 'city' => 'Raccoon City', 'zipCode' => '10117', 'group' => 'Umbrella Group'],
            ['display' => 'GeneDyne Technologies', 'email' => 'info@genedyne.example', 'country' => 'CA', 'category' => 'Software Solutions', 'phone' => '+1 416 555 0200', 'url' => 'https://genedyne.example', 'street' => '2500 Tech Valley Dr', 'city' => 'Toronto', 'zipCode' => 'M5V 3A8', 'group' => 'Skynet Group'],
            ['display' => 'NeuralLink Systems', 'email' => 'contact@neurallink.example', 'country' => 'JP', 'category' => 'Technology', 'phone' => '+81 3 5555 0300', 'url' => 'https://neurallink.example', 'street' => '1-1-1 Shibuya', 'city' => 'Tokyo', 'zipCode' => '150-0002', 'group' => 'Skynet Group'],
            ['display' => 'Parker Industries', 'email' => 'hello@parker.example', 'country' => 'US', 'category' => 'Mobile Development', 'phone' => '+1 555 0400', 'url' => 'https://parker.example', 'street' => '20 Ingram Street', 'city' => 'New York', 'zipCode' => '10038', 'group' => 'Marvel Group'],
            ['display' => 'Pym Technologies', 'email' => 'info@pym.example', 'country' => 'US', 'category' => 'Software Solutions', 'phone' => '+1 415 555 0500', 'url' => 'https://pym.example', 'street' => '1955 Quantum Ave', 'city' => 'San Francisco', 'zipCode' => '94102', 'group' => 'Marvel Group'],
            ['display' => 'Rand Corporation', 'email' => 'contact@rand.example', 'country' => 'US', 'category' => 'Business Services', 'phone' => '+1 555 0600', 'url' => 'https://rand.example', 'street' => '200 Iron Fist Plaza', 'city' => 'New York', 'zipCode' => '10013', 'group' => 'Marvel Group'],
            ['display' => 'Queen Industries', 'email' => 'admin@queen.example', 'country' => 'US', 'category' => 'Financial Services', 'phone' => '+1 206 555 0700', 'url' => 'https://queen.example', 'street' => '1701 Green Arrow Way', 'city' => 'Star City', 'zipCode' => '98101', 'group' => 'DC Group'],
            ['display' => 'LexCorp', 'email' => 'info@lexcorp.example', 'country' => 'US', 'category' => 'Consulting', 'phone' => '+1 555 0800', 'url' => 'https://lexcorp.example', 'street' => '1000 LexCorp Plaza', 'city' => 'Metropolis', 'zipCode' => '10001', 'group' => 'DC Group'],
            ['display' => 'Kord Industries', 'email' => 'hello@kord.example', 'country' => 'US', 'category' => 'Web Development', 'phone' => '+1 773 555 0900', 'url' => 'https://kord.example', 'street' => '42 Beetle Drive', 'city' => 'Chicago', 'zipCode' => '60601', 'group' => 'DC Group'],
            ['display' => 'Tyrell Corporation', 'email' => 'corp@tyrell.example', 'country' => 'US', 'category' => 'Technology', 'phone' => '+1 213 555 1000', 'url' => 'https://tyrell.example', 'street' => '2019 Replicant Blvd', 'city' => 'Los Angeles', 'zipCode' => '90028', 'group' => 'Weyland-Yutani Group'],
            ['display' => 'Seegson Corporation', 'email' => 'contact@seegson.example', 'country' => 'FR', 'category' => 'Legal Services', 'phone' => '+33 1 55 55 1100', 'url' => 'https://seegson.example', 'street' => '77 Rue de la Paix', 'city' => 'Paris', 'zipCode' => '75001', 'group' => 'Weyland-Yutani Group'],
            ['display' => 'Tricell Pharmaceuticals', 'email' => 'info@tricell.example', 'country' => 'ZA', 'category' => 'Digital Marketing', 'phone' => '+27 11 555 1200', 'url' => 'https://tricell.example', 'street' => '15 Kijuju Business Park', 'city' => 'Johannesburg', 'zipCode' => '2000', 'group' => 'Umbrella Group'],
            ['display' => 'TerraSave International', 'email' => 'hello@terrasave.example', 'country' => 'AU', 'category' => 'Content Creation', 'phone' => '+61 2 5555 1300', 'url' => 'https://terrasave.example', 'street' => '88 Resident Way', 'city' => 'Sydney', 'zipCode' => '2000', 'group' => 'Umbrella Group'],
            ['display' => 'Blue Umbrella Ltd', 'email' => 'contact@blueumbrella.example', 'country' => 'GB', 'category' => 'Marketing & Sales', 'phone' => '+44 20 7555 1400', 'url' => 'https://blueumbrella.example', 'street' => '10 Downing Street', 'city' => 'London', 'zipCode' => 'SW1A 2AA', 'group' => 'Umbrella Group'],
        ];

        foreach ($companiesData as $index => $data) {
            $category = $manager->getRepository(Category::class)->findOneBy(['name' => $data['category']]);
            $group = $manager->getRepository(CompanyGroup::class)->findOneBy(['name' => $data['group']]);

            // Randomly assign a demo logo
            $randomLogo = $demoLogos[array_rand($demoLogos)];

            $company = (new Company())
                ->setName($data['display'])
                ->setEmail($data['email'])
                ->setCountryCode($data['country'])
                ->setCategory($category)
                ->setPhone($data['phone'])
                ->setUrl($data['url'])
                ->setStreet($data['street'])
                ->setCity($data['city'])
                ->setZip($data['zipCode'])
                ->setCompanyGroup($group)
                ->setImagePath($randomLogo);

            $manager->persist($company);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            CompanyGroupFixtures::class,
        ];
    }
}
