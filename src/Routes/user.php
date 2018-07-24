<?php

use \Controllers\User as Controllers;
use \Controllers\Authentication as AuthControllers;
use Dflydev\FigCookies\FigResponseCookies;

$updateRoute = function ($request, $response, $args) {
    $user = new Controllers\Modify($request, $response, $this->get('user'));
    $userId = $args['id'];
    return $user->update($userId);
};

$createRoute = function ($request, $response) {
    $user = new Controllers\Modify($request, $response, $this->get('user'));
    return $user->insert();
};

$app->group('/user', function () use ($app, $createRoute, $updateRoute) {
    $app->get('', function ($request, $response) {
        $user = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('user'));
        return $user->renderList();
    });

    $app->get('/list', function ($request, $response) {
        $user = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('user'));
        return $user->renderListResult();
    });

    $app->get('/view/{id}', function ($request, $response, $args) {
        $user = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('user'));
        $userId = $args['id'];
        return $user->renderSingle($userId);
    });

    $app->post('/view/{id}', $updateRoute);

    $app->get('/create', function ($request, $response) {
        $user = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('user'));
        return $user->renderCreatePage();
    });

    $app->post('/create', $createRoute);

    $app->group('/import', function () {
        $this->get('/upload', function ($request, $response) {
            $userImport = new Controllers\UserImport(
                $request,
                $response,
                $this->get('renderer'),
                $this->get('user')
            );
            return $userImport->renderUploadForm();
        });

        $this->post('/audit', function ($request, $response) {
            $userImport = new Controllers\UserImport(
                $request,
                $response,
                $this->get('renderer'),
                $this->get('user')
            );
            return $userImport->renderAuditUploadForm();
        });

        $this->post('/import', function ($request, $response) {
            $userImport = new Controllers\UserImport(
                $request,
                $response,
                $this->get('renderer'),
                $this->get('user')
            );

            return $userImport->importUsers();
        });
    });

    $app->get('/recovery', function ($request, $response, $args) {
        // Shows the email input field for initial step of password recovery
        $controller = new Controllers\UserRecovery(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('user')
        );

        return $controller->renderRecoveryForm();
    });

    $app->post('/recovery', function ($request, $response, $args) {
        // Takes email from step 1 and dispatches a generated email to enduser with instructions
        $controller = new Controllers\UserRecovery(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('user')
        );

        return $controller->submitRecoveryForm();
    });

    $app->get('/recovery/{token}', function ($request, $response, $args) {
        // Shows the password recovery fields
        $controller = new Controllers\UserRecovery(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('user')
        );

        return $controller->renderPasswordRecoveryForm($args['token']);
    });

    $app->post('/recovery/{token}', function ($request, $response, $args) {
        // Updates password based on supplied input
        $controller = new Controllers\UserRecovery(
            $request,
            $response,
            $this->get('renderer'),
            $this->get('user')
        );

        return $controller->submitRecoveryPassword($args['token']);
    });
});


$app->group('/api/administrators', function () use ($app, $createRoute, $updateRoute) {

    $app->get('/list', function ($request, $response) {
        $user = new Controllers\JsonView($request, $response, $this->get('user'));
        return $user->listResult();
    });

    $app->put('/{id}', $updateRoute);

    $app->post('', $createRoute);

    $app->group('/import', function () {
        $this->post('/audit', function ($request, $response) {
            $userImport = new Controllers\JsonView(
                $request,
                $response,
                $this->get('user')
            );
            return $userImport->auditUploadFormData();
        });

        $this->post('/import', function ($request, $response) {
            $userImport = new Controllers\JsonView(
                $request,
                $response,
                $this->get('user')
            );

            return $userImport->importUsers();
        });
    });

    $app->post('/recovery', function ($request, $response, $args) {
        // Takes email from step 1 and dispatches a generated email to enduser with instructions
        $controller = new Controllers\JsonView(
            $request,
            $response,
            $this->get('user')
        );

        if($request->getParam('token')){
            return $controller->submitRecoveryPassword($request->getParam('token'));
        }

        return $controller->submitRecoveryForm();
    });
});

$app->post('/user/login', function ($request, $response, $args) {
    $auth = new AuthControllers\ApiLogin(
        $this->get('authentication'),
        $this->get('roles'),
        $this->get('defaultRoutes')
    );

    return $auth($request, $response, $args);
});

$app->map(['post', 'get'], '/login', AuthControllers\Login::class);
$app->get('/logout', function ($request, $response, $args) use ($app) {
    $response = FigResponseCookies::expire($response, 'token');
    $response = FigResponseCookies::expire($response, 'token_expires');
    return $response->withRedirect('/login', 200);
});

$app->get('/invite', function ($request, $response, $args) {
    // Show the user invite registration form.
    $inviteController = new Controllers\UserInvite(
        $request,
        $response,
        $this->get('renderer'),
        $this->get('user')
    );

    return $inviteController->renderInviteForm();
});

$app->post('/invite', function ($request, $response, $args) {
    // Process the submitted user invite form.
    // Show the user invite registration form.
    $inviteController = new Controllers\UserInvite(
        $request,
        $response,
        $this->get('renderer'),
        $this->get('user')
    );

    return $inviteController->registerUser();
});
