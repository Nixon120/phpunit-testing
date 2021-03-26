<?php

namespace Services\Authentication;

use Services\CacheService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthAttemptValidation
{
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        if ($this->hasExceededInvalidAttempts() === true) {
            // Throw 423 Locked: The resource that is being accessed is locked.
            return $response->withStatus(423)
                ->withJson([
                      'message' => 'Locked',
                      'errors' => [_("The resource that is being accessed is locked")]
                ]);
        }
        return false;
    }

    /**
     * @return bool
     */
    private function hasExceededInvalidAttempts(): bool
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        if (in_array($ip, $this->getWhitelistedIpCollection()) === true) {
            return false;
        }

        $key = 'LOCKOUT_' . $ip;
        if ($this->cacheService->cachedItemExists($key) === true) {
            $attempts = $this->cacheService->getCachedItem($key);
            if ($attempts >= 5) {
                return true;
            }
        }

        return false;
    }

    private function getWhitelistedIpCollection(): array
    {
        $collection = [];
        $whitelistString = trim(getenv('IP_WHITELIST'));

        if (!empty($whitelistString)) {
            $whitelist = explode(',', $whitelistString);
            $collection = array_map(function (string $ip) {
                return trim($ip);
            }, $whitelist);
        }

        return $collection;
    }
}
