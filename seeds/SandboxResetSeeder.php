<?php

use Phinx\Seed\AbstractSeed;

class SandboxResetSeeder extends AbstractSeed
{
    private $products = [];

    private $participantEmailContainerSeed = [];

    private $participantFirstnameContainerSeed = [];

    private $participantLastnameContainerSeed = [];

    private $participantAddressReferenceContainerSeed = [];

    private $participantAddressContainerSeed = [];

    private $transactionProductContainerSeed = [];

    private $drawCountSeed = [];

    public function run()
    {
        $this->prepareInformationForSeeders();
        $orgId = $this->seedOrganization();
        $this->seedWebhook();
        $this->seedDomain();
        $programId = $this->seedProgram();
        $this->seedLayoutRows();
        $this->seedLayoutRowCards();
        $this->seedSweepstake();
        $this->seedUser();
        $this->seedParticipant();
        $this->seedParticipantAddress();
        $this->seedCreditAdjustments();
        $this->seedTransaction();
        $this->setParticipantCalculatedCredit();
    }

    /**
     * @return \Faker\Generator
     */
    private function getFaker(): Faker\Generator
    {
        return Faker\Factory::create();
    }

    /**
     * Gets a random date between two dates
     * @param $start
     * @param $end
     * @return false|string
     */
    private function getRandomDate($start, $end)
    {
        $min = strtotime($start);
        $max = strtotime($end);
        $val = mt_rand($min, $max);
        return date('Y-m-d H:i:s', $val);
    }

    /**
     * Fetches and stores products that are used to seed transactions
     * Calls for mock participant information generation
     * Calls for mock address information generation
     */
    private function prepareInformationForSeeders()
    {
        $this->prepareProductsToBeConsumed();
        $this->prepareParticipantInformation();
        $this->prepareAddressSeedContainer();
        $this->prepareDrawCount();
    }

    /**
     * Fetches products that are consumed in the transaction seeds
     * @return array
     */
    private function prepareProductsToBeConsumed()
    {
        if (empty($this->products)) {
            $this->products = require __DIR__ . '/fixtures/products.php';
        }
        return $this->products;
    }

    /**
     * Mocks participant information to be consumed in the participant seed
     */
    private function prepareParticipantInformation()
    {
        for ($i = 0; $i <= 100; $i++) {
            $email = $this->getFaker()->userName . '@alldigitalrewards.com';
            $firstname = $this->getFaker()->firstName;
            $lastname = $this->getFaker()->lastName;
            array_push($this->participantEmailContainerSeed, $email);
            array_push($this->participantFirstnameContainerSeed, $firstname);
            array_push($this->participantLastnameContainerSeed, $lastname);
        }
    }

    /**
     * Mocks address information to be consumed in the participant/transaction seeds
     * @return array
     */
    private function prepareAddressSeedContainer()
    {
        for ($i = 0, $j = 1; $i <= 100; $i++, $j++) {
            $addressContainer = [
                'firstname' => $this->participantFirstnameContainerSeed[$i],
                'lastname' => $this->participantLastnameContainerSeed[$i],
                'address1' => $this->getFaker()->streetAddress,
                'address2' => $this->getFaker()->secondaryAddress,
                'city' => $this->getFaker()->city,
                'state' => $this->getFaker()->stateAbbr,
                'zip' => $this->getFaker()->postcode,
                'country' => 840
            ];
            $reference = sha1(json_encode($addressContainer));
            $addressContainer['reference_id'] = $reference;
            $addressContainer['participant_id'] = $j;

            array_push($this->participantAddressReferenceContainerSeed, $reference);
            array_push($this->participantAddressContainerSeed, $addressContainer);
        }
    }

    private function prepareDrawCount()
    {
        for ($i = 0; $i < 10; $i++) {
            $this->drawCountSeed [] = mt_rand(10, 20);
        }
    }

    /**
     * Creates a unique id
     * @return string
     */
    private function getParticipantUuid()
    {
        $uuid = [];
        for ($i = 1; $i < 15; $i++) {
            // get a random digit, but always a new one, to avoid duplicates
            $character = [$this->getFaker()->randomDigit, $this->getFaker()->randomLetter];
            $uuid[] = $character[mt_rand(0, 1)];
        }
        $uuid = implode('', $uuid);

        return $uuid;
    }

