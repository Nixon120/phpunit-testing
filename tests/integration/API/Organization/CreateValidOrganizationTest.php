<?php

namespace IntegrationTests\API\Organization;

use IntegrationTests\API\AbstractAPITestCase;

class CreateValidOrganizationTest extends AbstractAPITestCase
{
    public function testCreateValidOrganization()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/organization',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'unique_id' => 'TESTORG1',
                    'name' => 'Test Organization 1',
                    'parent' => 'sharecare'
                ]),
            ]
        );

        // Response MUST be status code 201
        $this->assertSame(201, $response->getStatusCode());
    }
}
