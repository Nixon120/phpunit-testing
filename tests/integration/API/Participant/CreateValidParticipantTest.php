<?php

namespace IntegrationTests\API\Organization;

use IntegrationTests\API\AbstractAPITestCase;

class CreateValidParticipantTest extends AbstractAPITestCase
{
    public function testCreateValidParticipant()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/user',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'email_address' => 'test+integration@alldigitalrewards.com',
                    'unique_id' => 'INTEGRATIONTEST',
                    'firstname' => 'INTEGRATION',
                    'birthdate' => date('Y-m-d', strtotime('-20 years')),
                    'lastname' => 'TEST',
                    'phone' => '1231231234',
                    'password' => 'password',
                    'address' => [
                        'firstname' => 'INTEGRATION',
                        'lastname' => 'TEST',
                        'address1' => '123 McTesty Testerson',
                        'address2' => '',
                        'city' => 'Beverly Hills',
                        'state' => 'CA',
                        'zip' => '90210'
                    ],
                    'program' => 'sharecare',
                    'organization' => 'sharecare',
                    'meta' => [
                        'integration' => 'test'
                    ]
                ]),
            ]
        );

        // Response MUST be status code 201
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateInvalidParticipant()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/user',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'email_address' => 'test+integrationfail@alldigitalrewards.com',
                    'unique_id' => 'INTEGRATIONTESTFAIL',
                    'firstname' => 'INTEGRATION',
                    'birthdate' => date('m/d/Y', strtotime('-20 years')),
                    'program' => 'sharecare',
                    'organization' => 'sharecare'
                ]),
            ]
        );

        // Response MUST be status code 201
        $this->assertSame(400, $response->getStatusCode());
    }
}
