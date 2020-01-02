<?php
namespace Controllers\Participant;

use Controllers\AbstractModifyController;
use Slim\Http\Request;
use Slim\Http\Response;
use Services\Participant\ServiceFactory;
use Services\Participant\Participant;
use Traits\LoggerAwareTrait;

class Modify extends AbstractModifyController
{
    use LoggerAwareTrait;
    /**
     * @var Participant
     */
    private $service;

    public function __construct(
        Request $request,
        Response $response,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response);
        $this->service = $factory->getService();
    }

    public function insert()
    {
        $post = $this->request->getParsedBody()??[];
        $this->log('POST', $post);
        unset($post['credit']);
        if ($participant = $this->service->insert($post)) {
            $output = new OutputNormalizer($participant);
            return $this->returnJson(201, $output->get());
        }

        $errors = $this->service->getErrors();
        return $this->returnFormattedJsonError(400, $errors);
    }

    public function update($id)
    {
        $post = $this->request->getParsedBody()??[];
//        $this->log('PUT', $post);
        unset($post['credit']);
        //@TODO: perhaps we can figure out a clean way to remove unneeded fields without explicitly removing them
        //We don't need this.
        if ($participant = $this->service->update($id, $post)) {
            $output = new OutputNormalizer($participant);
            return $this->returnJson(200, $output->get());
        }

        $errors = $this->service->getErrors();
        return $this->returnFormattedJsonError(400, $errors);
    }

    private function log(string $action, array $data)
    {
        if(getenv('LOG_API_REQUEST_BODIES') == 1) {
            $this->getLogger()->notice(
                'Participant ' . $action,
                [
                    'data' => $data
                ]
            );
        }
    }

}
