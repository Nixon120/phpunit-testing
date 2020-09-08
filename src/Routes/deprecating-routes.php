<?php

/** @deprecated */

use \Controllers\Participant as Controllers;
use Services\Authentication\Authenticate;

$updateRoute = function ($request, $response, $args) {
    $participant = new Controllers\Modify($request, $response, $this->get('participant'));
    $participantId = $args['id'];
    /** @var Authenticate $auth */
    $auth = $this->get('authentication');
    return $participant->update($participantId, $auth->getUser()->getEmailAddress());
};

$createRoute = function ($request, $response) {
    $participant = new Controllers\Modify($request, $response, $this->get('participant'));
    /** @var Authenticate $auth */
    $auth = $this->get('authentication');
    return $participant->insert($auth->getUser()->getEmailAddress());
};

$app->group('/api/user', function () use ($app, $createRoute, $updateRoute) {
    // Create
    $app->post('', $createRoute)->add(Services\Participant\ValidationMiddleware::class);

    // List
    $app->get('', function ($request, $response) {
        $participant = new Controllers\JsonView($request, $response, $this->get('participant'));
        return $participant->list();
    });

    // Fetch Single
    $app->get('/{id}', function ($request, $response, $args) {
        $participant = new Controllers\JsonView($request, $response, $this->get('participant'));
        $participantId = $args['id'];
        return $participant->single($participantId);
    });

    // Update
    $app->put('/{id}', $updateRoute)->add(Services\Participant\ValidationMiddleware::class);

    //@TODO: misc. routes that need to be duplicated to /participant
    $app->put('/{id}/meta', Controllers\UpdateMeta::class);
    $app->patch('/{id}/meta', Controllers\PatchMeta::class);

    $app->post('/{id}/sso', function ($request, $response, $args) {
        $participant = new Controllers\Sso($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $participant->generateSso($auth->getUser(), $uniqueId);
    })->add(\Middleware\ParticipantStatusValidator::class);

    $app->get('/{id}/sso', function ($request, $response, $args) {
        $participant = new Controllers\Sso($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $participant->authenticateSsoToken($auth->getUser()->getOrganizationId(), $uniqueId);
    });

    $app->get('/{id}/transaction', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        $year = $request->getParam('year');
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->transactionList($auth->getUser()->getOrganizationId(), $uniqueId, $year);
    });

    $app->get('/{id}/transaction/{transaction_id}', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        $transactionId = $args['transaction_id'];
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->single($auth->getUser()->getOrganizationId(), $uniqueId, $transactionId);
    });

    $app->patch('/{id}/transaction/{transaction_id}/meta', function ($request, $response, $args) {
        // Update Transaction Meta using Transaction ID OR Transaction Item GUID.
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->patchMeta($auth->getUser()->getOrganizationId(), $args['id'], $args['transaction_id']);
    });

    $app->map(['post','get'], '/{id}/transaction/{transaction_id}/return/{item_guid}', Controllers\TransactionReturn::class);

    $app->get('/{id}/transaction/{transaction_id}/{item_guid}', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        $itemGuid = $args['item_guid'];
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->singleItem($auth->getUser()->getOrganizationId(), $uniqueId, $itemGuid);
    });

    $app->put('/{id}/transaction/{item_guid}/reissue_date', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        $itemGuid = $args['item_guid'];
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->addReissueDate(
            $auth->getUser()->getOrganizationId(),
            $uniqueId,
            $itemGuid
        );
    });

    $app->post('/{id}/transaction', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');

        return $transaction->addTransaction($auth->getUser()->getOrganizationId(), $uniqueId);
    })->add(\Middleware\ParticipantProgramIsActiveValidator::class)
        ->add(\Middleware\ParticipantStatusValidator::class);

    $app->post('/{id}/customerservice_transaction', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');

        return $transaction->customerServiceTransaction($auth->getUser()->getOrganizationId(), $uniqueId);
    })->add(\Middleware\ParticipantProgramIsActiveValidator::class)
        ->add(\Middleware\ParticipantStatusValidator::class);

    $app->get('/{id}/adjustment', function ($request, $response, $args) {
        $balance = new Controllers\Balance($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $balance->list($auth->getUser()->getOrganizationId(), $uniqueId);
    });

    $app->post('/{id}/adjustment', function ($request, $response, $args) {
        $balance = new Controllers\Balance($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $balance->insert($auth->getUser()->getOrganizationId(), $uniqueId);
    })->add(\Middleware\ParticipantStatusValidator::class);

    $app->patch('/{id}/adjustment/{adjustment_id}', function ($request, $response, $args) {
        $balance = new Controllers\Balance($request, $response, $this->get('participant'));
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $balance->update($auth->getUser()->getOrganizationId(), $args['id'], $args['adjustment_id']);
    })->add(\Middleware\ParticipantStatusValidator::class);

    $app->post('/{id}/sweepstake', function ($request, $response, $args) {
        $sweepstake = new Controllers\SweepstakeEntry($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');

        return $sweepstake->create($auth->getUser()->getOrganizationId(), $uniqueId);
    });
});
$app->group('/api/participant', function () use ($app, $createRoute, $updateRoute) {
    // Create
    $app->post('', $createRoute)->add(Services\Participant\ValidationMiddleware::class);

    // List
    $app->get('', function ($request, $response) {
        $participant = new Controllers\JsonView($request, $response, $this->get('participant'));
        return $participant->list();
    });

    // Fetch Single
    $app->get('/{id}', function ($request, $response, $args) {
        $participant = new Controllers\JsonView($request, $response, $this->get('participant'));
        $participantId = $args['id'];
        return $participant->single($participantId);
    });

    // Update
    $app->put('/{id}', $updateRoute)->add(Services\Participant\ValidationMiddleware::class);

    $app->put('/{id}/meta', Controllers\UpdateMeta::class);
    $app->patch('/{id}/meta', Controllers\PatchMeta::class);

    $app->get('/{id}/adjustment', function ($request, $response, $args) {
        $balance = new Controllers\Balance($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $balance->list($auth->getUser()->getOrganizationId(), $uniqueId);
    });

    $app->patch('/{id}/adjustment/{adjustment_id}', function ($request, $response, $args) {
        $balance = new Controllers\Balance($request, $response, $this->get('participant'));
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $balance->update($auth->getUser()->getOrganizationId(), $args['id'], $args['adjustment_id']);
    })->add(\Middleware\ParticipantStatusValidator::class);

    $app->get('/{id}/transaction/{transaction_id}', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->single($auth->getUser()->getOrganizationId(), $args['id'], $args['transaction_id']);
    });

    $app->patch('/{id}/transaction/{transaction_id}/meta', function ($request, $response, $args) {
        // Update Transaction Meta using Transaction ID OR Transaction Item GUID.
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->patchMeta($auth->getUser()->getOrganizationId(), $args['id'], $args['transaction_id']);
    });

    $app->map(['post','get'], '/{id}/transaction/{transaction_id}/return/{item_guid}', Controllers\TransactionReturn::class);

    $app->put('/{id}/transaction/{item_guid}/reissue_date', function ($request, $response, $args) {
        $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
        $uniqueId = $args['id'];
        $itemGuid = $args['item_guid'];
        /** @var Authenticate $auth */
        $auth = $this->get('authentication');
        return $transaction->addReissueDate(
            $auth->getUser()->getOrganizationId(),
            $uniqueId,
            $itemGuid
        );
    });
});
