<?php

namespace Controllers\Organization;

use Controllers\AbstractModifyController;
use Services\Organization\ServiceFactory;
use Services\Organization\UpdateOrganizationModel;
use Slim\Http\Request;
use Slim\Http\Response;
use Traits\LoggerAwareTrait;

class Modify extends AbstractModifyController
{
    use LoggerAwareTrait;

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
            $this->getLogger()->notice(
                'Organization Created',
                [
                    'organization_id' => $organization->getUniqueId(),
                    'subsystem' => 'Organization',
                    'action' => 'create',
                    'success' => true,
                ]
            );
            $output = new OutputNormalizer($organization);

            return $this->returnJson(201, $output->get());
        }

        $errors = $insertModel->getErrors();

        $this->getLogger()->error(
            'Organization Create Failed',
            [
                'subsystem' => 'Organization',
                'action' => 'create',
                'success' => false,
                'errors' => $errors
            ]
        );

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
        if (!$this->factory->getProgramRepository()->isDomainDeletable($id)) {
            //Don't delete the domain if it is tied to a Program
            return $this->returnJson(400, ['Domain cannot be deleted. It is being used by a marketplace.']);
        }

        if ($this->service->deleteDomain($id)) {
            return $this->returnJson(204);
        }

        return $this->returnJson(400, ['Something went wrong.']);
    }
}
