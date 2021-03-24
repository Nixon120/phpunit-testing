<?php

namespace IntegrationTests\API;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

abstract class AbstractAPITestCase extends TestCase
{
    private $apiClient;
    private $token;
    private $user = 'username';
    private $pass = 'password';

    protected function getApiClient()
    {
        if (!$this->apiClient) {
            $this->apiClient = new Client([
                'base_uri' => 'http://localhost/api',
                'http_errors' => false,
                'allow_redirects' => false
            ]);
        }

        return $this->apiClient;
    }

    protected function getToken()
    {
        if (!$this->token) {
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

            $json = json_decode($response->getBody());

            if (!$json->token) {
                throw new \Exception('Failed to get JSON WEB TOKEN');
            }

            $this->token = $json->token;
        }

        return $this->token;
    }

    protected function getHeaders()
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$this->getToken()}"
        ];
    }

    protected function getUser()
    {
        return $this->user;
    }

    protected function getPassword()
    {
        return $this->pass;
    }
}
