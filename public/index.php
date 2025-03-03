<?php

use App\Middleware\SecurityMiddleware;
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$container = new Container();
$settings = require __DIR__ . '/../config/container.php';

foreach ($settings as $key => $value) {
    $container->set($key, $value);
}

$container->set('view', function() {
    return Twig::create(__DIR__ . '/../templates', ['cache' => false]);
});

$container->set('allowedDomains', ['http://localhost:8080']); // одобренные хосты помещены в DI контейнер
$container->set(SecurityMiddleware::class, function ($container) {
    return new SecurityMiddleware($container);
});

$container->set('em', function() {
    $paths = [__DIR__ . '/../src/Entity'];

    $dbParams = [
        'driver'   => 'pdo_pgsql',
        'host'     => $_ENV['DB_HOST'] ?? 'db',
        'user'     => $_ENV['DB_USER'] ?? 'user',
        'password' => $_ENV['DB_PASS'] ?? 'banking_password',
        'dbname'   => $_ENV['DB_NAME'] ?? 'banking',
    ];

    $config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
        $paths,
        true,
        null,
        null,
        false
    );
    
    return \Doctrine\ORM\EntityManager::create($dbParams, $config);
});

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->add(TwigMiddleware::createFromContainer($app));

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

require __DIR__ . '/../src/routes.php';

$app->run();