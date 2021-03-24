<?php

namespace IntegrationTests\API\Sweepstake;

use IntegrationTests\API\AbstractAPITestCase;

class SweepstakeEntryTest extends AbstractAPITestCase
{
    public function testCreateSweepstakesWithoutEnoughPoints()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/user/TESTPARTICIPANT1/sweepstake',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(['entryCount' => 10]),
            ]
        );

        $decodedResponse = json_decode((string)$response->getBody());
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame("Participant does not have enough points for this transaction.", $decodedResponse[0]);
    }

    public function testCreateSweepstakesEntry()
    {
        $this->addPointsToParticipant(1000);
        $response = $this->getApiClient()->request(
            'POST',
            'api/user/TESTPARTICIPANT1/sweepstake',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(['entryCount' => 1]),
            ]
        );

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateSweepstakesWithExceededMaximumEntries()
    {
        $this->addPointsToParticipant(1000000);
        $response = $this->getApiClient()->request(
            'POST',
            'api/user/TESTPARTICIPANT1/sweepstake',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(['entryCount' => 1001]),
            ]
        );

        $decodedResponse = json_decode((string)$response->getBody())[0];
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            "Participant will exceed the maximum entry count for this sweepstake campaign",
            $decodedResponse
        );
    }

    private function addPointsToParticipant($points)
    {
        $this->getApiClient()->request(
            'POST',
            'api/user/TESTPARTICIPANT1/adjustment',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode([
                    'type' => 'credit',
                    'amount' => $points
                ])
            ]
        );
    }
}
