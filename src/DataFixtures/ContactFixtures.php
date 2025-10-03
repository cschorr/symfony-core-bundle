<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\Contact;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ContactFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $contactsData = [
            // COMPANY 0 (Cyberdyne Systems) - Technology Hierarchy
            ['firstName' => 'John', 'lastName' => 'Doe', 'email' => 'john.doe@cyberdyne.example', 'phone' => '+1 555 1001', 'cell' => '+1 555 2001', 'company' => 'company_0', 'academicTitle' => null, 'position' => 'Chief Executive Officer', 'department' => 'Executive', 'hierarchy_level' => 1, 'parent_key' => null],
            ['firstName' => 'Michael', 'lastName' => 'Brown', 'email' => 'michael.brown@cyberdyne.example', 'phone' => '+1 555 1004', 'cell' => '+1 555 2004', 'company' => 'company_0', 'academicTitle' => null, 'position' => 'Chief Technology Officer', 'department' => 'Technology', 'hierarchy_level' => 2, 'parent_key' => 'contact_0'],
            ['firstName' => 'Sarah', 'lastName' => 'Connor', 'email' => 'sarah.connor@cyberdyne.example', 'phone' => '+1 555 1024', 'cell' => '+1 555 2024', 'company' => 'company_0', 'academicTitle' => 'Ms.', 'position' => 'VP of Operations', 'department' => 'Operations', 'hierarchy_level' => 2, 'parent_key' => 'contact_0'],
            ['firstName' => 'Kyle', 'lastName' => 'Reese', 'email' => 'kyle.reese@cyberdyne.example', 'phone' => '+1 555 1025', 'cell' => '+1 555 2025', 'company' => 'company_0', 'academicTitle' => null, 'position' => 'Lead Software Engineer', 'department' => 'Technology', 'hierarchy_level' => 3, 'parent_key' => 'contact_1'],
            ['firstName' => 'Miles', 'lastName' => 'Dyson', 'email' => 'miles.dyson@cyberdyne.example', 'phone' => '+1 555 1026', 'cell' => '+1 555 2026', 'company' => 'company_0', 'academicTitle' => 'Dr.', 'position' => 'Senior Research Manager', 'department' => 'Technology', 'hierarchy_level' => 3, 'parent_key' => 'contact_1'],
            ['firstName' => 'Catherine', 'lastName' => 'Brewster', 'email' => 'catherine.brewster@cyberdyne.example', 'phone' => '+1 555 1027', 'cell' => '+1 555 2027', 'company' => 'company_0', 'academicTitle' => 'Dr.', 'position' => 'Operations Manager', 'department' => 'Operations', 'hierarchy_level' => 3, 'parent_key' => 'contact_2'],
            // COMPANY 1 (Stark Industries) - Innovation Hierarchy
            ['firstName' => 'Jane', 'lastName' => 'Smith', 'email' => 'jane.smith@stark.example', 'phone' => '+1 555 1002', 'cell' => '+1 555 2002', 'company' => 'company_1', 'academicTitle' => 'Ms.', 'position' => 'Chief Executive Officer', 'department' => 'Executive', 'hierarchy_level' => 1, 'parent_key' => null],
            ['firstName' => 'Tony', 'lastName' => 'Stark', 'email' => 'tony.stark@stark.example', 'phone' => '+1 555 1005', 'cell' => '+1 555 2005', 'company' => 'company_1', 'academicTitle' => 'Dr.', 'position' => 'Chief Innovation Officer', 'department' => 'Research & Development', 'hierarchy_level' => 2, 'parent_key' => 'contact_6'],
            ['firstName' => 'Pepper', 'lastName' => 'Potts', 'email' => 'pepper.potts@stark.example', 'phone' => '+1 555 1028', 'cell' => '+1 555 2028', 'company' => 'company_1', 'academicTitle' => 'Ms.', 'position' => 'Chief Operating Officer', 'department' => 'Operations', 'hierarchy_level' => 2, 'parent_key' => 'contact_6'],
            ['firstName' => 'James', 'lastName' => 'Rhodes', 'email' => 'james.rhodes@stark.example', 'phone' => '+1 555 1029', 'cell' => '+1 555 2029', 'company' => 'company_1', 'academicTitle' => 'Col.', 'position' => 'Senior Engineering Manager', 'department' => 'Research & Development', 'hierarchy_level' => 3, 'parent_key' => 'contact_7'],
            ['firstName' => 'Bruce', 'lastName' => 'Banner', 'email' => 'bruce.banner@stark.example', 'phone' => '+1 555 1030', 'cell' => '+1 555 2030', 'company' => 'company_1', 'academicTitle' => 'Dr.', 'position' => 'Lead Research Scientist', 'department' => 'Research & Development', 'hierarchy_level' => 3, 'parent_key' => 'contact_7'],
            ['firstName' => 'Happy', 'lastName' => 'Hogan', 'email' => 'happy.hogan@stark.example', 'phone' => '+1 555 1031', 'cell' => '+1 555 2031', 'company' => 'company_1', 'academicTitle' => null, 'position' => 'Operations Team Lead', 'department' => 'Operations', 'hierarchy_level' => 3, 'parent_key' => 'contact_8'],
            // COMPANY 2 (Wayne Enterprises) - Security & Finance Hierarchy
            ['firstName' => 'Alice', 'lastName' => 'Johnson', 'email' => 'alice.johnson@wayne.example', 'phone' => '+1 555 1003', 'cell' => '+1 555 2003', 'company' => 'company_2', 'academicTitle' => 'Dr.', 'position' => 'Chief Executive Officer', 'department' => 'Executive', 'hierarchy_level' => 1, 'parent_key' => null],
            ['firstName' => 'Bruce', 'lastName' => 'Wayne', 'email' => 'bruce.wayne@wayne.example', 'phone' => '+1 555 1006', 'cell' => '+1 555 2006', 'company' => 'company_2', 'academicTitle' => 'Mr.', 'position' => 'Chairman of the Board', 'department' => 'Executive', 'hierarchy_level' => 2, 'parent_key' => 'contact_12'],
            ['firstName' => 'Lucius', 'lastName' => 'Fox', 'email' => 'lucius.fox@wayne.example', 'phone' => '+1 555 1032', 'cell' => '+1 555 2032', 'company' => 'company_2', 'academicTitle' => 'Mr.', 'position' => 'Chief Technology Officer', 'department' => 'Technology', 'hierarchy_level' => 2, 'parent_key' => 'contact_12'],
            ['firstName' => 'Alfred', 'lastName' => 'Pennyworth', 'email' => 'alfred.pennyworth@wayne.example', 'phone' => '+1 555 1033', 'cell' => '+1 555 2033', 'company' => 'company_2', 'academicTitle' => 'Mr.', 'position' => 'Executive Assistant Manager', 'department' => 'Executive', 'hierarchy_level' => 3, 'parent_key' => 'contact_13'],
            ['firstName' => 'Barbara', 'lastName' => 'Gordon', 'email' => 'barbara.gordon@wayne.example', 'phone' => '+1 555 1034', 'cell' => '+1 555 2034', 'company' => 'company_2', 'academicTitle' => 'Ms.', 'position' => 'IT Security Manager', 'department' => 'Technology', 'hierarchy_level' => 3, 'parent_key' => 'contact_14'],
            ['firstName' => 'Harvey', 'lastName' => 'Dent', 'email' => 'harvey.dent@wayne.example', 'phone' => '+1 555 1035', 'cell' => '+1 555 2035', 'company' => 'company_2', 'academicTitle' => 'Mr.', 'position' => 'Legal Affairs Manager', 'department' => 'Legal', 'hierarchy_level' => 3, 'parent_key' => 'contact_14'],
            // Additional flat contacts for other companies
            ['firstName' => 'Emma', 'lastName' => 'Martinez', 'email' => 'emma.martinez@oscorp.example', 'phone' => '+1 555 1007', 'cell' => '+1 555 2007', 'company' => 'company_3', 'academicTitle' => null, 'position' => 'Systems Architect', 'department' => 'Engineering'],
        ];

        // Create contacts in two passes to support hierarchy
        // Pass 1: Create all contacts without parent relationships
        foreach ($contactsData as $index => $contactData) {
            $company = $this->getReference($contactData['company'], Company::class);

            $contact = (new Contact())
                ->setNameFirst($contactData['firstName'])
                ->setNameLast($contactData['lastName'])
                ->setEmail($contactData['email'])
                ->setPhone($contactData['phone'])
                ->setCell($contactData['cell'])
                ->setCompany($company);

            if (isset($contactData['academicTitle'])) {
                $contact->setAcademicTitle($contactData['academicTitle']);
            }

            if (isset($contactData['position'])) {
                $contact->setPosition($contactData['position']);
            }

            if (isset($contactData['department'])) {
                $contact->setDepartment($contactData['department']);
            }

            $manager->persist($contact);
            $this->addReference('contact_' . $index, $contact);
        }

        $manager->flush();

        // Pass 2: Set up parent-child relationships for hierarchy
        foreach ($contactsData as $index => $contactData) {
            if (isset($contactData['parent_key']) && $contactData['parent_key']) {
                $contact = $this->getReference('contact_' . $index, Contact::class);
                $parent = $this->getReference($contactData['parent_key'], Contact::class);

                $contact->setParent($parent);
                $manager->persist($contact);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CompanyFixtures::class,
        ];
    }
}
