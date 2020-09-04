<?php

namespace Controllers\Webhook;

use Controllers\AbstractViewController;
use Entities\Webhook;
use MongoDB\BSON\ObjectID;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Repositories\WebhookRepository;
use Services\Organization\ServiceFactory;
use Services\Webhook\WebhookReplayService;
use Slim\Views\PhpRenderer;
use Traits\MongoAwareTrait;

class JsonView extends AbstractViewController
{
    use MongoAwareTrait;
    /**
     * @var WebhookRepository
     */
    private $webhookRepository;
    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        PhpRenderer $renderer,
        ServiceFactory $serviceFactory
    ) {
        parent::__construct($request, $response, $renderer);
        $this->serviceFactory = $serviceFactory;
        $this->webhookRepository = $serviceFactory->getWebhookRepository();
    }

    public function renderList($organization_id)
    {
        $organization = $this->getOrg($organization_id);

        if (is_null($organization)) {
            return $this->renderGui404();
        }

        $webhooks = $this
            ->webhookRepository
            ->getOrganizationWebhooks($organization);

        $output = new OutputNormalizer($webhooks);

        return $this->response->withJson($output->getList());
    }

    private function getOrg($org_id)
    {
        $orgService = $this->serviceFactory->getService();
        return $orgService->getSingle($org_id);
    }

    public function viewWebhook($org_id, $webhook_id)
    {
        $webhook = $this
            ->webhookRepository
            ->getWebhook($webhook_id);
        $webhookOutput = new OutputNormalizer($webhook);

        $org = $this->getOrg($org_id);

        if (is_null($webhook)
            || $webhook->getOrganizationId() !== $org->getId()) {
            return $this
                ->response
                ->withStatus(404);
        }

        return $this
            ->response
            ->withJson($webhookOutput->get());
    }

    public function listWebhookLogs($org_id, $webhook_id)
    {
        $webhook = $this
            ->webhookRepository
            ->getWebhook($webhook_id);

        $org = $this->getOrg($org_id);

        if (is_null($webhook) || $webhook->getOrganizationId() !== $org->getId()) {
            return $this
                ->response
                ->withStatus(404);
        }

        return $this
            ->response
            ->withJson($this->getWebhookLogs($webhook_id));
    }

    public function viewWebhookLog($org_id, $webhook_id, $webhook_log_id)
    {
        $webhook = $this
            ->webhookRepository
            ->getWebhook($webhook_id);

        $org = $this->getOrg($org_id);

        if (is_null($webhook)
            || $webhook->getOrganizationId() !== $org->getId()) {
            return $this->renderGui404();
        }

        try {
            $log = $this->getWebhookLog($webhook_id, $webhook_log_id);
        } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
            return $this->renderGui404();
        }

        return $this
            ->response
            ->withJson($log);
    }

    public function replayWebhookLog($org_id, $webhook_id, $webhook_log_id)
    {
        $webhook = $this
            ->webhookRepository
            ->getWebhook($webhook_id);

        $org = $this->getOrg($org_id);

        if (is_null($webhook)
            || $webhook->getOrganizationId() != $org->getId()) {
            return $this->renderGui404();
        }

        try {
            $log = $this->getWebhookLog($webhook_id, $webhook_log_id);
        } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
            return $this->renderGui404();
        }

        // If the http_status = success, don't replay.

        $webhookReplayService = new WebhookReplayService();
        $http_status_code = $webhookReplayService->publish($webhook, $log);

        return $this
            ->response
            ->withJson(['http_status_code' => $http_status_code]);
    }

    private function getWebhookLogs($webhook_id)
    {
        $collection = $this
            ->getMongo()
            ->selectCollection(
                'webhook_' . $webhook_id
            );

        $filter = [];

        $query_params = $this->request->getQueryParams();
        $lastId = !empty($query_params['last_id']) && $query_params['last_id'] !== "" ? $query_params['last_id'] : null;
        if ($lastId !== null) {
            $filter['_id'] = [
                '$lt' => new ObjectID($lastId)
            ];
        }

        if (!empty($query_params['status_code']) && $query_params['status_code'] !== 'All') {
            $status_code = $query_params['status_code'];

            if (is_numeric($query_params['status_code'])) {
                $status_code = (int)$query_params['status_code'];
            }

            $filter = [
                'http_status' => $status_code
            ];
        }

        $cursor = $collection->find(
            $filter,
            [
                'limit' => 100,
                'sort' => [
                    '_id' => -1
                ]
            ]
        );

        return array_map(
            function ($item) {
                return $this->removeAuthHeaderFromLog($item);
            },
            $cursor->toArray()
        );
    }

    private function getWebhookLog($webhook_id, $webhook_log_id)
    {
        $collection = $this
            ->getMongo()
            ->selectCollection(
                'webhook_' . $webhook_id
            );

        $log = $collection->findOne(['_id' => new ObjectID($webhook_log_id)]);
        return $this->removeAuthHeaderFromLog($log);
    }

    private function removeAuthHeaderFromLog($log)
    {
        if (isset($log['request']['headers']['Authorization'])) {
            unset($log['request']['headers']['Authorization']);
        }

        return $log;
    }

    public function insertWebhook($organization_id)
    {
        $organization = $this->getOrg($organization_id);

        if (is_null($organization)) {
            return $this->renderGui404();
        }

        $post = $this->request->getParsedBody() ?? [];
        $post['organization_id'] = $organization->getId();

        $webhook = new Webhook();
        $webhook->exchange($post);

        if (!$this->webhookRepository->isValid($webhook)) {
            $errors = $this->webhookRepository->getErrors();

            return $this
                ->response
                ->withStatus(400)
                ->withJson($errors);
        }

        $this->webhookRepository->insert($webhook->toArray());

        return $this
            ->response
            ->withStatus(204);
    }

    public function deleteWebhook($org_id, $webhook_id)
    {
        if (!$this->getOrg($org_id)) {
            return $this->renderJson404();
        }

        if ($this->webhookRepository->delete($webhook_id)) {
            return $this->response->withStatus(204);
        }

        // Something failed and I'm too lazy to decipher the matrix to throw a proper error.
        //  All I see here is blond, brunette...
        return $this->renderJson404();
    }

    public function modifyWebhook($webhook_id)
    {
        // This should only modify if the webhook is mutable.
        $webhook = $this
            ->webhookRepository
            ->getWebhook($webhook_id);

        if ($webhook->isImmutable()) {
            return $this->response->withStatus(400)->withJson(['error' => ['Webhook is immutable.']]);
        }

        $post = $this->request->getParsedBody() ?? [];

        $webhook->exchange($post);

//        $webhook = new Webhook;
//        $webhook->setTitle($post['title']);
//        $webhook->setEvent($post['event']);
//        $webhook->setUrl($post['url']);
//        $webhook->setUsername($post['username']);
//        $webhook->setPassword($post['password']);

        if (!$this->webhookRepository->isValid($webhook)
            || !$this->webhookRepository->updateWebhook($webhook_id, $webhook->toArray())
        ) {
            $errors = $this->webhookRepository->getErrors();
            return $this->response->withStatus(400)->withJson(['error' => $errors]);
        }

        return $this->response->withStatus(204);
    }
}
