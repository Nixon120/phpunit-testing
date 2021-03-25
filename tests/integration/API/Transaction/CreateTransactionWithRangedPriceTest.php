<?php


namespace IntegrationTests\API\Transaction;

use IntegrationTests\API\AbstractAPITestCase;

class CreateTransactionWithRangedPriceTest extends AbstractAPITestCase
{
    //trigger transaction with product within normal variables 
    public function testCreateTransactionWithRangedPrice()
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
                            'sku' => 'VVISA01',
                            'quantity' => 1,
                            'amount' => 5 
                            ],
                        ],
                    ]
                ),
            ]
        );
        // Response MUST be status code 201
        $this->assertSame(201, $response->getStatusCode());
    }

    //trigger transaction with product with MIN value less than set MIN value 
    public function testCreateTransactionWithRangedPriceBadMin()
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
                            'sku' => 'VVISA01',
                            'quantity' => 1,
                            'amount' => 2 
                            ],
                        ],
                    ]
                ),

            ]
        );
        // Response MUST be status code 400 
        $this->assertSame(400, $response->getStatusCode());
    }

    //trigger transaction with product with MAX value more than set MAX value 
    public function testCreateTransactionWithRangedPriceBadMax()
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
                            'sku' => 'VVISA01',
                            'quantity' => 1,
                            'amount' => 550 
                            ],
                        ],
                    ]
                ),

            ]
        );
        // Response MUST be status code 400 
        $this->assertSame(400, $response->getStatusCode());
    }

    //trigger transaction with product with no AMOUNT  
    public function testCreateTransactionWithRangedPriceBadAmount()
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
                            'sku' => 'VVISA01',
                            'quantity' => 1
                            ],
                        ],
                    ]
                ),

            ]
        );
        // Response MUST be status code 400 
        $this->assertSame(400, $response->getStatusCode());
    }
}
