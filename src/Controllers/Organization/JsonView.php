<?php
namespace Controllers\Organization;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Organization\UpdateOrganizationModel;
use Services\Organization\ServiceFactory;

class JsonView extends AbstractViewController
{
    /**
     * @var ServiceFactory
     */
    private $factory;

    /**
     * @var UpdateOrganizationModel
     */
    private $service;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response);
        $this->factory = $factory;
        $this->service = $factory->getService();
    }

    public function list()
    {
        $get = $this->request->getQueryParams();
        $input = new InputNormalizer($get);
        $return = $this->service->get($input);
        $output = new OutputNormalizer($return);
        $response = $this->response->withStatus(200)
            ->withJson($output->getList());
        return $response;
    }

    public function single($id)
    {
        $organization = $this->service->getSingle($id);
        if (is_null($organization)) {
            return $this->renderJson404();
        }
        $output = new OutputNormalizer($organization);
        $response = $this->response->withStatus(200)
            ->withJson($output->get());
        return $response;
    }

    public function domainList($uniqueId = false)
    {
        //@TODO move this to organization service
        $get = $this->request->getQueryParams();
        if ($uniqueId === false && !empty($get['organization'])) {
            //@TODO check if org exist, throw error
            $uniqueId = $get['organization'];
        }

        return $this->response->withStatus(200)
            ->withJson(
                $this
                    ->factory
                    ->getOrganizationRepository()
                    ->getOrganizationDomains($uniqueId)
            );
    }
}