    /**
     * Mock transaction creation
     * @param $participantId
     * @return array
     */
    private function getParticipantTransaction($participantId)
    {
        return [
            'participant_id' => $participantId,
            'email_address' => $this->participantEmailContainerSeed[($participantId - 1)],
            'type' => 1,
            'shipping_reference' => $this->participantAddressReferenceContainerSeed[($participantId - 1)],
            'created_at' => $this->getRandomDate('2017-01-01', date('Y-m-d'))
        ];
    }

    /**
     * Mock transaction items
     * @param $transactionId
     * @return array
     */
    private function getParticipantTransactionItems($transactionId): array
    {
        $items = [];
        $numberOfItemsInTransaction = mt_rand(1, 3);
        for ($i = 0; $i <= $numberOfItemsInTransaction; $i++) {
            $selectedProduct = $this->products[rand(0, 3)];
            $product = [
                'unique_id' => $selectedProduct->getSku(),
                'wholesale' => $selectedProduct->getPriceWholesale(),
                'retail' => $selectedProduct->getPriceRetail(),
                'shipping' => $selectedProduct->getPriceShipping(),
                'handling' => $selectedProduct->getPriceHandling(),
                'vendor_code' => $selectedProduct->getSku(),
                'kg' => 0,
                'name' => $selectedProduct->getName(),
                'description' => $selectedProduct->getDescription(),
                'terms' => $selectedProduct->getTerms(),
                //@TODO change type to digital on marketplace entity
                'type' => $selectedProduct->isDigital() ? 1 : 0
            ];
            $reference = sha1(json_encode($product));
            $product['reference_id'] = $reference;
            $this->transactionProductContainerSeed[$reference] = $product;
            $items[] = [
                'transaction_id' => $transactionId,
                'reference_id' => $reference,
                'quantity' => rand(1, 5),
                'guid' => \Ramsey\Uuid\Uuid::uuid1()
            ];
        }
        return $items;
    }

    /** Seeders */

    private function seedOrganization()
    {
        // Purge all existing Organizations.
        $this->execute("DELETE FROM Organization");
        // Reset auto increment value.
        $this->execute('ALTER TABLE Organization AUTO_INCREMENT = 1');

        $data = [
            [
                'name' => 'All Digital Rewards',
                'lft' => 1,
                'rgt' => 4,
                'lvl' => 1,
                'active' => 1,
                'unique_id' => 'alldigitalrewards',
            ],
            [
                'name' => 'ShareCare',
                'lft' => 2,
                'rgt' => 3,
                'lvl' => 2,
                'active' => 1,
                'unique_id' => 'sharecare',
                'parent_id' => 1
            ]
        ];

        $organizations = $this->table('Organization');

        $organizations
            ->insert($data)
            ->save();

        return $this->fetchRow("select LAST_INSERT_ID() as org_id")['org_id'];
    }

    private function seedWebhook()
    {
        $data = [
            [
                'organization_id' => 1,
                'title' => 'RA Transaction',
                'url' => 'https://ra.staging.alldigitalrewards.com/api/transaction',
                'username' => 'claim',
                'password' => 'claim',
                'event' => 'Transaction.create',
                'active' => 1,
                'updated_at' => '2017-01-01'
            ]
        ];

        $table = $this->table('webhook');
        $table->insert($data)->save();
    }

    private function seedLayoutRows()
    {
        $data = [
            [
                'id' => 1,
                'priority' => 0,
                'program_id' => 'alldigitalrewards',
                'label' => ''
            ],
            [
                'id' => 2,
                'priority' => 1,
                'program_id' => 'alldigitalrewards',
                'label' => ''
            ],
            [
                'id' => 3,
                'priority' => 2,
                'program_id' => 'alldigitalrewards',
                'label' => 'Marketplace'
            ],
            [
                'id' => 4,
                'priority' => 3,
                'program_id' => 'alldigitalrewards',
                'label' => ''
            ],
            [
                'id' => 5,
                'priority' => 4,
                'program_id' => 'alldigitalrewards',
                'label' => 'Marketplace'
            ],
            [
                'id' => 6,
                'priority' => 5,
                'program_id' => 'alldigitalrewards',
                'label' => 'Marketplace'
            ],
            [
                'id' => 7,
                'priority' => 6,
                'program_id' => 'alldigitalrewards',
                'label' => ''
            ],
        ];
        $table = $this->table('LayoutRow');
        $table->truncate();
        $table->insert($data)->save();
    }

