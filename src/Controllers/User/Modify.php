<?php
namespace Controllers\User;

use Controllers\AbstractModifyController;
use Slim\Http\Request;
use Slim\Http\Response;
use Services\User\ServiceFactory;

class Modify extends AbstractModifyController
{
    /**
     * @var ServiceFactory
     */
    private $factory;

    public function __construct(
        Request $request,
        Response $response,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response);
        $this->factory = $factory;
    }

    public function insert()
    {
        $post = $this->request->getParsedBody()??[];
        if ($participant = $this->factory->getUserModify()->insert($post)) {
            $output = new OutputNormalizer($participant);
            return $this->returnJson(201, $output->get());
        }

        $errors = $this->factory->getUserModify()->getErrors();
        return $this->returnJson(400, $errors);
    }

    public function update($id)
    {
        $post = $this->request->getParsedBody() ?? [];

        if ($user = $this->factory->getUserModify()->update($id, $post)) {
            $output = new OutputNormalizer($user);
            return $this->returnJson(200, $output->get());
        }

        $errors = $this->factory->getUserModify()->getErrors();
        return $this->returnJson(400, $errors);
    }

    public function patch($userId)
    {
        $patch = $this->request->getParsedBody() ?? [];

        if ($user = $this->factory->getUserModify()->patch($userId, $patch)) {
            $output = new OutputNormalizer($user);
            return $this->returnJson(200, $output->get());
        }

        $errors = $this->factory->getUserModify()->getErrors();
        return $this->returnJson(400, $errors);
    }
}
