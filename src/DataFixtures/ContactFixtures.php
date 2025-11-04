<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\Contact;
use C3net\CoreBundle\Entity\Department;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Enum\Gender;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ContactFixtures extends AbstractCategorizableFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $contactsData = [
            // COMPANY 0 (Cyberdyne Systems) - Technology Hierarchy
            ['firstName' => 'John', 'lastName' => 'Doe', 'email' => 'john.doe@cyberdyne.example', 'phone' => '+1 555 1001', 'cell' => '+1 555 2001', 'company' => 'Cyberdyne Systems', 'academicTitle' => null, 'position' => 'Chief Executive Officer', 'department' => 'MKT', 'hierarchy_level' => 1, 'parent_email' => null, 'gender' => Gender::MALE, 'categories' => ['Business Services', 'Management Consulting']],
            ['firstName' => 'Michael', 'lastName' => 'Brown', 'email' => 'michael.brown@cyberdyne.example', 'phone' => '+1 555 1004', 'cell' => '+1 555 2004', 'company' => 'Cyberdyne Systems', 'academicTitle' => null, 'position' => 'Chief Technology Officer', 'department' => 'ENG', 'hierarchy_level' => 2, 'parent_email' => 'john.doe@cyberdyne.example', 'gender' => Gender::MALE, 'categories' => ['Technology', 'Software Solutions', 'DevOps & Infrastructure']],
            ['firstName' => 'Sarah', 'lastName' => 'Connor', 'email' => 'sarah.connor@cyberdyne.example', 'phone' => '+1 555 1024', 'cell' => '+1 555 2024', 'company' => 'Cyberdyne Systems', 'academicTitle' => 'Ms.', 'position' => 'VP of Operations', 'department' => 'RND', 'hierarchy_level' => 2, 'parent_email' => 'john.doe@cyberdyne.example', 'gender' => Gender::FEMALE, 'categories' => ['Business Services', 'Management Consulting']],
            ['firstName' => 'Kyle', 'lastName' => 'Reese', 'email' => 'kyle.reese@cyberdyne.example', 'phone' => '+1 555 1025', 'cell' => '+1 555 2025', 'company' => 'Cyberdyne Systems', 'academicTitle' => null, 'position' => 'Lead Software Engineer', 'department' => 'ENG', 'hierarchy_level' => 3, 'parent_email' => 'michael.brown@cyberdyne.example', 'gender' => Gender::MALE, 'categories' => ['Software Solutions', 'Web Development']],
            ['firstName' => 'Miles', 'lastName' => 'Dyson', 'email' => 'miles.dyson@cyberdyne.example', 'phone' => '+1 555 1026', 'cell' => '+1 555 2026', 'company' => 'Cyberdyne Systems', 'academicTitle' => 'Dr.', 'position' => 'Senior Research Manager', 'department' => 'RND', 'hierarchy_level' => 3, 'parent_email' => 'michael.brown@cyberdyne.example', 'gender' => Gender::NON_BINARY, 'categories' => ['AI & Machine Learning', 'Technology']],
            ['firstName' => 'Catherine', 'lastName' => 'Brewster', 'email' => 'catherine.brewster@cyberdyne.example', 'phone' => '+1 555 1027', 'cell' => '+1 555 2027', 'company' => 'Cyberdyne Systems', 'academicTitle' => 'Dr.', 'position' => 'Operations Manager', 'department' => 'RND', 'hierarchy_level' => 3, 'parent_email' => 'sarah.connor@cyberdyne.example', 'gender' => Gender::FEMALE, 'categories' => ['Management Consulting', 'Business Services']],
            // COMPANY 1 (Stark Industries) - Innovation Hierarchy
            ['firstName' => 'Jane', 'lastName' => 'Smith', 'email' => 'jane.smith@stark.example', 'phone' => '+1 555 1002', 'cell' => '+1 555 2002', 'company' => 'Stark Industries', 'academicTitle' => 'Ms.', 'position' => 'Chief Executive Officer', 'department' => 'PR', 'hierarchy_level' => 1, 'parent_email' => null, 'gender' => Gender::FEMALE, 'categories' => ['Business Services', 'Strategy Consulting']],
            ['firstName' => 'Tony', 'lastName' => 'Stark', 'email' => 'tony.stark@stark.example', 'phone' => '+1 555 1005', 'cell' => '+1 555 2005', 'company' => 'Stark Industries', 'academicTitle' => 'Dr.', 'position' => 'Chief Innovation Officer', 'department' => 'ADVTECH', 'hierarchy_level' => 2, 'parent_email' => 'jane.smith@stark.example', 'gender' => Gender::MALE, 'categories' => ['AI & Machine Learning', 'Technology', 'Software Solutions']],
            ['firstName' => 'Pepper', 'lastName' => 'Potts', 'email' => 'pepper.potts@stark.example', 'phone' => '+1 555 1028', 'cell' => '+1 555 2028', 'company' => 'Stark Industries', 'academicTitle' => 'Ms.', 'position' => 'Chief Operating Officer', 'department' => 'PR', 'hierarchy_level' => 2, 'parent_email' => 'jane.smith@stark.example', 'gender' => Gender::FEMALE, 'categories' => ['Business Services', 'Management Consulting']],
            ['firstName' => 'James', 'lastName' => 'Rhodes', 'email' => 'james.rhodes@stark.example', 'phone' => '+1 555 1029', 'cell' => '+1 555 2029', 'company' => 'Stark Industries', 'academicTitle' => 'Col.', 'position' => 'Senior Engineering Manager', 'department' => 'ADVTECH', 'hierarchy_level' => 3, 'parent_email' => 'tony.stark@stark.example', 'gender' => Gender::MALE, 'categories' => ['DevOps & Infrastructure', 'Software Solutions']],
            ['firstName' => 'Bruce', 'lastName' => 'Banner', 'email' => 'bruce.banner@stark.example', 'phone' => '+1 555 1030', 'cell' => '+1 555 2030', 'company' => 'Stark Industries', 'academicTitle' => 'Dr.', 'position' => 'Lead Research Scientist', 'department' => 'ADVTECH', 'hierarchy_level' => 3, 'parent_email' => 'tony.stark@stark.example', 'gender' => Gender::DIVERSE, 'categories' => ['AI & Machine Learning', 'Technology']],
            ['firstName' => 'Happy', 'lastName' => 'Hogan', 'email' => 'happy.hogan@stark.example', 'phone' => '+1 555 1031', 'cell' => '+1 555 2031', 'company' => 'Stark Industries', 'academicTitle' => null, 'position' => 'Operations Team Lead', 'department' => 'PR', 'hierarchy_level' => 3, 'parent_email' => 'pepper.potts@stark.example', 'gender' => Gender::MALE, 'categories' => ['Management Consulting', 'Business Services']],
            // COMPANY 2 (Wayne Enterprises) - Security & Finance Hierarchy
            ['firstName' => 'Alice', 'lastName' => 'Johnson', 'email' => 'alice.johnson@wayne.example', 'phone' => '+1 555 1003', 'cell' => '+1 555 2003', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Dr.', 'position' => 'Chief Executive Officer', 'department' => 'CORP', 'hierarchy_level' => 1, 'parent_email' => null, 'gender' => Gender::FEMALE, 'categories' => ['Business Services', 'Financial Services']],
            ['firstName' => 'Bruce', 'lastName' => 'Wayne', 'email' => 'bruce.wayne@wayne.example', 'phone' => '+1 555 1006', 'cell' => '+1 555 2006', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Mr.', 'position' => 'Chairman of the Board', 'department' => 'CORP', 'hierarchy_level' => 2, 'parent_email' => 'alice.johnson@wayne.example', 'gender' => Gender::MALE, 'categories' => ['Business Services', 'Strategy Consulting', 'Financial Services']],
            ['firstName' => 'Lucius', 'lastName' => 'Fox', 'email' => 'lucius.fox@wayne.example', 'phone' => '+1 555 1032', 'cell' => '+1 555 2032', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Mr.', 'position' => 'Chief Technology Officer', 'department' => 'SCI', 'hierarchy_level' => 2, 'parent_email' => 'alice.johnson@wayne.example', 'gender' => Gender::MALE, 'categories' => ['Technology', 'Cybersecurity']],
            ['firstName' => 'Alfred', 'lastName' => 'Pennyworth', 'email' => 'alfred.pennyworth@wayne.example', 'phone' => '+1 555 1033', 'cell' => '+1 555 2033', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Mr.', 'position' => 'Executive Assistant Manager', 'department' => 'CORP', 'hierarchy_level' => 3, 'parent_email' => 'bruce.wayne@wayne.example', 'gender' => Gender::MALE, 'categories' => ['Business Services', 'Management Consulting']],
            ['firstName' => 'Barbara', 'lastName' => 'Gordon', 'email' => 'barbara.gordon@wayne.example', 'phone' => '+1 555 1034', 'cell' => '+1 555 2034', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Ms.', 'position' => 'IT Security Manager', 'department' => 'SCI', 'hierarchy_level' => 3, 'parent_email' => 'lucius.fox@wayne.example', 'gender' => Gender::NON_BINARY, 'categories' => ['Cybersecurity', 'IT Consulting']],
            ['firstName' => 'Harvey', 'lastName' => 'Dent', 'email' => 'harvey.dent@wayne.example', 'phone' => '+1 555 1035', 'cell' => '+1 555 2035', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Mr.', 'position' => 'Legal Affairs Manager', 'department' => 'SCI', 'hierarchy_level' => 3, 'parent_email' => 'lucius.fox@wayne.example', 'gender' => Gender::MALE, 'categories' => ['Legal Services', 'Business Services']],
            // Additional Cyberdyne Systems contacts (Engineering team expansion)
            ['firstName' => 'Thomas', 'lastName' => 'Anderson', 'email' => 'thomas.anderson@cyberdyne.example', 'phone' => '+1 555 1036', 'cell' => '+1 555 2036', 'company' => 'Cyberdyne Systems', 'academicTitle' => 'Mr.', 'position' => 'Software Engineer', 'department' => 'ENG', 'hierarchy_level' => 3, 'parent_email' => 'michael.brown@cyberdyne.example', 'gender' => Gender::MALE, 'categories' => ['Web Development', 'Software Solutions']],
            ['firstName' => 'Trinity', 'lastName' => 'Matrix', 'email' => 'trinity.matrix@cyberdyne.example', 'phone' => '+1 555 1037', 'cell' => '+1 555 2037', 'company' => 'Cyberdyne Systems', 'academicTitle' => 'Ms.', 'position' => 'Senior Software Engineer', 'department' => 'ENG', 'hierarchy_level' => 3, 'parent_email' => 'michael.brown@cyberdyne.example', 'gender' => Gender::FEMALE, 'categories' => ['Cybersecurity', 'Software Solutions']],
            ['firstName' => 'Morpheus', 'lastName' => 'Tech', 'email' => 'morpheus.tech@cyberdyne.example', 'phone' => '+1 555 1038', 'cell' => '+1 555 2038', 'company' => 'Cyberdyne Systems', 'academicTitle' => 'Dr.', 'position' => 'AI Research Lead', 'department' => 'RND', 'hierarchy_level' => 3, 'parent_email' => 'miles.dyson@cyberdyne.example', 'gender' => Gender::MALE, 'categories' => ['AI & Machine Learning', 'Technology']],
            // Additional Stark Industries contacts (expanding Advanced Tech and PR teams)
            ['firstName' => 'Natasha', 'lastName' => 'Romanoff', 'email' => 'natasha.romanoff@stark.example', 'phone' => '+1 555 1039', 'cell' => '+1 555 2039', 'company' => 'Stark Industries', 'academicTitle' => 'Ms.', 'position' => 'Strategic Communications Director', 'department' => 'PR', 'hierarchy_level' => 3, 'parent_email' => 'pepper.potts@stark.example', 'gender' => Gender::FEMALE, 'categories' => ['Marketing & Sales', 'Digital Marketing']],
            ['firstName' => 'Clint', 'lastName' => 'Barton', 'email' => 'clint.barton@stark.example', 'phone' => '+1 555 1040', 'cell' => '+1 555 2040', 'company' => 'Stark Industries', 'academicTitle' => 'Mr.', 'position' => 'Systems Integration Specialist', 'department' => 'ADVTECH', 'hierarchy_level' => 3, 'parent_email' => 'tony.stark@stark.example', 'gender' => Gender::MALE, 'categories' => ['DevOps & Infrastructure', 'IT Consulting']],
            ['firstName' => 'Wanda', 'lastName' => 'Maximoff', 'email' => 'wanda.maximoff@stark.example', 'phone' => '+1 555 1041', 'cell' => '+1 555 2041', 'company' => 'Stark Industries', 'academicTitle' => 'Dr.', 'position' => 'Machine Learning Engineer', 'department' => 'ADVTECH', 'hierarchy_level' => 3, 'parent_email' => 'bruce.banner@stark.example', 'gender' => Gender::DIVERSE, 'categories' => ['AI & Machine Learning', 'Software Solutions']],
            ['firstName' => 'Vision', 'lastName' => 'Stark', 'email' => 'vision.stark@stark.example', 'phone' => '+1 555 1042', 'cell' => '+1 555 2042', 'company' => 'Stark Industries', 'academicTitle' => 'Dr.', 'position' => 'AI Systems Architect', 'department' => 'ADVTECH', 'hierarchy_level' => 3, 'parent_email' => 'bruce.banner@stark.example', 'gender' => Gender::NON_BINARY, 'categories' => ['AI & Machine Learning', 'Software Solutions']],
            // Additional Wayne Enterprises contacts (expanding Security and Corporate teams)
            ['firstName' => 'Selina', 'lastName' => 'Kyle', 'email' => 'selina.kyle@wayne.example', 'phone' => '+1 555 1043', 'cell' => '+1 555 2043', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Ms.', 'position' => 'Security Specialist', 'department' => 'SCI', 'hierarchy_level' => 3, 'parent_email' => 'lucius.fox@wayne.example', 'gender' => Gender::FEMALE, 'categories' => ['Cybersecurity', 'IT Consulting']],
            ['firstName' => 'Dick', 'lastName' => 'Grayson', 'email' => 'dick.grayson@wayne.example', 'phone' => '+1 555 1044', 'cell' => '+1 555 2044', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Mr.', 'position' => 'Corporate Strategy Manager', 'department' => 'CORP', 'hierarchy_level' => 3, 'parent_email' => 'bruce.wayne@wayne.example', 'gender' => Gender::MALE, 'categories' => ['Strategy Consulting', 'Business Services']],
            ['firstName' => 'Tim', 'lastName' => 'Drake', 'email' => 'tim.drake@wayne.example', 'phone' => '+1 555 1045', 'cell' => '+1 555 2045', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Mr.', 'position' => 'Junior Security Analyst', 'department' => 'SCI', 'hierarchy_level' => 3, 'parent_email' => 'barbara.gordon@wayne.example', 'gender' => Gender::MALE, 'categories' => ['Cybersecurity', 'Technology']],
            ['firstName' => 'Jason', 'lastName' => 'Todd', 'email' => 'jason.todd@wayne.example', 'phone' => '+1 555 1046', 'cell' => '+1 555 2046', 'company' => 'Wayne Enterprises', 'academicTitle' => 'Mr.', 'position' => 'Risk Management Specialist', 'department' => 'CORP', 'hierarchy_level' => 3, 'parent_email' => 'bruce.wayne@wayne.example', 'gender' => Gender::MALE, 'categories' => ['Financial Services', 'Business Services']],
            // Additional flat contacts for other companies (no department - Oscorp doesn't have departments in fixtures)
            ['firstName' => 'Emma', 'lastName' => 'Martinez', 'email' => 'emma.martinez@oscorp.example', 'phone' => '+1 555 1007', 'cell' => '+1 555 2007', 'company' => 'Oscorp', 'academicTitle' => null, 'position' => 'Systems Architect', 'department' => null, 'gender' => Gender::PREFER_NOT_TO_SAY, 'categories' => ['Software Solutions', 'Technology']],
            ['firstName' => 'Norman', 'lastName' => 'Osborn', 'email' => 'norman.osborn@oscorp.example', 'phone' => '+1 555 1047', 'cell' => '+1 555 2047', 'company' => 'Oscorp', 'academicTitle' => 'Dr.', 'position' => 'Chief Executive Officer', 'department' => null, 'gender' => Gender::MALE, 'categories' => ['Business Services', 'Strategy Consulting']],
            ['firstName' => 'Otto', 'lastName' => 'Octavius', 'email' => 'otto.octavius@oscorp.example', 'phone' => '+1 555 1048', 'cell' => '+1 555 2048', 'company' => 'Oscorp', 'academicTitle' => 'Dr.', 'position' => 'Chief Scientist', 'department' => null, 'gender' => Gender::MALE, 'categories' => ['AI & Machine Learning', 'Technology']],
            ['firstName' => 'Curt', 'lastName' => 'Connors', 'email' => 'curt.connors@oscorp.example', 'phone' => '+1 555 1049', 'cell' => '+1 555 2049', 'company' => 'Oscorp', 'academicTitle' => 'Dr.', 'position' => 'Senior Research Scientist', 'department' => null, 'gender' => Gender::MALE, 'categories' => ['Technology', 'AI & Machine Learning']],
            // Additional companies - expanding demo data
            ['firstName' => 'Lex', 'lastName' => 'Luthor', 'email' => 'lex.luthor@lexcorp.example', 'phone' => '+1 555 1050', 'cell' => '+1 555 2050', 'company' => 'LexCorp', 'academicTitle' => 'Mr.', 'position' => 'CEO', 'department' => null, 'gender' => Gender::MALE, 'categories' => ['Business Services', 'Strategy Consulting']],
            ['firstName' => 'Mercy', 'lastName' => 'Graves', 'email' => 'mercy.graves@lexcorp.example', 'phone' => '+1 555 1051', 'cell' => '+1 555 2051', 'company' => 'LexCorp', 'academicTitle' => 'Ms.', 'position' => 'Executive Assistant', 'department' => null, 'gender' => Gender::FEMALE, 'categories' => ['Business Services', 'Management Consulting']],
            ['firstName' => 'Diana', 'lastName' => 'Prince', 'email' => 'diana.prince@themyscira.example', 'phone' => '+1 555 1052', 'cell' => '+1 555 2052', 'company' => 'Themyscira Corp', 'academicTitle' => 'Dr.', 'position' => 'Director of International Relations', 'department' => null, 'gender' => Gender::FEMALE, 'categories' => ['Business Services', 'Strategy Consulting']],
            ['firstName' => 'Steve', 'lastName' => 'Trevor', 'email' => 'steve.trevor@themyscira.example', 'phone' => '+1 555 1053', 'cell' => '+1 555 2053', 'company' => 'Themyscira Corp', 'academicTitle' => 'Mr.', 'position' => 'Operations Manager', 'department' => null, 'gender' => Gender::MALE, 'categories' => ['Management Consulting', 'Business Services']],
        ];

        // Create contacts in two passes to support hierarchy
        // Pass 1: Create all contacts without parent relationships
        $contacts = [];
        foreach ($contactsData as $contactData) {
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

            if (isset($contactData['gender'])) {
                $contact->setGender($contactData['gender']);
            }

            if (isset($contactData['department']) && $contactData['department']) {
                // Find department by shortcode within the contact's company
                $department = $manager->getRepository(Department::class)->findOneBy([
                    'shortcode' => $contactData['department'],
                    'company' => $company,
                ]);

                if ($department !== null) {
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
            'thomas.anderson@cyberdyne.example' => 'trinity.matrix@cyberdyne.example', // Thomas Anderson's standin is Trinity (both engineers)
            'trinity.matrix@cyberdyne.example' => 'kyle.reese@cyberdyne.example', // Trinity's standin is Kyle (senior engineers in same dept)
            'morpheus.tech@cyberdyne.example' => 'miles.dyson@cyberdyne.example', // Morpheus' standin is Miles (both AI/R&D leaders)
            // Stark Industries standins
            'bruce.banner@stark.example' => 'james.rhodes@stark.example', // Bruce Banner's standin is James Rhodes (both in R&D)
            'james.rhodes@stark.example' => 'bruce.banner@stark.example', // James Rhodes' standin is Bruce Banner (both in R&D)
            'happy.hogan@stark.example' => 'pepper.potts@stark.example', // Happy's standin is Pepper (both in Operations hierarchy)
            'natasha.romanoff@stark.example' => 'pepper.potts@stark.example', // Natasha's standin is Pepper (both in PR/Operations)
            'clint.barton@stark.example' => 'james.rhodes@stark.example', // Clint's standin is James (both technical specialists)
            'wanda.maximoff@stark.example' => 'vision.stark@stark.example', // Wanda's standin is Vision (both AI engineers)
            'vision.stark@stark.example' => 'bruce.banner@stark.example', // Vision's standin is Bruce (both research scientists)
            // Wayne Enterprises standins
            'barbara.gordon@wayne.example' => 'lucius.fox@wayne.example', // Barbara's standin is Lucius (both in Technology hierarchy)
            'harvey.dent@wayne.example' => 'alfred.pennyworth@wayne.example', // Harvey's standin is Alfred (both report to executive level)
            'selina.kyle@wayne.example' => 'barbara.gordon@wayne.example', // Selina's standin is Barbara (both security team)
            'tim.drake@wayne.example' => 'barbara.gordon@wayne.example', // Tim's standin is Barbara (his manager in security)
            'dick.grayson@wayne.example' => 'alfred.pennyworth@wayne.example', // Dick's standin is Alfred (both in corporate hierarchy)
            'jason.todd@wayne.example' => 'bruce.wayne@wayne.example', // Jason's standin is Bruce (both in corporate risk management)
            // Oscorp standins
            'emma.martinez@oscorp.example' => 'otto.octavius@oscorp.example', // Emma's standin is Otto (both technical leaders)
            'otto.octavius@oscorp.example' => 'curt.connors@oscorp.example', // Otto's standin is Curt (both research scientists)
            'curt.connors@oscorp.example' => 'emma.martinez@oscorp.example', // Curt's standin is Emma (both technical experts)
            // LexCorp standins
            'mercy.graves@lexcorp.example' => 'lex.luthor@lexcorp.example', // Mercy's standin is Lex (assistant to CEO)
            // Themyscira Corp standins
            'steve.trevor@themyscira.example' => 'diana.prince@themyscira.example', // Steve's standin is Diana (operations to director)
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
