<?php

namespace IntegrationTests\API\Program;

use IntegrationTests\API\AbstractAPITestCase;

class UpdateProgramTest extends AbstractAPITestCase
{
    public function testUpdateValidProgram()
    {
        $response = $this->getApiClient()->request(
            'PUT',
            'api/program/alldigitalrewards',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'name'=> 'alldigitalrewards',
                    'unique_id' => 'alldigitalrewards',
                    'start_date' => '2020-03-03 01:01:01',
                    'timezone' => 'America/Chicago',
                    'end_date' => '2025-03-03 01:01:01'
                ]),
            ]
        );

        // Response MUST be status code 200
        $this->assertSame(
            200,
            $response->getStatusCode()
        );
    }

    public function testUpdateInvalidProgram()
    {
        $response = $this->getApiClient()->request(
            'PUT',
            'api/program/alldigitalrewards',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'name' => null,
                    'unique_id' => 'NotARealProgramEver',
                    'start_date' => '2020-03-03 01:01:01',
                    'end_date' => '2025-03-03 01:01:01',
                    'timezone' => 'America/Chicago'
                ]),
            ]
        );

        // Response MUST be status code 400
        $this->assertSame(
            400,
            $response->getStatusCode()
        );
    }

    public function testUpdateInvalidProgramStartDate()
    {
        $response = $this->getApiClient()->request(
            'PUT',
            'api/program/alldigitalrewards',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'name' => 'test name',
                    'unique_id' => 'alldigitalrewards',
                    'start_date' => '2020-03-03 01:01',
                    'end_date' => '2025-03-03 01:01:01',
                    'timezone' => 'America/Chicago'
                ]),
            ]
        );

        // Response MUST be status code 400
        $this->assertSame(
            400,
            $response->getStatusCode()
        );
    }

    public function testUpdateInvalidProgramEndDate()
    {
        $response = $this->getApiClient()->request(
            'PUT',
            'api/program/sharecare',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'unique_id' => 'ed-test-2',
                    'start_date' => '2020-03-03 01:01:01',
                    'end_date' => '2025-03-03 01:01',
                    'timezone' => 'America/Chicago'
                ]),
            ]
        );

        // Response MUST be status code 400
        $this->assertSame(
            400,
            $response->getStatusCode()
        );
    }

    public function testUpdateInvalidProgramTimezone()
    {
        $response = $this->getApiClient()->request(
            'PUT',
            'api/program/sharecare',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'unique_id' => 'ed-test-2',
                    'start_date' => '2020-03-03 01:01:01',
                    'end_date' => '2025-03-03 01:01:01',
                    'timezone' => 'West/Phillidelphia'
                ]),
            ]
        );

        // Response MUST be status code 400
        $this->assertSame(
            400,
            $response->getStatusCode()
        );
    }
}
