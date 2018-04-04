<?php
namespace Controllers\Product;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Product\ProductRead;
use Services\Product\ServiceFactory;

class JsonView extends AbstractViewController
{
    /**
     * @var ProductRead
     */
    private $factory;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response);
        $this->factory = $factory;
    }

    public function list()
    {
        $get = $this->request->getQueryParams();
        $input = new InputNormalizer($get);
        $return = $this->factory->getProductRead()
            ->get($input);

        $output = new OutputNormalizer($return);
        $response = $this->response->withStatus(200)
            ->withJson($output->getList());

        return $response;
    }

    public function single($sku)
    {
        $product = $this->factory->getProductRepository()
            ->getProductBySku($sku);

        if (is_null($product)) {
            return $this->renderJson404();
        }

        $output = new OutputNormalizer($product);
        $response = $this->response->withStatus(200)
            ->withJson($output->get());

        return $response;
    }
}
