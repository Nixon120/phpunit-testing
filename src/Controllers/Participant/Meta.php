<?php
namespace Controllers\Participant;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Services\Participant\ServiceFactory;
use Services\Participant\Participant;

class Meta
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var Participant
     */
    private $service;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        ServiceFactory $factory
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->service = $factory->getService();
    }

    public function customerServiceTransaction($organizationId, $uniqueId)
    {
        $participant = $this->service->getParticipantByOrganization($organizationId, $uniqueId);

        if ($participant !== null) {
            $post = $this->request->getParsedBody() ?? [];
            $post['issue_points'] = false;
            $offlineRedemptions = $this->service->participantRepository->getOfflineRedemptions($participant->getProgram());
            $selectedProduct = $post['products'][0]['sku'];

            if (in_array($selectedProduct, $offlineRedemptions) === false) {
                return $this->returnJson(404, ['Product does not match allowable offline redemption products for this program.']);
            }

            try {
                if ($transaction = $this->service->insert($organizationId, $uniqueId, $post)) {
                    //@TODO: Make sure domains do not include HTTPS / HTTP on entry or here ?
                    $output = new OutputNormalizer($transaction);
                    return $this->returnJson(201, $output->getTransaction());
                } else {
                    return $this->returnJson(400, $this->service->repository->getErrors());
                }
            } catch (TransactionServiceException $e) {
                return $this->returnJson(400, [$e->getMessage()]);
            }
        }

        return $this->returnJson(400, ['Resource does not exist']);
    }

    private function returnJson($statusCode, $return = [])
    {
        return $this->response->withStatus($statusCode)
            ->withJson($return);
    }
}
