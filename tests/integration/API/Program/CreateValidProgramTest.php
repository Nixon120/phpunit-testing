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

    public function testCreateInValidProgram()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/program',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'unique_id' => 'TESTPROG1',
                    'name' => 'Test Program 1',
                    'point' => 0,
                    'organization' => 'sharecare'
                ]),
            ]
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
                'body' => json_encode([
                    'unique_id' => 'TESTPROG2',
                    'name' => 'Test Program 2',
                    'point' => 100,
                    'organization' => 'sharecare',
                    'url' => 'test'
                ]),
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
                'body' => json_encode([
                    'unique_id' => 'TESTPROG2',
                    'name' => 'Test Program 2',
                    'point' => 100,
                    'organization' => 'sharecare',
                    'url' => 'test.someunknowndomain.com'
                ]),
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
                'body' => json_encode([
                    'unique_id' => 'TESTPROG2',
                    'name' => 'Test Program 2',
                    'point' => 100,
                    'organization' => 'sharecare',
                    'url' => 'test.com'
                ]),
            ]
        );

        $this->assertSame(
            400,
            $missingSubDomainResponse->getStatusCode()
        );
    }
}
