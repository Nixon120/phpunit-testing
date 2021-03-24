<?php

namespace IntegrationTests\API\Organization;

use IntegrationTests\API\AbstractAPITestCase;

class CreateValidWebhookTest extends AbstractAPITestCase
{
    public function testCreateValidWebhook()
    {
        $response = $this->getApiClient()->request(
            'POST',
            'api/organization/sharecare/webhooks',
            [
                'headers' => $this->getHeaders(),
                'body' => json_encode(
                    [
                        'title' => 'A Sweet Webhook',
                        'url' => 'https://example.com',
                        'username' => '',
                        'password' => '',
                        'event' => 'Transaction.create',
                        'active' => 1,
                        'immutable' => 0
                    ]
                ),
            ]
        );

        self::assertSame(204, $response->getStatusCode());
    }
}