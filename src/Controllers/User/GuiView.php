<?php
namespace Controllers\User;

use Controllers\AbstractViewController;
use Entities\User;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\User\ServiceFactory;
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
        return $this->render(
            $this->getRenderer()->fetch('user/form.phtml', [
                'user' => new User,
                'formAction' => '/user/create',
                'formContext' => 'create'
            ])
        );
    }

    public function renderList()
    {
        return $this->render(
            $this->getRenderer()->fetch('user/list.phtml')
        );
    }

    public function renderListResult()
    {
        $get = $this->request->getQueryParams();
        $input = new InputNormalizer($get);
        $users = $this->factory->getUserRead()->get($input);

        return $this->render(
            $this->getRenderer()->fetch('user/loop.phtml', [
                'users' => $users
            ]),
            'empty.phtml'
        );
    }

    public function renderSingle($id)
    {
        $user = $this->factory->getUserRead()->getById($id);

        if (is_null($user)) {
            return $this->renderGui404();
        }

        return $this->render(
            $this->getRenderer()->fetch('user/form.phtml', [
                'user' => $user,
                'formAction' => '/user/view/' . $user->getId(),
                'formContext' => 'update'
            ])
        );
    }
}
