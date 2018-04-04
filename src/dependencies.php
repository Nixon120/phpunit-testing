<?php
// DIC configuration
use Interop\Container\ContainerInterface;

/**
 * Base Dependencies
 */

$container['logger'] = function (ContainerInterface $c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

$container['database'] = function (ContainerInterface $c) {
    $settings = $c->get('settings')['database'];
    $host = $settings['host'];
    $user = $settings['user'];
    $pass = $settings['pass'];
    $db = $settings['db'];

    $dsn = "mysql:host=$host;dbname=$db;charset=utf8";
    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $opt);

    return $pdo;
};

//@TODO move to a service
$container["authentication"] = function ($container) {
    $roles = $container->get('roles');
    $scopes = [];
    array_walk_recursive($roles, function ($v, $k) use (&$scopes) {
        $scopes[] = $v;
    });

    $token = new \Services\Authentication\Token(array_unique($scopes));
    $auth = new \Services\Authentication\Authenticate($container->get('database'), $token);

    return $auth;
};

$container["validation"] = function () {
    return new \Validation\InputValidator;
};

$container['flash'] = function ($container) {
    /** @var \Services\Authentication\Authenticate $authentication */
    $authentication = $container->get('authentication');
    $flash = new \Slim\Flash\Messages();
    $authentication->setFlashMessaging($flash);

    return $flash;
};

$container['renderer'] = function (ContainerInterface $c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path'], [
        'flash' => $c->get('flash')->getMessages(),
        'auth' => $c->get('authentication'),
        'baseUrl' => $c->get('settings')['baseUrl'],
        'states' => [
            'AL'=>'Alabama',
            'AK'=>'Alaska',
            'AZ'=>'Arizona',
            'AR'=>'Arkansas',
            'CA'=>'California',
            'CO'=>'Colorado',
            'CT'=>'Connecticut',
            'DE'=>'Delaware',
            'DC'=>'District of Columbia',
            'FL'=>'Florida',
            'GA'=>'Georgia',
            'HI'=>'Hawaii',
            'ID'=>'Idaho',
            'IL'=>'Illinois',
            'IN'=>'Indiana',
            'IA'=>'Iowa',
            'KS'=>'Kansas',
            'KY'=>'Kentucky',
            'LA'=>'Louisiana',
            'ME'=>'Maine',
            'MD'=>'Maryland',
            'MA'=>'Massachusetts',
            'MI'=>'Michigan',
            'MN'=>'Minnesota',
            'MS'=>'Mississippi',
            'MO'=>'Missouri',
            'MT'=>'Montana',
            'NE'=>'Nebraska',
            'NV'=>'Nevada',
            'NH'=>'New Hampshire',
            'NJ'=>'New Jersey',
            'NM'=>'New Mexico',
            'NY'=>'New York',
            'NC'=>'North Carolina',
            'ND'=>'North Dakota',
            'OH'=>'Ohio',
            'OK'=>'Oklahoma',
            'OR'=>'Oregon',
            'PA'=>'Pennsylvania',
            'RI'=>'Rhode Island',
            'SC'=>'South Carolina',
            'SD'=>'South Dakota',
            'TN'=>'Tennessee',
            'TX'=>'Texas',
            'UT'=>'Utah',
            'VT'=>'Vermont',
            'VA'=>'Virginia',
            'WA'=>'Washington',
            'WV'=>'West Virginia',
            'WI'=>'Wisconsin',
            'WY'=>'Wyoming',
        ]
    ]);
};

require __DIR__ . '/Services/Participant/DI.php';
require __DIR__ . '/Services/User/DI.php';
require __DIR__ . '/Services/Product/DI.php';
require __DIR__ . '/Services/Organization/DI.php';
require __DIR__ . '/Services/Program/DI.php';
require __DIR__ . '/Services/Report/DI.php';
