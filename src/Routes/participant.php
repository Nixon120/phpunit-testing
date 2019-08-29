<?php

use \Controllers\Participant as Controllers;

$updateRoute = function ($request, $response, $args) {
    $participant = new Controllers\Modify($request, $response, $this->get('participant'));
    $participantId = $args['id'];
    return $participant->update($participantId);
};

$createRoute = function ($request, $response) {
    $participant = new Controllers\Modify($request, $response, $this->get('participant'));
    return $participant->insert();
};

$app->group('/api/user', function () use ($app, $createRoute, $updateRoute) {
    $app->get('', function ($request, $response) {
        $participant = new Controllers\JsonView($request, $response, $this->get('participant'));
        return $participant->list();
    });

    $app->get('/{id}', function ($request, $response, $args) {
        $participant = new Controllers\JsonView($request, $response, $this->get('participant'));
        $participantId = $args['id'];
        return $participant->single($participantId);
    });

    $app->post('', $createRoute)->add(Services\Participant\ValidationMiddleware::class);

    $app->put('/{id}', $updateRoute)->add(Services\Participant\ValidationMiddleware::class);

    $app->put('/{id}/meta', Controllers\SaveMeta::class);
    $app->patch('/{id}/meta', Controllers\UpdateMeta::class);

    $app->post('/{id}/sso', function ($request, $response, $args) {
        $participant = new Controllers\Sso($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');
        return $participant->generateSso($auth->getUser()->getOrganizationId(), $uniqueId);
    })->add(\Middleware\ParticipantStatusValidator::class);

    $app->get('/{id}/sso', function ($request, $response, $args) {
        $participant = new Controllers\Sso($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');
        return $participant->authenticateSsoToken($auth->getUser()->getOrganizationId(), $uniqueId);
    });

    $app->get('/{id}/transaction', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->transactionList($auth->getUser()->getOrganizationId(), $uniqueId);
    });

    $app->get('/{id}/transaction/{transaction_id}', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        $transactionId = $args['transaction_id'];
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->single($auth->getUser()->getOrganizationId(), $uniqueId, $transactionId);
    });

    $app->patch('/{id}/transaction/{transaction_id}/meta', function ($request, $response, $args) {
        // Update Transaction Meta using Transaction ID OR Transaction Item GUID.
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->updateMeta($auth->getUser()->getOrganizationId(), $args['id'], $args['transaction_id']);
    });

    $app->get('/{id}/transaction/{transaction_id}/{item_guid}', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        $itemGuid = $args['item_guid'];
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->singleItem($auth->getUser()->getOrganizationId(), $uniqueId, $itemGuid);
    });

    $app->put('/{id}/transaction/{transaction_id}/{item_guid}/reissue_date', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        $itemGuid = $args['item_guid'];
        $transactionId = $args['transaction_id'];
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->addReissueDate($auth->getUser()->getOrganizationId(), $uniqueId, $transactionId, $itemGuid);
    });

    $app->post('/{id}/transaction', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');

        return $transaction->addTransaction($auth->getUser()->getOrganizationId(), $uniqueId);
    })->add(\Middleware\ParticipantStatusValidator::class);

    $app->post('/{id}/customerservice_transaction', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');

        return $transaction->customerServiceTransaction($auth->getUser()->getOrganizationId(), $uniqueId);
    })->add(\Middleware\ParticipantStatusValidator::class);

    $app->get('/{id}/adjustment', function ($request, $response, $args) {
        $balance = new Controllers\Balance($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');
        return $balance->list($auth->getUser()->getOrganizationId(), $uniqueId);
    });

    $app->post('/{id}/adjustment', function ($request, $response, $args) {
        $balance = new Controllers\Balance($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');
        return $balance->insert($auth->getUser()->getOrganizationId(), $uniqueId);
    })->add(\Middleware\ParticipantStatusValidator::class);

    $app->patch('/{id}/adjustment/{adjustment_id}', function ($request, $response, $args) {
        $balance = new Controllers\Balance($request, $response, $this->get('participant'));
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');
        return $balance->update($auth->getUser()->getOrganizationId(), $args['id'], $args['adjustment_id']);
    })->add(\Middleware\ParticipantStatusValidator::class);

    $app->post('/{id}/sweepstake', function ($request, $response, $args) {
        $sweepstake = new Controllers\SweepstakeEntry($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');

        return $sweepstake->create($auth->getUser()->getOrganizationId(), $uniqueId);
    });
});


$app->group('/participant', function () use ($app, $createRoute, $updateRoute) {
    $app->get('', function ($request, $response) {
        $participant = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('participant'));
        return $participant->renderList();
    });

    $app->get('/list', function ($request, $response) {
        $participant = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('participant'));
        return $participant->renderListResult();
    });

    $this->get('/organization/list', function ($request, $response) {
        $organization = new \Controllers\Participant\Organization(
            $request,
            $response,
            $this->get('participant')
        );
        return $organization->renderListResult();
    });

    $this->get('/program/list', function ($request, $response) {
        $organization = new \Controllers\Report\Program(
            $request,
            $response,
            $this->get('report')
        );
        return $organization->renderListResult();
    });

    $app->get('/{id}/adjustment', function ($request, $response, $args) {
        $participant = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('participant'));
        $participantId = $args['id'];
        return $participant->renderAdjustmentList($participantId);
    });

    $app->get('/{id}/transaction/{transaction_id}', function ($request, $response, $args) {
        $participant = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('participant'));
        $participantId = $args['id'];
        $transactionId = $args['transaction_id'];
        return $participant->renderTransaction($participantId, $transactionId);
    });

    $app->get('/{id}/view', function ($request, $response, $args) {
        $participant = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('participant'));
        $participantId = $args['id'];
        return $participant->renderSingle($participantId);
    });

    $app->post('/{id}/view', $updateRoute);

    $app->get('/create', function ($request, $response) {
        $participant = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('participant'));
        return $participant->renderCreatePage();
    });

    $app->post('/create', $createRoute);
})->add(Services\Participant\ValidationMiddleware::class);;


