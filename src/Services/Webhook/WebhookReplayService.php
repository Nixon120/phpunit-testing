<?php

namespace Services\Webhook;

use Entities\Webhook;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Traits\MongoAwareTrait;

class WebhookReplayService
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

    public function publish(Webhook $webhook, $data)
    {
        $this->webhook = $webhook;
        $this->data = $data;

        try {
            $response = $this
                ->getClient()
                ->send($this->getRequest());

            $this->log($response);
            return $response->getStatusCode();
        } catch (ConnectException $e) {
            $this->logFailed($e->getMessage());
            return 'Failed';
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
        return (array)$this->data['request']['headers'];
    }

    private function getRequestBody()
    {
        return (string)$this->data['request']['body'];
    }

    private function log(Response $response)
    {
        $stored_response = [];
        $stored_response['headers'] = $response->getHeaders();
        $stored_response['body'] = (string)$response->getBody();
        $stored_response['http_status'] = $response->getStatusCode();

        $this->saveResponse($stored_response);
        $this->updateHttpStatus($response->getStatusCode());
    }

    private function logFailed($message)
    {
        $this->saveResponse($message);
    }

    private function updateHttpStatus($status_code)
    {
        $this
            ->getCollection()
            ->findOneAndUpdate(
                ['_id' => $this->data['_id']],
                ['$set' => ['http_status' => $status_code]]
            );
    }

    private function saveResponse($response)
    {
        $this
            ->getCollection()
            ->findOneAndUpdate(
                ['_id' => $this->data['_id']],
                ['$push' => ['responses' => $response]]
            );
    }

    private function getCollection()
    {
        return $this
            ->getMongo()
            ->selectCollection(
                'webhook_' . $this->webhook->getId()
            );
    }
}
