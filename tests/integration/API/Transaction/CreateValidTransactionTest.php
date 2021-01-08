<?php

namespace IntegrationTests\API\Transaction;

use IntegrationTests\API\AbstractAPITestCase;

class CreateValidTransactionTest extends AbstractAPITestCase
{
    private function addPointsToParticipant()
    {
        $this->getApiClient()->request(
            'POST',
            'api/user/TESTPARTICIPANT1/adjustment',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'type' => 'credit',
                    'amount' => '10000'
                ])
            ]
        );
    }

    public function testCreateValidAutoPointIssuanceTransaction()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/user/TESTPARTICIPANT1/transaction',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'issue_points' => true,
                    'shipping' => [
                        'firstname' => 'Test Firstname',
                        'lastname' => 'Test Lastname',
                        'address1' => '123 Anywhere Street',
                        'city' => 'Denver',
                        'state' => 'CO',
                        'zip' => '80202'
                    ],
                    'products' => [
                        [
                            'sku' => 'HRA01',
                            'quantity' => 1,
                            'amount' => 10
                        ],
                    ],
                ]),
            ]
        );

        // Response MUST be status code 201 .. Not enough points
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateInValidAutoPointIssuanceTransaction()
    {
        $this->getApiClient()->request(
            'POST',
            'api/user',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'program' => 'sharecare',
                    'firstname' => 'john',
                    'lastname' => 'smith',
                    'unique_id' => '123ABCTEST',
                    'email_address' => '123abctest@alldigitalrewards.com'
                ])
            ]
        );
        $response = $this->getApiClient()->request(
            'POST',
            'api/user/123ABCTEST/transaction',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'shipping' => [
                        'firstname' => 'Test Firstname',
                        'lastname' => 'Test Lastname',
                        'address1' => '123 Anywhere Street',
                        'city' => 'Denver',
                        'state' => 'CO',
                        'zip' => '80202'
                    ],
                    'products' => [
                        [
                            'sku' => 'HRA01',
                            'quantity' => 1,
                            'amount' => 10
                        ],
                    ],
                ]),
            ]
        );

        // Response MUST be status code 400 .. Not enough points
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateValidTransaction()
    {
        $this->addPointsToParticipant();
        $response = $this->getApiClient()->request(
            'POST',
            'api/user/TESTPARTICIPANT1/transaction',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'shipping' => [
                        'firstname' => 'Test Firstname',
                        'lastname' => 'Test Lastname',
                        'address1' => '123 Anywhere Street',
                        'city' => 'Denver',
                        'state' => 'CO',
                        'zip' => '80202'
                    ],
                    'products' => [
                        [
                            'sku' => 'HRA01',
                            'quantity' => 1,
                            'amount' => 10
                        ],
                    ],
                ]),
            ]
        );

        // Response MUST be status code 201
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testFetchTransactionByOneUniqueIdReturnsOneInArray()
    {
        $this->addPointsToParticipant();
        $this->createTransactionsWithUniqueId();

        $uniqueIds = ['someuniqueidhere1'];

        foreach ($uniqueIds as $uniqueId) {
            //lets call each unique id
            $uri = 'api/user/TESTPARTICIPANT1/transaction?unique_id[]=' . $uniqueId;
            $response = $this->getApiClient()->request(
                'GET',
                $uri,
                [
                    'headers' => $this->getHeaders()
                ]
            );
            $responseObj = json_decode($response->getBody(), true);
            $this->assertSame($uniqueId, $responseObj[0]['unique_id']);
        }
    }

    public function testFetchTransactionByNonExistingUniqueIdsReturns404()
    {
        $uniqueIds = ['idontexist1', 'idontexist2', 'idontexist3'];

        foreach ($uniqueIds as $uniqueId) {
            //lets call each unique id
            $uri = 'api/user/TESTPARTICIPANT1/transaction?unique_id[]=' . $uniqueId;
            $response = $this->getApiClient()->request(
                'GET',
                $uri,
                [
                    'headers' => $this->getHeaders()
                ]
            );
            $responseObj = json_decode($response->getBody());

            $this->assertSame(404, $response->getStatusCode());
            $this->assertSame('Unique Ids Not Found', $responseObj[0]);
        }
    }

    /**
     * Transaction Object must include an array of products.
     */
    public function testResponseHasArrayOfProducts()
    {
        $this->addPointsToParticipant();
        $response = $this->getApiClient()->request(
            'POST',
            'api/user/TESTPARTICIPANT1/transaction',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'shipping' => [
                        'firstname' => 'Test Firstname',
                        'lastname' => 'Test Lastname',
                        'address1' => '123 Anywhere Street',
                        'city' => 'Denver',
                        'state' => 'CO',
                        'zip' => '80202'
                    ],
                    'products' => [
                        [
                            'sku' => 'HRA01',
                            'quantity' => 1,
                            'amount' => 10
                        ],
                    ],
                ]),
            ]
        );

        $responseObj = json_decode($response->getBody());

        // Response MUST be status code 201
        $this->assertTrue(is_array($responseObj->products));
    }

    private function createTransactionsWithUniqueId()
    {
        $uniqueIds = ['someuniqueidhere1', 'someuniqueidhere2', 'someuniqueidhere3'];

        foreach ($uniqueIds as $uniqueId) {
            $response = $this->getApiClient()->request(
                'POST',
                'api/user/TESTPARTICIPANT1/transaction',
                [
                    'headers' => $this->getHeaders(),
                    'body' => json_encode([
                        'unique_id' => "$uniqueId",
                        'shipping' => [
                            'firstname' => 'Test Firstname',
                            'lastname' => 'Test Lastname',
                            'address1' => '123 Anywhere Street',
                            'city' => 'Denver',
                            'state' => 'CO',
                            'zip' => '80202'
                        ],
                        'products' => [
                            [
                                'sku' => 'HRA01',
                                'quantity' => 1,
                                'amount' => 10
                            ],
                        ],
                    ]),
                ]
            );
        }
    }
}