$app->group('/api/participant', function () use ($app, $createRoute, $updateRoute) {
    $app->get('', function ($request, $response) {
        $participant = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('participant'));
        return $participant->renderList();
    });

    $app->get('/list', function ($request, $response) {
        $participant = new Controllers\JsonView($request, $response, $this->get('participant'));
        return $participant->list();
    });

    $app->get('/{id}/view', function ($request, $response, $args) {
        $participant = new Controllers\JsonView($request, $response, $this->get('participant'));
        $participantId = $args['id'];
        return $participant->single($participantId);
    });

    $app->post('/{id}/view', $updateRoute);

    $app->post('/create', $createRoute);

    $app->put('/{id}/meta', Controllers\SaveMeta::class);
    $app->patch('/{id}/meta', Controllers\UpdateMeta::class);

    $app->get('/{id}/adjustment', function ($request, $response, $args) {
        $participant = new Controllers\JsonView($request, $response, $this->get('participant'));
        $participantId = $args['id'];
        return $participant->adjustmentList($participantId);
    });

    $app->patch('/{id}/adjustment/{adjustment_id}', function ($request, $response, $args) {
        $balance = new Controllers\Balance($request, $response, $this->get('participant'));
        /** @var \Services\Authentication\Authenticate $auth */
        $auth = $this->get('authentication');
        return $balance->update($auth->getUser()->getOrganizationId(), $args['id'], $args['adjustment_id']);
    })->add(\Middleware\ParticipantStatusValidator::class);

    $app->get('/{id}/transaction/{transaction_id}', function ($request, $response, $args) {
        $participant = new Controllers\JsonView($request, $response, $this->get('participant'));
        $participantId = $args['id'];
        $transactionId = $args['transaction_id'];
        return $participant->transaction($participantId, $transactionId);
    });
})->add(Services\Participant\ValidationMiddleware::class);
