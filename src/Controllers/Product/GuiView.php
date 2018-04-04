<?php
namespace Controllers\Product;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Product\ServiceFactory;
use Slim\Views\PhpRenderer;

class GuiView extends AbstractViewController
{
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
        parent::__construct($request, $response, $renderer);
        $this->factory = $factory;
    }

    public function renderList()
    {
        return $this->render(
            $this->getRenderer()->fetch('product/list.phtml')
        );
    }

    public function renderListResult()
    {
        $get = $this->request->getQueryParams();
        $input = new InputNormalizer($get);
        $products = $this->factory
            ->getProductRead()
            ->get($input);

        //@TODO NORMALIZE DATA
        if (isset($get['method']) && $get['method'] === 'json') {
            unset($get['method']);
            $response = $this->response->withStatus(200)
                ->withJson($products);

            return $response;
        }

        return $this->render(
            $this->getRenderer()->fetch('product/loop.phtml', [
                'products' => $products
            ]),
            'empty.phtml'
        );
    }

    public function renderSingle($sku)
    {
        $product = $this->factory
            ->getProductRepository()
            ->getProductBySku($sku);

        if (is_null($product)) {
            return $this->renderGui404();
        }

        return $this->render(
            $this->getRenderer()->fetch('product/form.phtml', [
                'product' => $product,
                'formAction' => '/product/view/' . $product->getSku()
            ])
        );
    }
}
