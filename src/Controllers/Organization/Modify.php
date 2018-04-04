<?php

namespace Controllers\Organization;

use Controllers\AbstractModifyController;
use Slim\Http\Request;
use Slim\Http\Response;
use Services\Organization\UpdateOrganizationModel;
use Services\Organization\ServiceFactory;

class Modify extends AbstractModifyController
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
        Request $request,
        Response $response,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response);
        $this->factory = $factory;
        $this->service = $this->factory->getService();
    }

    public function insert()
    {
        $insertModel = $this->factory->getInsertModel();

        $post = $this->request->getParsedBody() ?? [];
        if ($organization = $insertModel->insert($post)) {
            $output = new OutputNormalizer($organization);
            return $this->returnJson(201, $output->get());
        }

        $errors = $insertModel->getErrors();
        return $this->returnFormattedJsonError(400, $errors);
    }

    public function update($id)
    {
        $post = $this->request->getParsedBody() ?? [];
        if ($organization = $this->service->update($id, $post)) {
            $output = new OutputNormalizer($organization);
            return $this->returnJson(200, $output->get());
        }

        $errors = $this->service->getErrors();
        return $this->returnFormattedJsonError(400, $errors);
    }

    public function deleteDomain($id)
    {
        if ($this->service->deleteDomain($id)) {
            return $this->returnJson(204);
        }

        return $this->returnJson(400, ['Something went wrong']);
    }
}
