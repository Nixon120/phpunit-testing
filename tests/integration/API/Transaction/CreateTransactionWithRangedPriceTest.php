<?php

namespace IntegrationTests\API\Transaction;

use IntegrationTests\API\AbstractAPITestCase;

class CreateTransactionWithRangedPriceTest extends AbstractAPITestCase
{
    //trigger transaction with product not in catalog program config
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
                        'products' => [
                            'sku' => 'VVISA01',
                            'quantity' => 1,
                            'amount' => 1 
                        ],
                    ],
                )
            ]
        );
        // Response MUST be status code 201
        $this->assertSame(201, $response->getStatusCode());
    }
}
