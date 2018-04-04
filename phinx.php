<?php
/**
 * @var \Psr\Container\ContainerInterface $container
 */

// We stall this script from running in order to let the environment finish spinning up.
sleep(5);

require __DIR__ . "/cli-bootstrap.php";

return [
    'environments' => [
        'default_database' => 'development',
        'development' => [
            'name' => $container->get('settings')['database']['db'],
            'connection' => $container->get('database')
        ]
    ],
    'paths' => [
        'migrations' => __DIR__ . '/migrations',
        'seeds' => __DIR__ . '/seeds'
    ]
];