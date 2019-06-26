<?php

namespace Controllers\Program;

use Controllers\AbstractViewController;
use Entities\User;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Program\ServiceFactory;
use Slim\Views\PhpRenderer;
use Traits\RendererTrait;

class Product extends AbstractViewController
{
    use RendererTrait;
    /**
     * @var ServiceFactory
     */
    private $factory;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        PhpRenderer $renderer,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response);
        $this->renderer = $renderer;
        $this->factory = $factory;

        if ($factory->getAuthenticatedUser()->getRole() !== 'superadmin') {
            $factory->getFlashMessenger()->addMessage('warning', 'Access denied');
            return $response = $this->response->withRedirect('/program');
        }
    }

    private function getProgramFilter()
    {
        $get = $this->request->getQueryParams();
        // Setup filters here for program
        $program = $this->factory->getProgramRepository()->getProgram($get['program']);

        if (is_null($program)) {
            // return false
            throw new \Exception('Unknown program');
        }

        $activeFilters = $program->getProductCriteria()->getFilterArray();

        return base64_encode(json_encode([
            'unique_id' => $program->getUniqueId(),
            'filters' => $activeFilters
        ]));
    }

    public function renderProductList()
    {
        $get = $this->request->getQueryParams();
        $gui = $get['gui'] ?? null;
        $program = $get['program'] ?? null;
        if ($program !== null) {
            $program = $this->getProgramFilter();
            $get['program'] = $program;
        }
        unset($get['title'], $get['program_id'], $get['gui']);

        $input = new InputNormalizer($get);
        $products = $this->factory
            ->getProductService()
            ->get($input);

        if ($gui !== null) {
            return $this->render(
                $this->getRenderer()->fetch('product/loop.phtml', [
                    'products' => $products
                ]),
                'empty.phtml'
            );
        }

        $productContainer = $this->factory->getProductService()->getProductSearchList($input);
        $response = $this->response->withStatus(200)
            ->withJson($productContainer);

        return $response;
    }

    public function renderCategoryList()
    {
        $get = $this->request->getQueryParams();

        $categories = $this->factory
            ->getProductService()
            ->getCategories($get);

        $response = $this->response->withStatus(200)
            ->withJson($categories);

        return $response;
    }

    public function renderBrandList()
    {
        $get = $this->request->getQueryParams();

        $brands = $this->factory
            ->getProductService()
            ->getBrands($get);

        $response = $this->response->withStatus(200)
            ->withJson($brands);

        return $response;
    }

    public function renderProductManagement($programId)
    {
        $repository = $this->factory->getProgramRepository();
        $program = $repository->getProgram($programId);

        if (is_null($program)) {
            return $this->renderGui404();
        }

        return $this->render(
            $this->getRenderer()->fetch(
                'program/product-management.phtml',
                [
                    'program' => $program
                ]
            )
        );
    }

    public function saveProductCriteria($programId)
    {
        $repository = $this->factory->getProgramRepository();
        $program = $repository->getProgram($programId);

        if (is_null($program)) {
            return $this->renderJson404();
        }

        if ($this->request->getParsedBody() !== null
            && $repository->saveProductCriteria($program, $this->request->getParsedBody())
        ) {
            return $response = $this->response->withStatus(200)
                ->withJson([]);
        }

        return $response = $this->response->withStatus(400)
            ->withJson([]);
    }

    public function saveFeaturedProducts($programId)
    {
        $repository = $this->factory->getProgramRepository();
        $program = $repository->getProgram($programId);

        if (is_null($program)) {
            return $this->renderJson404();
        }

        $skuContainer = $this->request->getParsedBody()['products'] ?? [];
        $featuredPageTitle = $this->request->getParsedBody()['featured_page_title'] ?? '';
        if ($this->request->getParsedBody() !== null
            && $repository->saveFeaturedProducts($program, $skuContainer, $featuredPageTitle)
        ) {
            return $response = $this->response->withStatus(200)
                ->withJson([]);
        }

        return $response = $this->response->withStatus(400)
            ->withJson([]);
    }
}
