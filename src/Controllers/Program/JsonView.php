<?php
namespace Controllers\Program;

use Controllers\AbstractViewController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Authentication\Authenticate;
use Services\Program\Program;
use Services\Program\ServiceFactory;

class JsonView extends AbstractViewController
{
    /**
     * @var ServiceFactory
     */
    private $factory;

    /**
     * @var Program
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

    public function metrics($id)
    {
        $program = $this->service->getSingle($id);
        
        return $this->response->withStatus(200)
            ->withJson([
                'participant_total' => $this->service->repository->getParticiantTotal($program->id),
                'transaction_total' => $this->service->repository->getTransactionTotal($program->id)
            ]);
    }

    public function single($id)
    {
        // Look up by ID first.
        $program = $this->service->getSingle($id);

        if (is_null($program)) {
            // Failing that, lookup up by domain.
            $program = $this->service->repository->getProgramByDomain($id);
        }

        //@TODO handle this with middleware?
        if (is_null($program)) {
            return $this->renderJson404();
        }

        $output = new OutputNormalizer($program);
        $response = $this->response->withStatus(200)
            ->withJson($output->get());

        return $response;
    }

    public function listCreditAdjustmentsByMeta($id)
    {
        $program = $this->service->getSingle($id);
        $get = $this->request->getQueryParams();
        $get['program'] = $program->getId();

        $adjustments = $this->service->getAdjustments($get);

        $response = $this->response->withStatus(200)
            ->withJson($adjustments);

        return $response;
    }

    public function saveProductCriteria($programId)
    {
        $repository = $this->factory->getProgramRepository();
        $program = $repository->getProgram($programId);

        if (is_null($program)) {
            return $this->renderJson404();
        }

        if ($this->request->getParsedBody() !== null
            && $repository->saveProductCriteria($program, $this->request->getParsedBody())
        ) {
            return $response = $this->response->withStatus(200)
                ->withJson([]);
        }

        return $response = $this->response->withStatus(400)
            ->withJson([]);
    }

    public function cloneProductCriteria()
    {
        $repository = $this->factory->getProgramRepository();
        $payload = $this->request->getParsedBody();
        $cloneFrom = $payload['from_id'] ?? null;
        $cloneTo = $payload['to_id'] ?? null;
        $cloneFromProgram = $repository->getProgram($cloneFrom);
        $cloneToProgram = $repository->getProgram($cloneTo);

        if ($cloneFromProgram === null || $cloneToProgram === null) {
            return $response = $this->response->withStatus(404)
                ->withJson(['Resource does not exist']);
        }

        if ($repository->cloneProductCriteria($cloneFrom, $cloneTo) === true) {
            return $response = $this->response->withStatus(200)
                ->withJson([]);
        }

        return $response = $this->response->withStatus(400)
            ->withJson([]);
    }

    public function saveFeaturedProducts($programId)
    {
       $repository = $this->factory->getProgramRepository();
       $program = $repository->getProgram($programId);

       if (is_null($program)) {
           return $this->renderJson404();
       }

       $skuContainer = $this->request->getParsedBody()['products'] ?? [];
       $featuredPageTitle = $this->request->getParsedBody()['featured_page_title'] ?? '';
       if ($this->request->getParsedBody() !== null
           && $repository->saveFeaturedProducts($program, $skuContainer, $featuredPageTitle)
       ) {
           return $response = $this->response->withStatus(200)
               ->withJson([]);
       }

       return $response = $this->response->withStatus(400)
           ->withJson([]);
    }

    public function layout($id)
    {
        $repository = $this->factory->getProgramRepository();
        // Look up by ID first.
        $program = $this->service->getSingle($id);

        if (is_null($program)) {
            // Failing that, lookup up by domain.
            $program = $this->service->repository->getProgramByDomain($id);
        }

        //@TODO handle this with middleware?
        if (is_null($program)) {
            return $this->renderJson404();
        }

        if ($this->request->getParsedBody() !== null
        ) {
            $rows = $this->request->getParsedBody()['row'] ?? null;
            $repository->saveProgramLayout($program, $rows);
            return $response = $this->response->withStatus(200)
                ->withJson([]);
        }

        $output = new OutputNormalizer($program->getLayoutRows());
        $response = $this->response->withStatus(200)
            ->withJson($output->getLayout());

        return $response;
    }

    public function layoutClone()
    {
        $repository = $this->factory->getProgramRepository();
        $payload = $this->request->getParsedBody();
        $cloneFromId = $payload['from_id'] ?? null;
        $cloneToId = $payload['to_id'] ?? null;
        // Look up by ID first.
        $cloneFrom = $this->service->getSingle($cloneFromId, true);
        if (is_null($cloneFrom)) {
            // Failing that, lookup up by domain.
            $cloneFrom = $this->service->repository->getProgramByDomain($cloneFromId);
        }
        $clonedToProgram = $this->service->getSingle($cloneToId, true);

        //@TODO handle this with middleware?
        if ($cloneFrom === null || $clonedToProgram === null) {
            return $this->renderJson404();
        }

        if ($payload !== null) {
            $cloned = $repository->cloneLayout($cloneFrom, $clonedToProgram);
            if ($cloned === true) {
                return $response = $this->response->withStatus(200)
                    ->withJson([]);
            }
            return $response = $this->response->withStatus(400)
                ->withJson($repository->getErrors());
        }

        $output = new OutputNormalizer($clonedToProgram->getLayoutRows());
        $response = $this->response->withStatus(200)
            ->withJson($output->getLayout());

        return $response;
    }

    public function autoRedemption($id)
    {
        $repository = $this->factory->getProgramRepository();

        $program = $this->service->getSingle($id);
        $data = $this->request->getParsedBody();

        if (is_null($program) === true) {
            // Failing that, lookup up by domain.
            $program = $this->service->repository->getProgramByDomain($id);
        }

        if (is_null($program)) {
            return $this->renderJson404();
        }

        if ($data !== null) {
            $saved = $repository->saveProgramAutoRedemption($program, $data);
            if ($saved === true) {
                return $response = $this->response->withStatus(200)
                    ->withJson([]);
            }
            return $response = $this->response->withStatus(400)
                ->withJson($repository->getErrors());
        }

        return $response = $this->response->withStatus(400)
            ->withJson(['data is not valid']);
    }

    public function offlineRedemption($id)
    {
        $repository = $this->factory->getProgramRepository();

        $program = $this->service->getSingle($id);

        if (is_null($program) === true) {
            return $this->renderJson404();
        }

        if ($this->request->getParsedBody() !== null) {
            $data = $this->request->getParsedBody();
            $repository->saveProgramOfflineRedemption($program, $data);
            return $response = $this->response->withStatus(200)
                ->withJson([]);
        }

        $offlineRedemptions = $repository->getOfflineRedemptions($program);
        $response = $this->response->withStatus(200)
            ->withJson($offlineRedemptions);

        return $response;
    }
}
