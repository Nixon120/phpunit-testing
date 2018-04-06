<?php

namespace AllDigitalRewards\RewardStack;

class ApiErrorHandler
{
    public function __invoke(\Slim\Http\Request $request, \Slim\Http\Response $response, \Exception $exception)
    {
        $status = 500;
        $data = [
            'status' => 'error',
            'message' => $exception->getMessage()
        ];
        $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        return $response->withStatus($status)
            ->withHeader('Content-Type', 'application/json')
            ->write($body);
    }
}