    private function seedLayoutRowCards()
    {
        $data = [
            [
                'row_id' => 1,
                'priority' => 0,
                'size' => 6,
                'type' => 'image',
                'product' => null,
                'image' => '4f538aab63fa10afc5be4c8b05adb4ec0f6f9074.png',
                'link' => null,
                'product_row' => null
            ],
            [
                'row_id' => 1,
                'priority' => 1,
                'size' => 6,
                'type' => 'link',
                'product' => null,
                'image' => '02e8c72942fdb3ca7f8ca54511ebf8fc09f7382c.jpeg',
                'link' => 'https://sharecare-demo.mydigitalrewards.com/reward/view?sku=VVISA01',
                'product_row' => null
            ],
            [
                'row_id' => 2,
                'priority' => 0,
                'size' => 4,
                'type' => 'link',
                'product' => null,
                'image' => '2bc45f160c6d4136570b583dd583d8088381dbd8.jpeg',
                'link' => 'https://sharecare-demo.mydigitalrewards.com/reward/view?sku=PS0000889497-24',
                'product_row' => null
            ],
            [
                'row_id' => 2,
                'priority' => 1,
                'size' => 4,
                'type' => 'link',
                'product' => null,
                'image' => '91db8de61963ab7cc78e19e79b999ca01ed6d355.jpeg',
                'link' => 'https://sharecare-demo.mydigitalrewards.com/reward/view?sku=PS0000889498-24',
                'product_row' => null
            ],
            [
                'row_id' => 2,
                'priority' => 2,
                'size' => 4,
                'type' => 'link',
                'product' => null,
                'image' => 'f368e4118faa612e58bc18c4326f621c1c7f2de4.jpeg',
                'link' => 'https://sharecare-demo.mydigitalrewards.com/reward/view?sku=HRA01',
                'product_row' => null
            ],
            [
                'row_id' => 3,
                'priority' => 0,
                'size' => 9,
                'type' => 'product_row',
                'product' => null,
                'image' => null,
                'link' => null,
                'product_row' => '["PS0000168274-24","PS0000883442-24","PS0000889498-24","PS0000913289-24"]'
            ],
            [
                'row_id' => 3,
                'priority' => 1,
                'size' => 3,
                'type' => 'link',
                'product' => null,
                'image' => '324cdd53610a7f0214d82b0cbc20eb228ae67f67.jpeg',
                'link' => 'https://sharecare-demo.mydigitalrewards.com/featured',
                'product_row' => null
            ],
            [
                'row_id' => 4,
                'priority' => 0,
                'size' => 12,
                'type' => 'image',
                'product' => null,
                'image' => 'b7628bf0cb95339c01989b2f5a5c29f7f2e14d51.png',
                'link' => null,
                'product_row' => null
            ],
            [
                'row_id' => 5,
                'priority' => 0,
                'size' => 3,
                'type' => 'link',
                'product' => null,
                'image' => '0331d477a1e1ba3bc0355077d577e3c6e08f97dc.jpeg',
                'link' => 'https://sharecare-demo.mydigitalrewards.com/reward/view?sku=PS0000913293-24',
                'product_row' => null
            ],
            [
                'row_id' => 5,
                'priority' => 1,
                'size' => 9,
                'type' => 'product_row',
                'product' => null,
                'image' => null,
                'link' => null,
                'product_row' => '["PS0000889498-24","PS0000889497-24","PS0000168274-24","PS0000883442-24"]'
            ],
            [
                'row_id' => 6,
                'priority' => 0,
                'size' => 9,
                'type' => 'product_row',
                'product' => null,
                'image' => null,
                'link' => null,
                'product_row' => '["PS0000889491-24","PS0000168274-24","PS0000889498-24","PS0000889497-24"]'
            ],
            [
                'row_id' => 6,
                'priority' => 1,
                'size' => 3,
                'type' => 'link',
                'product' => null,
                'image' => 'dfdbb033770d22bc9cba86a2f1d3ce473922b6fb.jpeg',
                'link' => 'https://sharecare-demo.mydigitalrewards.com/reward/view?sku=PS0000913293-24',
                'product_row' => null
            ],
            [
                'row_id' => 7,
                'priority' => 0,
                'size' => 4,
                'type' => 'link',
                'product' => null,
                'image' => '2729143327f0a51c5dc481edc6df06b1a6db1c67.jpeg',
                'link' => 'https://sharecare-demo.mydigitalrewards.com/reward/view?sku=PS0000883442-24',
                'product_row' => null
            ],
            [
                'row_id' => 7,
                'priority' => 1,
                'size' => 4,
                'type' => 'link',
                'product' => null,
                'image' => 'ffc134cc45b36fe04b3545e64a831da4090a86e9.jpeg',
                'link' => 'https://sharecare-demo.mydigitalrewards.com/reward/view?sku=PS0000168274-24',
                'product_row' => null
            ],
            [
                'row_id' => 7,
                'priority' => 2,
                'size' => 4,
                'type' => 'link',
                'product' => null,
                'image' => '1f1ad145ad1691599175ff62e3a8871751d80d66.jpeg',
                'link' => 'https://sharecare-demo.mydigitalrewards.com/reward/view?sku=PS0000889491-24',
                'product_row' => null
            ]
        ];
        $table = $this->table('LayoutRowCard');
        $table->truncate();
        $table->insert($data)->save();
        if (getenv('ENVIRONMENT') === 'development') {
            $this->importFixturedLayoutImages();
        }
    }

