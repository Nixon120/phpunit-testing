<?php

use \Controllers\Program as Controllers;

$app->group('/api/program/type', function () use ($app) {
    $app->get('', Controllers\GetProgramTypeJsonCollection::class);
    $app->post('', Controllers\CreateProgramType::class);
    $app->delete('/{id}', Controllers\DeleteProgramType::class);
    $app->put('/{id}', Controllers\UpdateProgramType::class);
});



$app->group('/api/program', function () use ($app) {
    //@TODO : Groups execute on runtime it seems, so can't use this as intermediary
    //@TODO : should we use alternate syntax? just class and let it load via __invoke ?

    $app->get('', function ($request, $response) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        return $program->list();
    });

    $app->get('/{id}', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->single($programId);
    });

    $app->get('/{id}/user/adjustments', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->listCreditAdjustmentsByMeta($programId);
    });

    $app->get('/{id}/adjustments_count', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->getProgramAdjustmentsCount($programId);
    });

    $app->map(['post', 'get'], '/{id}/layout', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->layout($programId);
    });

    $app->post('/layout/clone', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        return $program->layoutClone();
    });

    $app->map(['post'], '/{id}/product/management/criteria', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->saveProductCriteria($programId);
    });

    $app->post('/product/management/criteria/clone', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        return $program->cloneProductCriteria();
    });

    $app->post('/{id}/product/management/featured', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->saveFeaturedProducts($programId);
    });

    $app->post('/{id}/autoredemption', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->autoRedemption($programId);
    });

    $app->map(['post', 'get'], '/{id}/offlineredemption', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->offlineRedemption($programId);
    });

    $app->delete('/{id}/layout/remove/{row_id}', function ($request, $response, $args) {
        $program = new Controllers\DeleteProgramLayout($request, $response, $this->get('renderer'), $this->get('program'));
        $programId = $args['id'];
        $rowId = $args['row_id'];
        return $program($programId, $rowId);
    });

    $app->get('/{id}/metrics', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->metrics($programId);
    });

    $app->map(['post', 'get'], '/{id}/sweepstake', function ($request, $response, $args) {
        $program = new Controllers\SweepstakeJsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->getSweepstakeConfig($programId);
    })->add(Services\Program\Sweepstake\ValidationMiddleware::class);

    $app->post('', function ($request, $response) {
        $program = new Controllers\Modify($request, $response, $this->get('program'));
        return $program->insert();
    });

    $app->put('/{id}', function ($request, $response, $args) {
        $program = new Controllers\Modify($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->update($programId);
    });

    $app->put('/{id}/publish/{publish}', function ($request, $response, $args) {
        $program = new Controllers\Publish($request, $response, $this->get('program'));
        $programId = $args['id'];
        $rowId = $args['publish'];

        return $program->updateProgramPublishSetting($programId, $rowId);
    });

    $app->put('/{id}/collectssn/{collectssn}', function ($request, $response, $args) {
        $program = new Controllers\CollectSsn($request, $response, $this->get('program'));
        $programId = $args['id'];
        $rowId = $args['collectssn'];

        return $program->updateProgramCollectSsnSetting($programId, $rowId);
    });
})->add(new \Middleware\ProgramModifiedCacheClearMiddleware($app->getContainer()));
