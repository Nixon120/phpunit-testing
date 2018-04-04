<?php

use \Controllers\Organization as OrgControllers;
use \Controllers\Webhook as WebhookControllers;

//@TODO should I change these into factories, so less bloat in actual routes ?
//@TODO convert these into invokable classes?
$createRoute = function ($request, $response) {
    $organization = new OrgControllers\Modify($request, $response, $this->get('organization'));
    return $organization->insert();
};

$updateRoute = function ($request, $response, $args) {
    $organization = new OrgControllers\Modify($request, $response, $this->get('organization'));
    $organizationId = $args['id'];
    return $organization->update($organizationId);
};

$app->group('/api/organization', function () use ($createRoute, $updateRoute) {
    //@TODO : Groups execute on runtime it seems, so can't use this as intermediary
    //@TODO : should we use alternate syntax? just class and let it load via __invoke ?
    $this->get('', function ($request, $response) {
        $organization = new OrgControllers\JsonView($request, $response, $this->get('organization'));
        return $organization->list();
    });

    $this->get('/{id}', function ($request, $response, $args) {
        $organization = new OrgControllers\JsonView($request, $response, $this->get('organization'));
        $organizationId = $args['id'];
        return $organization->single($organizationId);
    });

    $this->get('/{id}/domain', function ($request, $response, $args) {
        $organization = new OrgControllers\JsonView($request, $response, $this->get('organization'));
        $organizationId = $args['id'];
        return $organization->domainList($organizationId);
    });

    $this->post('', $createRoute);

    $this->put('/{id}', $updateRoute);


    $this->group('/{id}', function () {
        // ROUTE GROUP: /organization/{id}

        $this->group('/webhooks', function () {

            /** @var \Services\Authentication\Authenticate $auth */
            $container = $this->getContainer();

            // ROUTE GROUP: /organization/{id}/webhooks

            $this->get('', function ($request, $response, $args) {
                // List Webhooks
                // ROUTE: /organization/{id}/webhooks

                $controller = new WebhookControllers\JsonView(
                    $request,
                    $response,
                    $this->get('renderer'),
                    $this->get('organization')
                );

                $organizationId = $args['id'];

                return $controller->renderList($organizationId);
            });

            $this->post('', function ($request, $response, $args) {
                // ROUTE: /organization/{id}/webhooks
                $controller = new WebhookControllers\JsonView(
                    $request,
                    $response,
                    $this->get('renderer'),
                    $this->get('organization')
                );
                $organizationId = $args['id'];

                return $controller->insertWebhook($organizationId);
            });

            $this->group('/{webhook_id}', function () {
                $this->get('', function (
                    \Psr\Http\Message\RequestInterface $request,
                    \Psr\Http\Message\ResponseInterface $response,
                    $args
                ) {
                    // View Single Webhooks
                    // ROUTE: /organization/{id}/webhooks/{webhook_id}

                    // JSON Output
                    $controller = new WebhookControllers\JsonView(
                        $request,
                        $response,
                        $this->get('renderer'),
                        $this->get('organization')
                    );

                    $organizationId = $args['id'];
                    $webhookId = $args['webhook_id'];

                    return $controller->viewWebhook($organizationId, $webhookId);
                });

                $this->get('/log/{webhook_log_id}', function ($request, $response, $args) {
                    $controller = new WebhookControllers\JsonView(
                        $request,
                        $response,
                        $this->get('renderer'),
                        $this->get('organization')
                    );

                    return $controller->viewWebhookLog(
                        $args['id'],
                        $args['webhook_id'],
                        $args['webhook_log_id']
                    );
                });

                $this->post('/log/{webhook_log_id}/replay', function ($request, $response, $args) {
                    $controller = new WebhookControllers\JsonView(
                        $request,
                        $response,
                        $this->get('renderer'),
                        $this->get('organization')
                    );

                    return $controller->replayWebhookLog(
                        $args['id'],
                        $args['webhook_id'],
                        $args['webhook_log_id']
                    );
                });
            });
        });
    });
})->add(Services\Organization\ValidationMiddleware::class);

