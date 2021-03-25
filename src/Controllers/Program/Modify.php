<?php
namespace Controllers\Program;

use Controllers\AbstractModifyController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Program\Program;
use Services\Program\ServiceFactory;
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
     * @var Program
     */
    private $service;

    public function __construct(
        Request $request,
        Response $response,
        ServiceFactory $factory
    ) {
        parent::__construct($request, $response);
        $this->factory = $factory;
        $this->service = $factory->getService();
    }

    public function isValidJson($json) {
        if (!@json_decode(json_encode($json))) {
            return false;
        } else {
            return true;
        }
    }

    public function isValidDateFormat($jsonDate) {
        $tempDate = \DateTime::createFromFormat("Y-m-d H:i:s", $jsonDate);
        return $tempDate !== false && !array_sum($tempDate::getLastErrors());
    }

    public function insert()
    {
        $post = $this->request->getParsedBody() ?? [];
        $input = new InputNormalizer($post);
        $program = $this->service->insert($input);
        if ($program instanceof \Entities\Program) {
            $this->getLogger()->notice(
                'Program Created',
                [
                    'program_id' => $program->getUniqueId(),
                    'subsystem' => 'Program',
                    'action' => 'create',
                    'success' => true,
                ]
            );

            $output = new OutputNormalizer($program);

            return $this->returnJson(201, $output->get());
        }

        $errors = $this->service->getErrors();

        $this->getLogger()->error(
            'Program Create Failed',
            [
                'subsystem' => 'Program',
                'action' => 'create',
                'success' => false,
                'errors' => $errors
            ]
        );

        return $this->returnJson(400, $errors);
    }

    public function update($id)
    {
        $post = $this->request->getParsedBody()??[];
        if (!$this->isValidJson($post)) {
            throw new \Exception('Invalid JSON.');
        }

        if (!$this->isValidDateFormat($post['start_date'])) {
            throw new \Exception('Invalid Start Date.');
        }

        if (!$this->isValidDateFormat($post['end_date'])) {
            throw new \Exception('Invalid End Date.');
        }
        
        $input = new InputNormalizer($post);
        if (!array_key_exists($post['timezone'], $program->timeZones['America'])) {
            throw new \Exception('Invalid Timezone.');
        }

        $program = $this->service->update($id, $input);
        if ($program instanceof \Entities\Program) {
            $output = new OutputNormalizer($program);
            return $this->returnJson(200, $output->get());
        }

        $errors = $this->service->getErrors();
        return $this->returnJson(400, $errors);
    }
}
