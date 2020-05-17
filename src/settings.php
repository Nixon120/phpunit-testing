<?php

return [
    'settings' => [
        'baseUrl' => ((!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ? 'https' : 'http')
            . '://'
            . (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'),
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],
        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::INFO,
        ],
        'database' => [
            'host' => getenv('MYSQL_HOST') ?: 'localhost',
            'user' => getenv('MYSQL_USERNAME') ?: 'root',
            'pass' => getenv('MYSQL_PASSWORD') ?: 'password',
            'db' => getenv('MYSQL_DATABASE') ?: 'database'
        ],
        'emailDatabase' => [
            'dsn' => 'mysql:host=localhost;dbname=email;charset=utf8mb4',
            'user' => 'email',
            'pass' => 'password'
        ],
        'raCredentials' => [
            'endpoint' => getenv('RA_ENDPOINT')?:'https://ra.staging.alldigitalrewards.com/api/',
            'username' => getenv('RA_USERNAME')?:'claim',
            'password' => getenv('RA_PASSWORD')?:'claim'
        ],
    ],
    'amqpConfig' => [
        'host' => getenv('AMQP_HOST'),
        'port' => getenv('AMQP_PORT'),
        'username' => getenv('AMQP_USERNAME'),
        'password' => getenv('AMQP_PASSWORD'),
        'channels' => [
            'events' => [
                'channelName' => getenv('AMQP_EVENT_CHANNEL'),
                'maxConsumers' => 1,
                'maxConsumerRuntime' => 180,
                'taskRunner' => __DIR__ . '/../cli/event-task-runner'
            ]
        ]
    ],
    'defaultRoutes' => [
        'superadmin' => '/organizations',
        'admin' => '/programs',
        'configs' => '/report-list',
        'reports' => '/report-list',
        'accounting' => '/report-list'
    ],
    'roles' => [
        'superadmin' => [
            'organization.all',
            'program.all',
            'participant.all',
            'user.all',
            'report.all',
            'product.all',
            'administrators.all',
            'vendors.all',
            'cardaccounts.all',
            'avs.all',
            'sftp.all',
            'redemption-campaigns.all',
        ],
        'admin' => [
            'organization.all',
            'program.all',
            'participant.all',
            'user.all',
            'report.all',
            'administrators.all',
            'sftp.all',
        ],
        'configs' => [
            'organization.all',
            'program.all',
            'participant.all',
//            'user.read',
            'report.all',
            'administrators.read'
        ],
        'reports' => [
            'report.all',
            'participant.read',
//            'user.read',
            'program.read',
            'organization.read',
            'administrators.read',
            'sftp.all',
        ],
        'accounting' => [
            'accounting.all',
            'organization.all',
            'program.all',
            'participant.all',
            'user.all',
            'report.all',
            'administrators.all',
            'sftp.all'
        ]
    ]
];
