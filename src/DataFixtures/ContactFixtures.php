<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\Contact;
use C3net\CoreBundle\Entity\Department;
use C3net\CoreBundle\Enum\DomainEntityType;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ContactFixtures extends AbstractCategorizableFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $contactsData = [
            // COMPANY 0 (Cyberdyne Systems) - Technology Hierarchy
            ['firstName' => 'John', 'lastName' => 'Doe', 'email' => 'john.doe@cyberdyne.example', 'phone' => '+1 555 1001', 'cell' => '+1 555 2001', 'company' => 'Cyberdyne Systems', 'academicTitle' => null, 'position' => 'Chief Executive Officer', 'department' => 'MKT', 'hierarchy_level' => 1, 'parent_email' => null, 'categories' => ['Business Services', 'Management Consulting']],
            ['firstName' => 'Michael', 'lastName' => 'Brown', 'email' => 'michael.brown@cyberdyne.example', 'phone' => '+1 555 1004', 'cell' => '+1 555 2004', 'company' => 'Cyberdyne Systems', 'academicTitle' => null, 'position' => 'Chief Technology Officer', 'department' => 'ENG', 'hierarchy_level' => 2, 'parent_email' => 'john.doe@cyberdyne.example', 'categories' => ['Technology', 'Software Solutions', 'DevOps & Infrastructure']],
            ['firstName' => 'Sarah', 'lastName' => 'Connor', 'email' => 'sarah.connor@cyberdyne.example', 'phone' => '+1 555 1024', 'cell' => '+1 555 2024', 'company' => 'Cyberdyne Systems', 'academicTitle' => 'Ms.', 'position' => 'VP of Operations', 'department' => 'RND', 'hierarchy_level' => 2, 'parent_email' => 'john.doe@cyberdyne.example', 'categories' => ['Business Services', 'Management Consulting']],
            ['firstName' => 'Kyle', 'lastName' => 'Reese', 'email' => 'kyle.reese@cyberdyne.example', 'phone' => '+1 555 1025', 'cell' => '+1 555 2025', 'company' => 'Cyberdyne Systems', 'academicTitle' => null, 'position' => 'Lead Software Engineer', 'department' => 'ENG', 'hierarchy_level' => 3, 'parent_email' => 'michael.brown@cyberdyne.example', 'categories' => ['Software Solutions', 'Web Development']],
            ['firstName' => 'Miles', 'lastName' => 'Dyson', 'email' => 'miles.dyson@cyberdyne.example', 'phone' => '+1 555 1026', 'cell' => '+1 555 2026', 'company' => 'Cyberdyne Systems', 'academicTitle' => 'Dr.', 'position' => 'Senior Research Manager', 'department' => 'RND', 'hierarchy_level' => 3, 'parent_email' => 'michael.brown@cyberdyne.example', 'categories' => ['AI & Machine Learning', 'Technology']],
            ['firstName' => 'Catherine', 'lastName' => 'Brewster', 'email' => 'catherine.brewster@cyberdyne.example', 'phone' => '+1 555 1027', 'cell' => '+1 555 2027', 'company' => 'Cyberdyne Systems', 'academicTitle' => 'Dr.', 'position' => 'Operations Manager', 'department' => 'RND', 'hierarchy_level' => 3, 'parent_email' => 'sarah.connor@cyberdyne.example', 'categories' => ['Management Consulting', 'Business Services']],
            // COMPANY 1 (Stark Industries) - Innovation Hierarchy
            ['firstName' => 'Jane', 'lastName' => 'Smith', 'email' => 'jane.smith@stark.example', 'phone' => '+1 555 1002', 'cell' => '+1 555 2002', 'company' => 'Stark Industries', 'academicTitle' => 'Ms.', 'position' => 'Chief Executive Officer', 'department' => 'PR', 'hierarchy_level' => 1, 'parent_email' => null, 'categories' => ['Business Services', 'Strategy Consulting']],
            ['firstName' => 'Tony', 'lastName' => 'Stark', 'email' => 'tony.stark@stark.example', 'phone' => '+1 555 1005', 'cell' => '+1 555 2005', 'company' => 'Stark Industries', 'academicTitle' => 'Dr.', 'position' => 'Chief Innovation Officer', 'department' => 'ADVTECH', 'hierarchy_level' => 2, 'parent_email' => 'jane.smith@stark.example', 'categories' => ['AI & Machine Learning', 'Technology', 'Software Solutions']],
            ['firstName' => 'Pepper', 'lastName' => 'Potts', 'email' => 'pepper.potts@stark.example', 'phone' => '+1 555 1028', 'cell' => '+1 555 2028', 'company' => 'Stark Industries', 'academicTitle' => 'Ms.', 'position' => 'Chief Operating Officer', 'department' => 'PR', 'hierarchy_level' => 2, 'parent_email' => 'jane.smith@stark.example', 'categories' => ['Business Services', 'Management Consulting']],
            ['firstName' => 'James', 'lastName' => 'Rhodes', 'email' => 'james.rhodes@stark.example', 'phone' => '+1 555 1029', 'cell' => '+1 555 2029', 'company' => 'Stark Industries', 'academicTitle' => 'Col.', 'position' => 'Senior Engineering Manager', 'department' => 'ADVTECH', 'hierarchy_level' => 3, 'parent_email' => 'tony.stark@stark.example', 'categories' => ['DevOps & Infrastructure', 'Software Solutions']],
            ['firstName' => 'Bruce', 'lastName' => 'Banner', 'email' => 'bruce.banner@stark.example', 'phone' => '+1 555 1030', 'cell' => '+1 555 2030', 'company' => 'Stark Industries', 'academicTitle' => 'Dr.', 'position' => 'Lead Research Scientist', 'department' => 'ADVTECH', 'hierarchy_level' => 3, 'parent_email' => 'tony.stark@stark.example', 'categories' => ['AI & Machine Learning', 'Technology']],
            ['firstName' => 'Happy', 'lastName' => 'Hogan', 'email' => 'happy.hogan@stark.example', 'phone' => '+1 555 1031', 'cell' => '+1 555 2031', 'company' => 'Stark Industries', 'academicTitle' => null, 'position' => 'Operations Team Lead', 'department' => 'PR', 'hierarchy_level' => 3, 'parent_email' => 'pepper.potts@stark.example', 'categories' => ['Management Consulting', 'Business Services']],
            // COMPANY 2 (Wayne Enterprises) - Security & Finance Hierarchy
            ['firstName' => 'Alice', 'lastName' => 'Johnson', 'email' => 'alice.johnson@wayne.example', 'phone' => '+1 555 1003', 'cell' => '+1 555 2003', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Dr.', 'position' => 'Chief Executive Officer', 'department' => 'CORP', 'hierarchy_level' => 1, 'parent_email' => null, 'categories' => ['Business Services', 'Financial Services']],
            ['firstName' => 'Bruce', 'lastName' => 'Wayne', 'email' => 'bruce.wayne@wayne.example', 'phone' => '+1 555 1006', 'cell' => '+1 555 2006', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Mr.', 'position' => 'Chairman of the Board', 'department' => 'CORP', 'hierarchy_level' => 2, 'parent_email' => 'alice.johnson@wayne.example', 'categories' => ['Business Services', 'Strategy Consulting', 'Financial Services']],
            ['firstName' => 'Lucius', 'lastName' => 'Fox', 'email' => 'lucius.fox@wayne.example', 'phone' => '+1 555 1032', 'cell' => '+1 555 2032', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Mr.', 'position' => 'Chief Technology Officer', 'department' => 'SCI', 'hierarchy_level' => 2, 'parent_email' => 'alice.johnson@wayne.example', 'categories' => ['Technology', 'Cybersecurity']],
            ['firstName' => 'Alfred', 'lastName' => 'Pennyworth', 'email' => 'alfred.pennyworth@wayne.example', 'phone' => '+1 555 1033', 'cell' => '+1 555 2033', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Mr.', 'position' => 'Executive Assistant Manager', 'department' => 'CORP', 'hierarchy_level' => 3, 'parent_email' => 'bruce.wayne@wayne.example', 'categories' => ['Business Services', 'Management Consulting']],
            ['firstName' => 'Barbara', 'lastName' => 'Gordon', 'email' => 'barbara.gordon@wayne.example', 'phone' => '+1 555 1034', 'cell' => '+1 555 2034', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Ms.', 'position' => 'IT Security Manager', 'department' => 'SCI', 'hierarchy_level' => 3, 'parent_email' => 'lucius.fox@wayne.example', 'categories' => ['Cybersecurity', 'IT Consulting']],
            ['firstName' => 'Harvey', 'lastName' => 'Dent', 'email' => 'harvey.dent@wayne.example', 'phone' => '+1 555 1035', 'cell' => '+1 555 2035', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Mr.', 'position' => 'Legal Affairs Manager', 'department' => 'SCI', 'hierarchy_level' => 3, 'parent_email' => 'lucius.fox@wayne.example', 'categories' => ['Legal Services', 'Business Services']],
            // Additional flat contacts for other companies (no department - Oscorp doesn't have departments in fixtures)
            ['firstName' => 'Emma', 'lastName' => 'Martinez', 'email' => 'emma.martinez@oscorp.example', 'phone' => '+1 555 1007', 'cell' => '+1 555 2007', 'company' => 'Oscorp', 'academicTitle' => null, 'position' => 'Systems Architect', 'department' => null, 'categories' => ['Software Solutions', 'Technology']],
        ];

        // Create contacts in two passes to support hierarchy
        // Pass 1: Create all contacts without parent relationships
        $contacts = [];
        foreach ($contactsData as $index => $contactData) {
            $company = $manager->getRepository(Company::class)->findOneBy(['name' => $contactData['company']]);
            $categories = $this->findCategoriesByNames($manager, $contactData['categories']);

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

            if (isset($contactData['department']) && $contactData['department']) {
                // Find department by shortcode within the contact's company
                $department = $manager->getRepository(Department::class)->findOneBy([
                    'shortcode' => $contactData['department'],
                    'company' => $company,
                ]);

                if ($department) {
                    $contact->setDepartment($department);
                }
            }

            // Persist and flush to get ID
            $this->persistAndFlush($manager, $contact);

            // Assign multiple categories
            $this->assignCategories($manager, $contact, $categories, DomainEntityType::Contact);

            $contacts[$contactData['email']] = $contact;
        }

        $this->flushSafely($manager);

        // Pass 2: Set up parent-child relationships for hierarchy
        foreach ($contactsData as $contactData) {
            if (isset($contactData['parent_email']) && $contactData['parent_email']) {
                $contact = $contacts[$contactData['email']];
                $parent = $contacts[$contactData['parent_email']];

                $contact->setParent($parent);
                $manager->persist($contact);
            }
        }

        $manager->flush();

        // Pass 3: Set up standin relationships (same company only)
        $standinRelationships = [
            // Cyberdyne Systems standins
            'miles.dyson@cyberdyne.example' => 'kyle.reese@cyberdyne.example', // Miles Dyson's standin is Kyle Reese (both in Technology)
            'kyle.reese@cyberdyne.example' => 'miles.dyson@cyberdyne.example', // Kyle Reese's standin is Miles Dyson (both in Technology)
            'catherine.brewster@cyberdyne.example' => 'sarah.connor@cyberdyne.example', // Catherine's standin is Sarah (both in Operations hierarchy)
            // Stark Industries standins
            'bruce.banner@stark.example' => 'james.rhodes@stark.example', // Bruce Banner's standin is James Rhodes (both in R&D)
            'james.rhodes@stark.example' => 'bruce.banner@stark.example', // James Rhodes' standin is Bruce Banner (both in R&D)
            'happy.hogan@stark.example' => 'pepper.potts@stark.example', // Happy's standin is Pepper (both in Operations hierarchy)
            // Wayne Enterprises standins
            'barbara.gordon@wayne.example' => 'lucius.fox@wayne.example', // Barbara's standin is Lucius (both in Technology hierarchy)
            'harvey.dent@wayne.example' => 'alfred.pennyworth@wayne.example', // Harvey's standin is Alfred (both report to executive level)
        ];

        foreach ($standinRelationships as $contactEmail => $standinEmail) {
            if (isset($contacts[$contactEmail]) && isset($contacts[$standinEmail])) {
                $contact = $contacts[$contactEmail];
                $standin = $contacts[$standinEmail];

                // Verify they're from the same company
                if ($contact->getCompany() === $standin->getCompany()) {
                    $contact->setStandin($standin);
                    $manager->persist($contact);
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            CompanyFixtures::class,
            DepartmentFixtures::class,
        ];
    }
}
