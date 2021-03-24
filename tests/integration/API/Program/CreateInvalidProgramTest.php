<?php

namespace IntegrationTests\API\Program;

use IntegrationTests\API\AbstractAPITestCase;

class CreateInvalidProgramTest extends AbstractAPITestCase
{
    public function testCreateInvalidValidProgramNameLength()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/program',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(
                    [
                        'unique_id' => 'INVALIDNAMELENGTH',
                        'name' => '01234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789',
                        'point' => 100,
                        'organization' => 'sharecare',
                        'url' => 'test.mydigitalrewards.com'
                    ]
                ),
            ]
        );

        $this->assertSame(
            '["Name must have a length between 1 and 125"]',
            (string)$response->getBody()
        );

        $this->assertSame(
            400,
            $response->getStatusCode()
        );
    }

    public function testCreateInValidProgram()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/program',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(
                    [
                        // TESTPROG1 id has already been taken.
                        'unique_id' => 'alldigitalrewards',
                        'name' => 'AllDigitalRewards',
                        'point' => 1,
                        'organization' => 'alldigitalrewards'
                    ]
                ),
            ]
        );

        $this->assertSame(
            '["Program ID alldigitalrewards has already been assigned to another Program."]',
            (string)$response->getBody()
        );

        // Response MUST be status code 201
        $this->assertSame(
            400,
            $response->getStatusCode()
        );
    }

    public function testInValidProgramUrlMissingDomain()
    {
        $missingTldDomainResponse = $this->getApiClient()->request(
            'POST',
            'api/program',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(
                    [
                        'unique_id' => 'TESTPROG2',
                        'name' => 'Test Program 2',
                        'point' => 100,
                        'organization' => 'sharecare',
                        'url' => 'test'
                    ]
                ),
            ]
        );

        // Response MUST be status code 400
        $this->assertSame(
            400,
            $missingTldDomainResponse->getStatusCode()
        );
    }

    public function testInValidProgramUrlDomain()
    {
        $unknownDomainResponse = $this->getApiClient()->request(
            'POST',
            'api/program',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(
                    [
                        'unique_id' => 'TESTPROG2',
                        'name' => 'Test Program 2',
                        'point' => 100,
                        'organization' => 'sharecare',
                        'url' => 'test.someunknowndomain.com'
                    ]
                ),
            ]
        );
        // Response MUST be status code 400

        $this->assertSame(
            400,
            $unknownDomainResponse->getStatusCode()
        );
    }

    public function testInValidProgramUrlSubDomain()
    {
        $missingSubDomainResponse = $this->getApiClient()->request(
            'POST',
            'api/program',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(
                    [
                        'unique_id' => 'TESTPROG2',
                        'name' => 'Test Program 2',
                        'point' => 100,
                        'organization' => 'sharecare',
                        'url' => 'test.com'
                    ]
                ),
            ]
        );

        $this->assertSame(
            400,
            $missingSubDomainResponse->getStatusCode()
        );
    }
}
