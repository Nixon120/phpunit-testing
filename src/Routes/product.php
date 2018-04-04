<?php
use \Controllers\Product as Controllers;

$app->group('/api/product', function () use ($app) {
    $app->get('', function ($request, $response) {
        $product = new Controllers\JsonView($request, $response, $this->get('product'));
        return $product->list();
    });

    $app->get('/{sku}', function ($request, $response, $args) {
        $product = new Controllers\JsonView($request, $response, $this->get('product'));
        $productId = $args['sku'];
        return $product->single($productId);
    });
});

$app->group('/product', function () use ($app, $createRoute, $updateRoute) {
    $app->get('', function ($request, $response) {
        $product = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('product'));
        return $product->renderList();
    });

    $app->get('/list', function ($request, $response) {
        $product = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('product'));
        return $product->renderListResult();
    });

    $app->get('/view/{sku}', function ($request, $response, $args) {
        $product = new Controllers\GuiView($request, $response, $this->get('renderer'), $this->get('product'));
        $productId = $args['sku'];
        return $product->renderSingle($productId);
    });
});
