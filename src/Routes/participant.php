<?php

use Controllers\Participant as Controllers;
use Middleware\ParticipantStatusDeleteValidator;
use Middleware\UserAccessValidator;
use Services\Authentication\Authenticate;

/** @var Slim\App $app */

$app->group(
    '/api/program/{programUuid}/participant',
    function () use ($app) {

        /** @var Authenticate $auth */
        $auth = $app->getContainer()->get('authentication');
        // Create
        $app->post(
            '',
            function ($request, $response) use ($auth) {
                $participant = new Controllers\Modify($request, $response, $this->get('participant'));
                return $participant->insert($auth->getUser());
            }
        )->add(Services\Participant\ValidationMiddleware::class)
            ->add(ParticipantStatusDeleteValidator::class)
            ->add(UserAccessValidator::class);

        // List
        $app->get(
            '',
            function ($request, $response) use ($auth) {
                $participant = new Controllers\JsonView($request, $response, $this->get('participant'));
                return $participant->list($auth->getUser()->getAccessLevel());
            }
        );

        // Fetch Single
        $app->get(
            '/{id}',
            function ($request, $response, $args) use ($auth) {
                $participant = new Controllers\JsonView($request, $response, $this->get('participant'));
                $participantId = $args['id'];
                return $participant->single($participantId, $auth->getUser()->getAccessLevel());
            }
        );

        // Delete Single
        $app->delete(
            '/{id}',
            function ($request, $response, $args) use ($auth) {
                $participant = new Controllers\Modify($request, $response, $this->get('participant'));
                $participantId = $args['id'];
                return $participant->removeParticipantPii($participantId, $auth->getUser());
            }
        );

        // Update
        $app->put(
            '/{id}',
            function ($request, $response, $args) use ($auth) {
                $participant = new Controllers\Modify($request, $response, $this->get('participant'));
                $participantId = $args['id'];
                return $participant->update($participantId, $auth->getUser());
            }
        )->add(Services\Participant\ValidationMiddleware::class)
            ->add(ParticipantStatusDeleteValidator::class)
            ->add(UserAccessValidator::class);

        $app->put('/{id}/meta', Controllers\UpdateMeta::class);

        $app->patch('/{id}/meta', Controllers\PatchMeta::class);

        $app->post(
            '/{id}/sso',
            function ($request, $response, $args) use ($auth) {
                $participant = new Controllers\Sso($request, $response, $this->get('participant'));
                $uniqueId = $args['id'];
                return $participant->generateSso($auth->getUser(), $uniqueId);
            }
        )->add(Middleware\ParticipantStatusValidator::class)
            ->add(UserAccessValidator::class);

        $app->get(
            '/{id}/sso',
            function ($request, $response, $args) use ($auth) {
                $participant = new Controllers\Sso($request, $response, $this->get('participant'));
                $uniqueId = $args['id'];
                return $participant->authenticateSsoToken($auth->getUser()->getOrganizationId(), $uniqueId);
            }
        );

        $app->get(
            '/{id}/transaction',
            function ($request, $response, $args) use ($auth) {
                $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
                $uniqueId = $args['id'];
                $year = $request->getParam('year');
                return $transaction->transactionList(
                    $auth->getUser()->getOrganizationId(),
                    $uniqueId,
                    $auth->getUser()->getAccessLevel(),
                    $year
                );
            }
        );

        $app->get(
            '/{id}/transaction/{transaction_id}',
            function ($request, $response, $args) use ($auth) {
                $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
                $uniqueId = $args['id'];
                $transactionId = $args['transaction_id'];
                return $transaction->single(
                    $auth->getUser()->getOrganizationId(),
                    $uniqueId,
                    $transactionId,
                    $auth->getUser()->getAccessLevel()
                );
            }
        );

        $app->patch(
            '/{id}/transaction/{transaction_id}/meta',
            function ($request, $response, $args) use ($auth) {
                // Update Transaction Meta using Transaction ID OR Transaction Item GUID.
                $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
                return $transaction->patchMeta(
                    $auth->getUser()->getOrganizationId(),
                    $args['id'],
                    $args['transaction_id']
                );
            }
        );

        $app->map(
            ['post', 'get'],
            '/{id}/transaction/{transaction_id}/return/{item_guid}',
            Controllers\TransactionReturn::class
        );

        $app->get(
            '/{id}/transaction/{transaction_id}/{item_guid}',
            function ($request, $response, $args) use ($auth) {
                $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
                $uniqueId = $args['id'];
                $itemGuid = $args['item_guid'];
                return $transaction->singleItem(
                    $auth->getUser()->getOrganizationId(),
                    $uniqueId,
                    $itemGuid,
                    $auth->getUser()->getAccessLevel()
                );
            }
        );

        $app->put(
            '/{id}/transaction/{item_guid}/reissue_date',
            function ($request, $response, $args) use ($auth) {
                $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
                $uniqueId = $args['id'];
                $itemGuid = $args['item_guid'];
                return $transaction->addReissueDate(
                    $auth->getUser()->getOrganizationId(),
                    $uniqueId,
                    $itemGuid
                );
            }
        )->add(Middleware\ParticipantStatusValidator::class);

        $app->post(
            '/{id}/transaction',
            function ($request, $response, $args) use ($auth) {
                $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
                $uniqueId = $args['id'];
                return $transaction->addTransaction(
                    $auth->getUser()->getOrganizationId(),
                    $uniqueId,
                    $auth->getUser()->getAccessLevel()
                );
            }
        )
            ->add(Middleware\ParticipantProgramIsActiveValidator::class)
            ->add(Middleware\ParticipantStatusValidator::class);

        $app->post(
            '/{id}/customerservice_transaction',
            function ($request, $response, $args) use ($auth) {
                $transaction = new Controllers\Transaction($request, $response, $this->get('participant'));
                $uniqueId = $args['id'];
                return $transaction->customerServiceTransaction(
                    $auth->getUser()->getOrganizationId(),
                    $uniqueId,
                    $auth->getUser()->getAccessLevel()
                );
            }
        )
            ->add(Middleware\ParticipantProgramIsActiveValidator::class)
            ->add(Middleware\ParticipantStatusValidator::class);

        $app->get(
            '/{id}/adjustment',
            function ($request, $response, $args) use ($auth) {
                $balance = new Controllers\Balance($request, $response, $this->get('participant'));
                $uniqueId = $args['id'];
                return $balance->list($auth->getUser()->getOrganizationId(), $uniqueId);
            }
        );

        $app->post(
            '/{id}/adjustment',
            function ($request, $response, $args) use ($auth) {
                $balance = new Controllers\Balance($request, $response, $this->get('participant'));
                $uniqueId = $args['id'];
                return $balance->insert($auth->getUser()->getOrganizationId(), $uniqueId, $auth->getUser()->getAccessLevel());
            }
        )->add(Middleware\ParticipantStatusValidator::class);

        $app->patch(
            '/{id}/adjustment/{adjustment_id}',
            function ($request, $response, $args) use ($auth) {
                $balance = new Controllers\Balance($request, $response, $this->get('participant'));
                return $balance->update($auth->getUser()->getOrganizationId(), $args['id'], $args['adjustment_id']);
            }
        )->add(Middleware\ParticipantStatusValidator::class);

        $app->post(
            '/{id}/sweepstake',
            function ($request, $response, $args) use ($auth) {
                $sweepstake = new Controllers\SweepstakeEntry($request, $response, $this->get('participant'));
                $uniqueId = $args['id'];
                return $sweepstake->create($auth->getUser()->getOrganizationId(), $uniqueId);
            }
        ) ->add(Middleware\ParticipantStatusValidator::class);
    }
)->add(Middleware\ProgramValidator::class);