$app->group('/organization', function () use ($createRoute, $updateRoute) {
    $this->get('', function ($request, $response) {
        $organization = new OrgControllers\GuiView(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('organization')
        );
        return $organization->renderList();
    });

    $this->get('/list', function ($request, $response) {
        $organization = new OrgControllers\GuiView(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('organization')
        );
        return $organization->renderListResult();
    });

    $this->get('/view/{id}', function ($request, $response, $args) {
        $organization = new OrgControllers\GuiView(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('organization')
        );
        $organizationId = $args['id'];
        return $organization->renderSingle($organizationId);
    });

    $this->post('/view/{id}', $updateRoute);

    $this->get('/create', function ($request, $response) {
        $organization = new OrgControllers\GuiView(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('organization')
        );
        return $organization->renderCreatePage();
    });

    $this->post('/create', $createRoute);

    $this->get('/domain/list', function ($request, $response) {
        $organization = new OrgControllers\JsonView($request, $response, $this->get('organization'));
        return $organization->domainList();
    });

    $this->delete('/domain/delete/{id}', function ($request, $response, $args) {
        $organization = new OrgControllers\Modify($request, $response, $this->get('organization'));
        $domainId = $args['id'];
        return $organization->deleteDomain($domainId);
    });

    $this->group('/{id}', function () {
        // ROUTE GROUP: /organization/{id}

        $this->group('/webhooks', function () {
            // ROUTE GROUP: /organization/{id}/webhooks

            $this->get('', function ($request, $response, $args) {
                // List Webhooks
                // ROUTE: /organization/{id}/webhooks

                $controller = new WebhookControllers\GuiView(
                    $request,
                    $response,
                    $this->get('renderer'),
                    $this->get('organization')
                );
                $organizationId = $args['id'];

                return $controller->renderList($organizationId);
            });

            $this->post('', function ($request, $response, $args) {
                // ROUTE: /organization/{id}/webhooks

                $controller = new WebhookControllers\GuiView(
                    $request,
                    $response,
                    $this->get('renderer'),
                    $this->get('organization')
                );
                $organizationId = $args['id'];

                return $controller->insertWebhook($organizationId);
            });

            $this->group('/{webhook_id}', function () {
                $this->get('', function (
                    \Psr\Http\Message\RequestInterface $request,
                    \Psr\Http\Message\ResponseInterface $response,
                    $args
                ) {
                    // View Single Webhooks
                    // ROUTE: /organization/{id}/webhooks/{webhook_id}

                    if ($request->hasHeader('accept')
                        && $request->getHeaderLine('accept') == 'application/json') {
                        // JSON Output
                        $controller = new WebhookControllers\JsonView(
                            $request,
                            $response,
                            $this->get('renderer'),
                            $this->get('organization')
                        );
                    } else {
                        // HTML Output
                        $controller = new WebhookControllers\GuiView(
                            $request,
                            $response,
                            $this->get('renderer'),
                            $this->get('organization')
                        );
                    }

                    $organizationId = $args['id'];
                    $webhookId = $args['webhook_id'];

                    return $controller->viewWebhook($organizationId, $webhookId);
                });

                $this->get('/log/{webhook_log_id}', function ($request, $response, $args) {

                    $controller = new WebhookControllers\GuiView(
                        $request,
                        $response,
                        $this->get('renderer'),
                        $this->get('organization')
                    );

                    return $controller->viewWebhookLog(
                        $args['id'],
                        $args['webhook_id'],
                        $args['webhook_log_id']
                    );
                });

                $this->post('/log/{webhook_log_id}/replay', function ($request, $response, $args) {

                    $controller = new WebhookControllers\JsonView(
                        $request,
                        $response,
                        $this->get('renderer'),
                        $this->get('organization')
                    );

                    return $controller->replayWebhookLog(
                        $args['id'],
                        $args['webhook_id'],
                        $args['webhook_log_id']
                    );
                });

                $this->post('/delete', function ($request, $response, $args) {

                    $controller = new WebhookControllers\GuiView(
                        $request,
                        $response,
                        $this->get('renderer'),
                        $this->get('organization')
                    );

                    $organizationId = $args['id'];
                    $webhookId = $args['webhook_id'];

                    return $controller->deleteWebhook($organizationId, $webhookId);
                });

                $this->get('/webhook-view', function ($request, $response, $args) {

                    $controller = new WebhookControllers\GuiView(
                        $request,
                        $response,
                        $this->get('renderer'),
                        $this->get('organization')
                    );

                    $organizationId = $args['id'];
                    $webhookId = $args['webhook_id'];

                    return $controller->webhookModalView($organizationId, $webhookId);
                });

                $this->post('/modify', function ($request, $response, $args) {

                    $controller = new WebhookControllers\GuiView(
                        $request,
                        $response,
                        $this->get('renderer'),
                        $this->get('organization')
                    );

                    $webhookId = $args['webhook_id'];

                    return $controller->modifyWebhook($webhookId);
                });
            });
        });
    });
})->add(Services\Organization\ValidationMiddleware::class);
