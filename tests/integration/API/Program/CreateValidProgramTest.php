<?php

namespace IntegrationTests\API\Program;

use IntegrationTests\API\AbstractAPITestCase;

class CreateValidProgramTest extends AbstractAPITestCase
{

    public function testCreateValidProgram()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/program',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'unique_id' => 'TESTPROG1',
                    'name' => 'Test Program 1',
                    'point' => 100,
                    'organization' => 'sharecare',
                    'url' => 'test.mydigitalrewards.com'
                ]),
            ]
        );

        // Response MUST be status code 201
        $this->assertSame(
            201,
            $response->getStatusCode()
        );
    }
}