    private function seedFaqs()
    {
        $data = [
            [
                'id' => 1,
                'program_id' => 'sharecare',
                'question' => 'How do I check how many points I have and how I earned them?',
                'answer' => 'You can check your account transaction details (both earned and redeemed points) at any time by clicking on your name. It\'s located at the top of every page to the right of the search button.'
            ],
            [
                'id' => 2,
                'program_id' => 'sharecare',
                'question' => 'Do I have to use all my points in a single transaction?',
                'answer' => 'No, you can spend a portion of your points and retain any points remaining in your account for use at a later time. <br />*Note: Points expire at the end of your program year.'
            ],
            [
                'id' => 3,
                'program_id' => 'sharecare',
                'question' => 'How do I confirm my reward redemption was successful?',
                'answer' => 'Any transaction resulting in points being deducted from your account will be accompanied by a confirmation page and a confirmation email and will be recorded in your "My Rewards Account" page. You can reach this page by clicking on your name, located at the top of every page to the right of the search button.'
            ],
            [
                'id' => 4,
                'program_id' => 'sharecare',
                'question' => 'Are my reward points transferable?',
                'answer' => 'Points have no cash value, and may not be assigned, transferred and/or pledged to any third party. You do not have property rights or other legal interests in any reward points granted pursuant to the Program.'
            ],
            [
                'id' => 5,
                'program_id' => 'sharecare',
                'question' => 'If I have a question regarding a transaction, who do I contact?',
                'answer' => 'The details of each transaction (including tracking numbers when available) are available on your "My Rewards Account" page. To access your "My Rewards Account" page simply click on the "My Rewards Account" link at the top right of every page in the system. If you have any questions, please feel free to contact the Marketplace Customer Support through the "Contact Us" link in the navigation bar on the right or bottom of every page.'
            ],
            [
                'id' => 6,
                'program_id' => 'sharecare',
                'question' => 'Who pays for the shipping and handling on products ordered?',
                'answer' => 'The cost of shipping and handling on all products are included in the price (points) displayed in the Marketplace.'
            ],
            [
                'id' => 7,
                'program_id' => 'sharecare',
                'question' => 'Do points expire?',
                'answer' => 'Yes, points expire at the end of your program plan year. Points do not carry over from one plan year to the next.'
            ],
        ];
        $table = $this->table('Faqs');
        $table->truncate();
        $table->insert($data)->save();
    }

