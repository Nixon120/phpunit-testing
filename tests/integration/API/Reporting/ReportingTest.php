<?php

namespace IntegrationTests\API\Reporting;

use IntegrationTests\API\AbstractAPITestCase;

class ReportingTest extends AbstractAPITestCase
{

    public function testGetOrganizationsWithNumericLimitParam() //covers appropriate query
    {
        $response = $this->getApiClient()->request(
            'GET',
            'api/organization?1=1&orderBy[field]=name&orderBy[direction]=asc&limit=3',
            [
                'headers' => $this->getHeaders(),
            ]
        );

        $decodedResponse = json_decode((string)$response->getBody());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(3, count($decodedResponse));
    }

    public function testGetOrganizationsWithNonNumericLimitParam() //covers inappropriate query
    {
        $response = $this->getApiClient()->request(
            'GET',
            'api/organization?1=1&orderBy[field]=name&orderBy[direction]=asc&limit=abc',
            [
                'headers' => $this->getHeaders(),
            ]
        );
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testGetProgramsWithNumericLimitParam() //covers appropriate query
    {
        $response = $this->getApiClient()->request(
            'GET',
            'api/program?organization=&limit=2&orderBy[field]=name&orderBy[direction]=asc',
            [
                'headers' => $this->getHeaders(),
            ]
        );
        $decodedResponse = json_decode((string)$response->getBody());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(2, count($decodedResponse));
    }

    public function testGetProgramsWithNonNumericLimitParam() //covers inappropriate query
    {
        $response = $this->getApiClient()->request(
            'GET',
            'api/program?organization=&limit=abc&orderBy[field]=name&orderBy[direction]=asc',
            [
                'headers' => $this->getHeaders(),
            ]
        );
        $this->assertSame(500, $response->getStatusCode());
    }

}
