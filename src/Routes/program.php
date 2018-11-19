<?php

use \Controllers\Program as Controllers;

$updateRoute = function ($request, $response, $args) {
    $program = new Controllers\Modify($request, $response, $this->get('program'));
    $programId = $args['id'];
    return $program->update($programId);
};

$createRoute = function ($request, $response) {
    $program = new Controllers\Modify($request, $response, $this->get('program'));
    return $program->insert();
};

$app->group('/api/program', function () use ($app, $createRoute, $updateRoute) {
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

    $app->group('/{id}/user', function () use ($app, $createRoute, $updateRoute) {
        $app->get('', function ($request, $response, $args) {
            $program = new Controllers\JsonView($request, $response, $this->get('program'));
            $programId = $args['id'];
            return $program->listUsers($programId);
        });
        $app->get('/adjustments', function ($request, $response, $args) {
            $program = new Controllers\JsonView($request, $response, $this->get('program'));
            return $program->listCreditAdjustmentsByParticipant();
        });
    });

    $app->map(['post', 'get'], '/{id}/layout', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->layout($programId);
    })->add(new \Middleware\ProgramModifiedCacheClearMiddleware($app->getContainer()));

    $app->map(['post', 'get'], '/{id}/faqs', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->faqs($programId);
    })->add(new \Middleware\ProgramModifiedCacheClearMiddleware($app->getContainer()));

    $app->delete('/{id}/layout/remove/{row_id}', function ($request, $response, $args) {
        $program = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('program'));
        $programId = $args['id'];
        $rowId = $args['row_id'];
        return $program->deleteProgramLayoutRow($programId, $rowId);
    })->add(new \Middleware\ProgramModifiedCacheClearMiddleware($app->getContainer()));

    $app->get('/{id}/metrics', function ($request, $response, $args) {
        $program = new Controllers\JsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->metrics($programId);
    });

    //ensure this considers program
    $this->get('/category/list', function ($request, $response) {
        $organization = new Controllers\Product(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('program')
        );
        return $organization->renderCategoryList();
    });

    //ensure this considers program
    $this->get('/brand/list', function ($request, $response) {
        $organization = new Controllers\Product(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('program')
        );
        return $organization->renderBrandList();
    });

    $this->get('/product/list', function ($request, $response) {
        $organization = new Controllers\Product(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('program')
        );
        return $organization->renderProductList();
    });

    $app->map(['post'], '/{id}/product/management/criteria', function ($request, $response, $args) {
        $program = new Controllers\Product($request, $response, $this->get('renderer'), $this->get('program'));
        $programId = $args['id'];
        return $program->saveProductCriteria($programId);
    })->add(new \Middleware\ProgramModifiedCacheClearMiddleware($app->getContainer()));

    $app->map(['post'], '/{id}/product/management/featured', function ($request, $response, $args) {
        $program = new Controllers\Product($request, $response, $this->get('renderer'), $this->get('program'));
        $programId = $args['id'];
        return $program->saveFeaturedProducts($programId);
    })->add(new \Middleware\ProgramModifiedCacheClearMiddleware($app->getContainer()));

    $app->map(['post', 'get'], '/{id}/sweepstake', function ($request, $response, $args) {
        $program = new Controllers\SweepstakeJsonView($request, $response, $this->get('program'));
        $programId = $args['id'];
        return $program->getSweepstakeConfig($programId);
    })->add(Services\Program\Sweepstake\ValidationMiddleware::class);

    $app->post('', $createRoute);

    $app->put('/{id}', $updateRoute);

    $app->map(['post'], '/{id}/publish/{publish}', function ($request, $response, $args) {
        $program = new Controllers\Publish($request, $response, $this->get('program'));
        $programId = $args['id'];
        $rowId = $args['publish'];

        return $program->updateProgramPublishSetting($programId, $rowId);
    })->add(new \Middleware\ProgramModifiedCacheClearMiddleware($app->getContainer()));
});

$app->group('/program', function () use ($app, $createRoute, $updateRoute) {
    $app->get('', function ($request, $response) {
        $program = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('program'));
        return $program->renderList();
    });

    $app->get('/list', function ($request, $response) {
        $program = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('program'));
        return $program->renderListResult();
    });

    $app->get('/view/{id}', function ($request, $response, $args) {
        $program = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('program'));
        $programId = $args['id'];
        return $program->renderSingle($programId);
    });

    $app->post('/view/{id}', $updateRoute);

    $app->get('/create', function ($request, $response) {
        $program = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('program'));
        return $program->renderCreatePage();
    });

    $app->post('/create', $createRoute);

    //ensure this considers program
    $this->get('/product/list', function ($request, $response) {
        $organization = new Controllers\Product(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('program')
        );
        return $organization->renderProductList();
    });

    //ensure this considers program
    $this->get('/category/list', function ($request, $response) {
        $organization = new Controllers\Product(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('program')
        );
        return $organization->renderCategoryList();
    });

    //ensure this considers program
    $this->get('/brand/list', function ($request, $response) {
        $organization = new Controllers\Product(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('program')
        );
        return $organization->renderBrandList();
    });

    $app->map(['get'], '/{id}/product/management', function ($request, $response, $args) {
        $program = new Controllers\Product($request, $response, $this->get('renderer'), $this->get('program'));
        $programId = $args['id'];
        return $program->renderProductManagement($programId);
    });

    $app->map(['post'], '/{id}/product/management/criteria', function ($request, $response, $args) {
        $program = new Controllers\Product($request, $response, $this->get('renderer'), $this->get('program'));
        $programId = $args['id'];
        return $program->saveProductCriteria($programId);
    });

    $app->map(['post'], '/{id}/product/management/featured', function ($request, $response, $args) {
        $program = new Controllers\Product($request, $response, $this->get('renderer'), $this->get('program'));
        $programId = $args['id'];
        return $program->saveFeaturedProducts($programId);
    });

    $app->map(['post', 'get'], '/{id}/layout', function ($request, $response, $args) {
        $program = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('program'));
        $programId = $args['id'];
        return $program->renderProgramLayout($programId);
    });

    $app->map(['post', 'get'], '/{id}/sweepstake', function ($request, $response, $args) {
        $program = new Controllers\Sweepstake($request, $response, $this->get('renderer'), $this->get('program'));
        $programId = $args['id'];
        return $program->renderSweepstakeConfig($programId);
    });

    $app->map(['get'], '/{id}/layout/remove/{row_id}', function ($request, $response, $args) {
        $program = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('program'));
        $programId = $args['id'];
        $rowId = $args['row_id'];
        return $program->deleteProgramLayoutRow($programId, $rowId);
    });

    $app->map(['post'], '/{id}/publish/{publish}', function ($request, $response, $args) {
        $program = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('program'));
        $programId = $args['id'];
        $rowId = $args['publish'];

        return $program->updateProgramPublishSetting($programId, $rowId);
    });
});
