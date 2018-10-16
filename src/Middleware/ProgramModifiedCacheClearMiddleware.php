<?php

namespace Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Services\CacheService;
use Slim\Http\Request;
use Slim\Http\Response;

class ProgramModifiedCacheClearMiddleware
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var CacheService
     */
    private $cacheService;
    /**
     * @var Request
     */
    private $request;
    private $uniqueId;

    /**
     * ProgramModifiedCacheClearMiddleware constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable|null $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $next = null)
    {
        $response = $next($request, $response);
        $this->request = $request;

        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'])
            && (substr($response->getStatus(), 0, 1) < 3)
        ) {
            $this->clearClientSiteCacheIfExists();
        }

        return $response;
    }

    /**
     * @return string|bool
     */
    private function getProgramSubDomainAndDomain()
    {
        $sql = <<<SQL
SELECT CONCAT(Program.url, '.', Domain.url) as url
FROM `Program`
LEFT JOIN `Domain` ON Domain.id = Program.domain_id
WHERE Program.unique_id = ?
SQL;

        $args = [$this->uniqueId];

        $sth = $this->getDb()->prepare($sql);
        $sth->execute($args);
        $result = $sth->fetch(\PDO::FETCH_KEY_PAIR);
        return $result['url'];
    }

    /**
     * @return CacheService
     */
    private function getCacheService(): CacheService
    {
        if (!$this->cacheService) {
            $this->cacheService = $this->container['cacheService'];
        }
        return $this->cacheService;
    }

    /**
     * @return \PDO
     */
    private function getDb(): \PDO
    {
        return $this->container->get('database');

    }

    private function clearClientSiteCacheIfExists(): void
    {
        $this->uniqueId = $this->request->getAttribute('id');
        $programUrl = $this->getProgramSubDomainAndDomain();
        if ($this->getCacheService()->cachedItemExists($programUrl)) {
            $this->getCacheService()->clearItem($programUrl);
        }
    }
}