<?php

namespace IntegrationTests\API\Organization;

use IntegrationTests\API\AbstractAPITestCase;

class OrganizationUniquenessTest extends AbstractAPITestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->makeDummyOrg();
    }

    private function makeDummyOrg()
    {
        $this->getApiClient()->request(
            'POST',
            'api/organization',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'unique_id' => 'TESTORG2',
                    'name' => 'Test Organization 2',
                    'parent' => 'sharecare',
                    'username' => 'testorg2'
                ]),
            ]
        );
    }

    public function testOrganizationUniqueIdUniquenessCheck()
    {
        $this->makeDummyOrg();
        $response = $this->getApiClient()->request(
            'POST',
            'api/organization',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'unique_id' => 'TESTORG2',
                    'name' => 'Test Organization 2',
                    'parent' => 'sharecare'
                ]),
            ]
        );

        // Response MUST be status code 201
        $this->assertSame(400, $response->getStatusCode());
    }

}
