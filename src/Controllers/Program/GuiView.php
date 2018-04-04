<?php
namespace Controllers\Program;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Program\Program;
use Services\Program\ServiceFactory;
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

    public function renderCreatePage()
    {
        $program = new \Entities\Program;
        return $this->render(
            $this->getRenderer()->fetch(
                'program/form.phtml',
                [
                    'program' => $program,
                    'form_post_path' => '/program/create'
                ]
            )
        );
    }

    public function renderList()
    {
        return $this->render(
            $this->getRenderer()->fetch('program/list.phtml')
        );
    }

    public function renderListResult()
    {
        $get = $this->request->getQueryParams();
        $input = new InputNormalizer($get);
        $programs = $this->factory->getService()
            ->get($input);

        if (isset($get['method']) && $get['method'] === 'json') {
            $response = $this->response->withStatus(200)
                ->withJson($programs);

            return $response;
        }

        return $this->render(
            $this->getRenderer()->fetch('program/loop.phtml', [
                'programs' => $programs
            ]),
            'empty.phtml'
        );
    }

    public function renderSingle($id)
    {
        $program = $this->factory->getService()
            ->getSingle($id);

        if (is_null($program)) {
            return $this->renderGui404();
        }

        return $this->render(
            $this->getRenderer()->fetch(
                'program/form.phtml',
                [
                    'program' => $program,
                    'form_post_path' => '/program/view/' . $program->getUniqueId()
                ]
            )
        );
    }

    public function renderProgramLayout($programId)
    {
        if ($this->factory->getAuthenticatedUser()->getRole() !== 'superadmin') {
            $this->factory->getFlashMessenger()->addMessage('warning', 'Access denied');
            return $response = $this->response->withRedirect('/program');
        }

        $repository = $this->factory->getProgramRepository();
        $program = $repository->getProgram($programId);

        if (is_null($program)) {
            return $this->renderGui404();
        }

        if ($this->request->getParsedBody() !== null
        ) {
            $rows = $this->request->getParsedBody()['row'] ?? [];
            $repository->saveProgramLayout($program, $rows);
            return $response = $this->response->withStatus(200)
                ->withJson([]);
        }

        return $this->render(
            $this->getRenderer()->fetch(
                'program/program-layout.phtml',
                [
                    'program' => $program
                ]
            )
        );
    }

    public function deleteProgramLayoutRow($programId, $rowId)
    {
        $repository = $this->factory->getProgramRepository();
        $program = $repository->getProgram($programId);

        if (is_null($program)) {
            return $this->renderGui404();
        }

        if ($this->factory->getProgramRepository()->deleteLayoutRow($rowId) === true) {
            return $response = $this->response->withStatus(204);
        }

        return $response = $this->response->withStatus(400);
    }

    public function updateProgramPublishSetting($programId, $publish)
    {
        $repository = $this->factory->getProgramRepository();
        $program = $repository->getProgram($programId);
        if (is_null($program)) {
            return $this->renderGui404();
        }

        if ($this->factory->getProgramRepository()->updatePublishColumn($programId, $publish) === true) {
            return $response = $this->response->withStatus(204);
        }

        return $response = $this->response->withStatus(400);
    }
}