    private function importFixturedLayoutImages()
    {
        $dir = opendir(ROOT . '/seeds/fixtures/layout-images');
        $src = ROOT . '/seeds/fixtures/layout-images';
        $dst = ROOT . '/public/resources/app/layout';
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }

        closedir($dir);
    }

    private function seedDomain()
    {
        $domainContainerSeed = [
            [
                'organization_id' => 1,
                'url' => 'mydigitalrewards.com',
                'active' => 1
            ],
            [
                'organization_id' => 2,
                'url' => 'mydigitalrewards.com',
                'active' => 1
            ]
        ];

        $domains = $this->table('Domain');
        $domains->truncate();

        $domains->insert($domainContainerSeed)
            ->save();

        return $this->fetchRow("select LAST_INSERT_ID() as domain_id")['domain_id'];
    }

    private function seedProgram()
    {
        $data = [
            [
                'organization_id' => 1,
                'name' => 'Demo',
                'point' => 1000,
                'url' => 'demo',
                'logo' => null,
                'active' => 1,
                'unique_id' => 'alldigitalrewards',
                'invoice_to' => 'Top Level Client',
                'domain_id' => 1
            ],
            [
                'organization_id' => 2,
                'name' => 'Sharecare Demo',
                'point' => 1000,
                'url' => 'sharecare-demo',
                'logo' => 'data:image/png;base64,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',
                'active' => 1,
                'unique_id' => 'sharecare',
                'invoice_to' => 'Top Level Client',
                'domain_id' => 2
            ]
        ];

        $programs = $this->table('Program');

        # Purge all Organizations
        $programs->truncate();

        $programs
            ->insert($data)
            ->save();

        return $this->fetchRow("select LAST_INSERT_ID() as program_id")['program_id'];
    }

    private function seedSweepstake()
    {
        $data = [
            [
                'program_id' => 'alldigitalrewards',
                'active' => 1,
                'start_date' => '2018-01-01',
                'end_date' => '2024-12-31',
                'max_participant_entry' => 1000,
                'created_at' => date('Y-m-d')
            ],
            [
                'program_id' => 'sharecare',
                'active' => 1,
                'start_date' => '2018-01-01',
                'end_date' => '2024-12-31',
                'max_participant_entry' => 1000,
                'created_at' => date('Y-m-d')
            ]
        ];

        $sweepstake = $this->table('Sweepstake');
        $entries = $this->table('SweepstakeEntry');

        # Purge all existing sweepstakes
        $sweepstake->truncate();
        $entries->truncate();

        $sweepstake->insert($data)->save();

        $this->seedSweepStakeDrawing();
        $this->seedSweepStakeEntry();
    }

    private function seedSweepStakeDrawing()
    {

        $data = [
            [
                'sweepstake_id' => 2,
                'date' => $this->getFaker()->dateTimeBetween('-4 months', '-3 months')->format('Y-m-d'),
                'draw_count' => $this->drawCountSeed[5],
                'processed' => 1,
                'created_at' => date('Y-m-d'),
                'updated_at' => date('Y-m-d')
            ],
            [
                'sweepstake_id' => 2,
                'date' => $this->getFaker()->dateTimeBetween('-3 months', '-2 months')->format('Y-m-d'),
                'draw_count' => $this->drawCountSeed[6],
                'processed' => 1,
                'created_at' => date('Y-m-d'),
                'updated_at' => date('Y-m-d')
            ],
            [
                'sweepstake_id' => 2,
                'date' => date('Y-m-d'),
                'draw_count' => $this->drawCountSeed[9],
                'processed' => 1,
                'created_at' => date('Y-m-d'),
                'updated_at' => date('Y-m-d')
            ],
            [
                'sweepstake_id' => 2,
                'date' => $this->getFaker()->dateTimeBetween('30 days', '2 months')->format('Y-m-d'),
                'draw_count' => $this->drawCountSeed[7],
                'created_at' => date('Y-m-d'),
                'updated_at' => date('Y-m-d')
            ],
            [
                'sweepstake_id' => 2,
                'date' => $this->getFaker()->dateTimeBetween('2 months', '3 months')->format('Y-m-d'),
                'draw_count' => $this->drawCountSeed[8],
                'created_at' => date('Y-m-d'),
                'updated_at' => date('Y-m-d')
            ],
        ];

        $drawing = $this->table('SweepstakeDraw');
        $drawing->truncate();
        $drawing->insert($data)->save();
    }

    private function seedSweepStakeEntry()
    {
        $data = [];
        $sweepstakeIds = [1, 2];
        $startRange = (new DateTime('-3 months'))->getTimestamp();
        $endRange = (new DateTime)->getTimestamp();

        // Seed Entry with no winners
        foreach ($sweepstakeIds as $sweepstakeId) {
            for ($i = 0; $i < 50; $i++) {
                $randomTimestamp = mt_rand($startRange, $endRange);
                $date = date("Y-m-d H:i:s", $randomTimestamp);
                $data[] = [
                    'sweepstake_id' => $sweepstakeId,
                    'sweepstake_draw_id' => null,
                    'participant_id' => $participantId = mt_rand(1, 100),
                    'point' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ];
            }
        }

        // Seed Entry with winners
        foreach ($sweepstakeIds as $sweepstakeId) {
            for ($i = 0; $i < $this->drawCountSeed[3]; $i++) {
                $randomTimestamp = mt_rand($startRange, $endRange);
                $date = date("Y-m-d H:i:s", $randomTimestamp);
                $data[] = [
                    'sweepstake_id' => $sweepstakeId,
                    'sweepstake_draw_id' => mt_rand(1,6),
                    'participant_id' => $participantId = mt_rand(1, 100),
                    'point' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ];
            }
        }

        $entries = $this->table('SweepstakeEntry');
        $entries->truncate();
        $entries->insert($data)->save();
    }

    private function seedUser()
    {
        $data = [
            [
                'email_address' => 'username',
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'firstname' => 'Test',
                'lastname' => 'API',
                'organization_id' => 2,
                'role' => 'admin',
                'active' => 1,
            ],
            [
                'email_address' => 'superadmin',
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'firstname' => 'Test',
                'lastname' => 'API',
                'role' => 'superadmin',
                'active' => 1,
            ],
            [
                'email_address' => 'test@alldigitalrewards.com',
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'firstname' => 'Test',
                'lastname' => 'Admin',
                'role' => 'superadmin',
                'active' => 1,
            ],
            [
                'email_address' => 'admin@alldigitalrewards.com',
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'firstname' => 'Super',
                'lastname' => 'Admin',
                'role' => 'superadmin',
                'active' => 1,
            ],
            [
                'organization_id' => 2,
                'email_address' => 'client@alldigitalrewards.com',
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'firstname' => 'Client',
                'lastname' => 'Admin',
                'role' => 'admin',
                'active' => 1,
            ],
            [
                'organization_id' => 2,
                'email_address' => 'configs@alldigitalrewards.com',
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'firstname' => 'Config',
                'lastname' => 'Admin',
                'role' => 'configs',
                'active' => 1,
            ],
            [
                'organization_id' => 2,
                'email_address' => 'reports@alldigitalrewards.com',
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'firstname' => 'Report',
                'lastname' => 'Admin',
                'role' => 'reports',
                'active' => 1,
            ]
        ];

        $users = $this->table('User');

        # Purge all existing users.
        $users->truncate();

        # Load users.
        $users->insert($data)->save();
    }

    private function seedParticipant()
    {
        $userContainerSeed = [];

        for ($i = 1, $j = 0; $i <= 100; $i++, $j++) {
            $birthdate = $this->getFaker()->dateTimeBetween('-50 years', 'now')->format('Y-m-d');

            $userContainerSeed[] = [
                'organization_id' => 2,
                'program_id' => 2,
                'email_address' => $this->participantEmailContainerSeed[$j],
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'unique_id' => $this->getParticipantUuid(),
                'firstname' => $this->participantFirstnameContainerSeed[$j],
                'lastname' => $this->participantLastnameContainerSeed[$j],
                'address_reference' => $this->participantAddressReferenceContainerSeed[$j],
                'active' => 1,
                'created_at' => "2017-01-01",
                'birthdate' => $birthdate
            ];
        }

        $userContainerSeed[] = [
            'organization_id' => 2,
            'program_id' => 2,
            'email_address' => 'test@alldigitalrewards.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
            'unique_id' => 'TESTPARTICIPANT1',
            'firstname' => 'Test',
            'lastname' => 'User',
            'active' => 1,
        ];

        $participants = $this->table('Participant');

        # Purge all existing participants.
        $participants->truncate();

        # Load participants.
        $participants->insert($userContainerSeed)
            ->save();
    }

    private function seedParticipantAddress()
    {
        $address = $this->table('Address');
        $address->truncate();

        $address->insert($this->participantAddressContainerSeed)
            ->save();
    }

    private function seedCreditAdjustments()
    {
        $adjustments = [];
        for ($i = 1; $i <= 100; $i++) {
            $adjustments[] = [
                'participant_id' => $i,
                'amount' => 500,
                'type' => 1,
                'active' => 1,
                'created_at' => "2017-01-01 01:00:00"
            ];
        }

        $users = $this->table('Adjustment');

        # Purge all existing adjustments.
        $users->truncate();
        $users->insert($adjustments)->save();
    }

    private function seedTransaction()
    {
        $transactionContainerSeed = [];
        $transactionItemContainerSeed = [];
        $transactionDebitAdjustmentsSeed = [];

        for ($i = 1; $i <= 100; $i++) {
            $participantId = mt_rand(1, 100);
            $transaction = $this->getParticipantTransaction($participantId);
            $items = $this->getParticipantTransactionItems($i);
            $total = 0;
            $subtotal = 0;
            $wholesale = 0;

            foreach ($items as $item) {
                $product = $this->transactionProductContainerSeed[$item['reference_id']];
                $productTotal = bcadd((bcadd($product['retail'], $product['shipping'], 2)), $product['handling'], 2);
                $wholesale = bcadd($total, $product['wholesale'], 2);
                $subtotal = bcadd($total, $product['retail'], 2);
                $total = bcadd($total, $productTotal, 2);
            }

            $transaction['wholesale'] = $wholesale;
            $transaction['subtotal'] = $subtotal;
            $transaction['total'] = $total;

            $transactionContainerSeed[] = $transaction;
            $transactionItemContainerSeed = array_merge($transactionItemContainerSeed, $items);

            $transactionDebitAdjustmentsSeed[] = [
                'participant_id' => $participantId,
                'amount' => $total,
                'type' => 2,
                'active' => 1,
                'transaction_id' => $i,
                'created_at' => $transaction['created_at']
            ];
        }

        $transactions = $this->table('Transaction');
        $transactionItems = $this->table('TransactionItem');
        $transactionProducts = $this->table('TransactionProduct');
        $debitAdjustments = $this->table('Adjustment');

        $this->execute('
            SET FOREIGN_KEY_CHECKS=0;
            TRUNCATE `' . getenv('MYSQL_DATABASE') . '`.`Transaction`;
            TRUNCATE `' . getenv('MYSQL_DATABASE') . '`.`TransactionItem`;
            TRUNCATE `' . getenv('MYSQL_DATABASE') . '`.`TransactionProduct`;
            SET FOREIGN_KEY_CHECKS=1;
        ');
        $transactions->insert($transactionContainerSeed)->save();
        $transactionItems->insert($transactionItemContainerSeed)->save();
        $transactionProducts->insert(array_values($this->transactionProductContainerSeed))->save();
        $debitAdjustments->insert($transactionDebitAdjustmentsSeed)->save();
    }

    private function setParticipantCalculatedCredit()
    {
        $query = "UPDATE Participant SET credit = (
            IFNULL((
                SELECT SUM(amount) FROM (SELECT * FROM Participant) temp
                LEFT JOIN Adjustment ON Adjustment.participant_id = temp.id
                WHERE type = 1 AND temp.id = Participant.id
                GROUP BY temp.id
            ),0) - 
            IFNULL((
                SELECT SUM(amount) FROM (SELECT * FROM Participant) temp
                LEFT JOIN Adjustment ON Adjustment.participant_id = temp.id
                WHERE type = 2 AND temp.id = Participant.id
                GROUP BY temp.id
            ),0)) * 1000 WHERE 1=1;";
        // Purge all existing Organizations.
        $this->execute($query);
    }
}
