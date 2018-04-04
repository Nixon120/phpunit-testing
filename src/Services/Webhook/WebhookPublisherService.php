<?php

namespace Services\Webhook;

use Entities\Webhook;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Traits\MongoAwareTrait;

class WebhookPublisherService
{
    use MongoAwareTrait;

    /**
     * @var Webhook
     */
    private $webhook;
    /**
     * @var array
     */
    private $data;

    public function publish(Webhook $webhook, array $data)
    {
        $this->webhook = $webhook;
        $this->data = $data;

        try {
            $response = $this
                ->getClient()
                ->send($this->getRequest());

            $this->log($response);
        } catch (ConnectException $e) {
            $this->logFailed($e->getMessage());
        }
    }

    private function getClient()
    {
        return new Client([
            'http_errors' => false,
            'allow_redirects' => true
        ]);
    }

    private function getRequest()
    {
        $request = new Request(
            'POST',
            $this->webhook->getUrl(),
            $this->getRequestHeaders(),
            $this->getRequestBody()
        );

        return $request;
    }

    private function getRequestHeaders()
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        if ($this->webhookRequiresAuth()) {
            $headers['Authorization'] = "Basic " . $this->getBasicAuthHash();
        }

        return $headers;
    }

    private function webhookRequiresAuth()
    {
        if ($this->webhook->getUsername() && $this->webhook->getPassword()) {
            return true;
        }

        return false;
    }

    private function getBasicAuthHash()
    {
        return base64_encode(
            $this->webhook->getUsername() .
            ":" .
            $this->webhook->getPassword()
        );
    }

    private function getRequestBody()
    {
        return json_encode($this->data);
    }

    private function log(Response $response)
    {
        $webhook_log = [];

        $webhook_log['request_time'] = new \DateTime();
        $webhook_log['http_status'] = $response->getStatusCode();

        $stored_response = [];
        $stored_response['headers'] = $response->getHeaders();
        $stored_response['body'] = (string)$response->getBody();
        $stored_response['http_status'] = $response->getStatusCode();

        $webhook_log['responses'] = [];
        $webhook_log['responses'][] = $stored_response;

        $webhook_log['request']['headers'] = $this->getRequestHeaders();
        $webhook_log['request']['body'] = (string)$this->getRequestBody();

        $this->saveWebhookLog($webhook_log);
    }

    private function logFailed($message)
    {
        $webhook_log = [];

        $webhook_log['request_time'] = new \DateTime();
        $webhook_log['http_status'] = 'Failed';

        $webhook_log['responses'] = [];
        $webhook_log['responses'][] = $message;

        $webhook_log['request']['headers'] = $this->getRequestHeaders();
        $webhook_log['request']['body'] = (string)$this->getRequestBody();

        $this->saveWebhookLog($webhook_log);
    }

    private function saveWebhookLog($webhook_log)
    {
        $collection = $this
            ->getMongo()
            ->selectCollection(
                'webhook_' . $this->webhook->getId()
            );
        $collection->insertOne($webhook_log);
    }
}
