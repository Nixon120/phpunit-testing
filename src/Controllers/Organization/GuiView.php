<?php

namespace Controllers\Organization;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Organization\UpdateOrganizationModel;
use Services\Organization\ServiceFactory;
use Slim\Views\PhpRenderer;

class GuiView extends AbstractViewController
{
    /**
     * @var UpdateOrganizationModel
     */
    private $service;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        PhpRenderer $renderer,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response, $renderer);
        $this->factory = $factory;
        $this->service = $factory->getService();
    }

    public function renderCreatePage()
    {
        return $this->render(
            $this->getRenderer()->fetch(
                'organization/form.phtml',
                [
                    'organization' => new \Entities\Organization,
                    'form_post_path' => '/organization/create'
                ]
            )
        );
    }

    public function renderList()
    {
        return $this->render(
            $this->getRenderer()->fetch('organization/list.phtml')
        );
    }

    public function renderListResult()
    {
        $get = $this->request->getQueryParams();
        $input = new InputNormalizer($get);
        $organizations = $this->service->get($input);

        if (isset($get['method']) && $get['method'] === 'json') {
            $response = $this->response->withStatus(200)
                ->withJson($organizations);

            return $response;
        }

        return $this->render(
            $this->getRenderer()->fetch('organization/loop.phtml', [
                'organizations' => $organizations
            ]),
            'empty.phtml'
        );
    }

    public function renderSingle($id)
    {
        $organization = $this->service->getSingle($id);

        if (is_null($organization)) {
            return $this->renderGui404();
        }

        return $this->render(
            $this->getRenderer()->fetch(
                'organization/form.phtml',
                [
                    'organization' => $organization,
                    'form_post_path' => '/organization/view/' . $organization->getUniqueId()
                ]
            )
        );
    }
}
