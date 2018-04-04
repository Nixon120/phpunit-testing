<?php

namespace IntegrationTests\API\Token;

use IntegrationTests\API\AbstractAPITestCase;

class ValidTokenTest extends AbstractAPITestCase
{
    public function testFetchValidToken()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'token',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'auth' => [$this->getUser(), $this->getPassword()]
            ]
        );

        // Response MUST be status code 201
        $this->assertSame(201, $response->getStatusCode());
    }
}
