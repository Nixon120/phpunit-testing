<?php

namespace AllDigitalRewards\RewardStack\Services;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Traits\LoggerAwareTrait;

class ReportApiService
{
    use LoggerAwareTrait;

    private $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * @param string $userEmail
     * @return bool
     */
    public function removeUserReports(string $userEmail): bool
    {
        $error = null;
        try {
            $response = $this->getClient()->request(
                'POST',
                '/remove',
                [
                    'headers' => [
                        'Authorization' => "Bearer {$this->token}",
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode(['user_email' => $userEmail])
                ]
            );
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
                return true;
            }
            $error = json_decode($response->getBody());
        } catch (GuzzleException | Exception $e) {
            $error = $e->getMessage();
        }

        $this->getLogger()->error(
            'ReportApiService RemoveUserReports request Failure',
            [
                'success' => false,
                'action' => 'delete',
                'error' => $error
            ]
        );

        return false;
    }


    private function getClient()
    {
        $baseUri = rtrim(getenv('REPORT_API_URL'), '/');
        return new \GuzzleHttp\Client([
             'base_uri' => $baseUri,
             'http_errors' => false,
             'allow_redirects' => false
         ]);
    }
}
