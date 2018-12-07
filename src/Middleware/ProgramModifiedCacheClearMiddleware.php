<?php

namespace Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Services\CacheService;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Route;

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
        /**
         * @var Response $response
         */
        $response = $next($request, $response);
        $this->request = $request;

        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'])
            && (substr($response->getStatusCode(), 0, 1) < 3)
        ) {
            $this->clearClientSiteCacheIfExists();
        }

        return $response;
    }

    /**
     * @return string|bool
     */
    private function getProgramSubDomainAndDomain($unique_id)
    {
        $sql = <<<SQL
SELECT CONCAT(Program.url, '.', Domain.url) as url
FROM `Program`
LEFT JOIN `Domain` ON Domain.id = Program.domain_id
WHERE Program.unique_id = ?
SQL;

        $args = [$unique_id];

        $sth = $this->getDb()->prepare($sql);
        $sth->execute($args);
        $result = $sth->fetch(\PDO::FETCH_ASSOC);
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
        /**
         * @var Route $route
         */
        $route = $this->request->getAttribute('route');
        $programUrl = $this->getProgramSubDomainAndDomain(
            $route->getArgument('id')
        );

        if (is_null($programUrl) === false) {
            if ($this->getCacheService()->cachedItemExists($programUrl)) {
                $this->getCacheService()->clearItem($programUrl);
            }
        }
    }
}