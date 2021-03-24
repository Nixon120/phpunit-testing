<?php

namespace IntegrationTests\API\Transaction;

use IntegrationTests\API\AbstractAPITestCase;

class CreateTransactionWithFeeTest extends AbstractAPITestCase
{
    //trigger transaction with product not in catalog program config
    public function testCreateTransactionWithProgramFee()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/program/alldigitalrewards/participant/TESTPARTICIPANT1/transaction',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(
                    [
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
                                'sku' => 'CHADTEST',
                                'quantity' => 1,
                            ],
                        ],
                    ]
                ),
            ]
        );

        // Response MUST be status code 201
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateTransactionWithVendorFee()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/program/alldigitalrewards/participant/TESTPARTICIPANT1/transaction',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(
                    [
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
                                'sku' => 'PS0000889498-24',
                                'quantity' => 1,
                            ],
                        ],
                    ]
                ),
            ]
        );

        // Response MUST be status code 201
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateTransactionWithVendorAndProgramFee()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/program/alldigitalrewards/participant/TESTPARTICIPANT1/transaction',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(
                    [
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
                                'sku' => 'PS0000889497-24',
                                'quantity' => 1,
                            ],
                        ],
                    ]
                ),
            ]
        );

        // Response MUST be status code 201
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateTransactionWithVendorAndProgramFeeAndWithoutFee()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/program/alldigitalrewards/participant/TESTPARTICIPANT1/transaction',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(
                    [
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
                                'sku' => 'PS0000889497-24',
                                'quantity' => 1,
                            ],
                            [
                                'sku' => 'PS0000889498-24',
                                'quantity' => 1,
                            ],
                            [
                                'sku' => 'CHADTEST',
                                'quantity' => 1,
                            ],
                            [
                                'sku' => 'ICVUSD-D-V-00-20',
                                'quantity' => 1
                            ]
                        ],
                    ]
                ),
            ]
        );

        // Response MUST be status code 201
        $this->assertSame(201, $response->getStatusCode());
    }
}
