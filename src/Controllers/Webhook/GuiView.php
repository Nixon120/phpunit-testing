<?php

namespace Controllers\Webhook;

use Controllers\AbstractViewController;
use Entities\Webhook;
use MongoDB\BSON\ObjectID;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Repositories\WebhookRepository;
use Services\Organization\ServiceFactory;
use Slim\Views\PhpRenderer;
use Traits\MongoAwareTrait;

class GuiView extends AbstractViewController
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

        return $this->render(
            $this->getRenderer()->fetch(
                'webhook/list.phtml',
                [
                    'organization' => $organization,
                    'webhooks' => $webhooks,
                    'form_post_path' => '/organization/view/' . $organization->getUniqueId()
                ]
            )
        );
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

        $org = $this->getOrg($org_id);

        if (is_null($webhook)
            || $webhook->getOrganizationId() != $org->getId()) {
            return $this->renderGui404();
        }

        return $this->render(
            $this->getRenderer()->fetch(
                'webhook/view.phtml',
                [
                    'webhook' => $webhook,
                    'organization' => $org,
                ]
            )
        );
    }

    public function deleteWebhook($org_id, $webhook_id)
    {
        $isDeleted = $this
            ->webhookRepository
            ->delete($webhook_id);

        $org = $this->getOrg($org_id);

        if (!$isDeleted
            || is_null($org)) {
            return $this->renderGui404();
        }

        return json_encode(['success' => true]);
    }

    public function webhookModalView($org_id, $webhook_id)
    {
        $webhook = $this
            ->webhookRepository
            ->getWebhook($webhook_id);

        $org = $this->getOrg($org_id);

        if (is_null($webhook)
            || $webhook->getOrganizationId() != $org->getId()) {
            return $this->renderGui404();
        }

        return json_encode(
            [
                'webhook' => $webhook,
                'organization' => $org,
            ]
        );
    }

    public function modifyWebhook($webhook_id)
    {
        $post = $this->request->getParsedBody() ?? [];

        $webhook = new Webhook;
        $webhook->setTitle($post['title']);
        $webhook->setEvent($post['event']);
        $webhook->setOrganizationId((int)$post['organization_id']);
        $webhook->setUrl($post['url']);
        $webhook->setUsername($post['username']);
        $webhook->setPassword($post['password']);

        if (!$this->webhookRepository->isValid($webhook)
            || !$this->webhookRepository->updateWebhook($webhook_id, $post)
        ) {
            $errors = $this->webhookRepository->getErrors();
            return json_encode([
                'error' => $errors
            ]);
        }

        return json_encode([
            'success' => true
        ]);
    }

    public function viewWebhookLog($org_id, $webhook_id, $webhook_log_id)
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

        return $this->render(
            $this->getRenderer()->fetch(
                'webhook/webhook_log_details.phtml',
                [
                    'webhook' => $webhook,
                    'organization' => $org,
                    'log' => $log
                ]
            )
        );
    }

    private function getWebhookLogs($webhook_id, $status_code = null)
    {
        $collection = $this
            ->getMongo()
            ->selectCollection(
                'webhook_' . $webhook_id
            );

        return $collection->find([

        ]);
    }

    private function getWebhookLog($webhook_id, $webhook_log_id)
    {
        $collection = $this
            ->getMongo()
            ->selectCollection(
                'webhook_' . $webhook_id
            );

        return $collection->findOne(['_id' => new ObjectID($webhook_log_id)]);
    }

    public function insertWebhook($organization_id)
    {
        $post = $this->request->getParsedBody() ?? [];

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
            ->withStatus(201)
            ->withJson($webhook);
    }
}
