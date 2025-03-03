<?php

use DI\Container;
use Doctrine\ORM\ORMSetup;
use Slim\Views\Twig;
use Twig\Loader\FilesystemLoader;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
return [
    Twig::class => function (Container $container) {
        $loader = new FilesystemLoader(__DIR__ . '/../templates');
        return new Twig($loader, [
            'cache' => __DIR__ . '/../var/cache/twig',
            'debug' => true,
        ]);
    },

    'db.params' => [
        'driver'   => 'pdo_pgsql',
        'host'     => 'db',
        'dbname'   => 'banking',
        'user'     => 'user',
        'password' => 'banking_password',
        'port'     => 5432,
    ],

    Connection::class => function (Container $container) {
        $params = $container->get('db.params');
        return \Doctrine\DBAL\DriverManager::getConnection($params);
    },

    EntityManager::class => function (Container $container) {
        $config = ORMSetup::createAnnotationMetadataConfiguration(
            [__DIR__ . '/../src/Entity'],
            true,
            __DIR__ . '/../var/cache/doctrine',
            new FilesystemAdapter('doctrine', 0, __DIR__ . '/../var/cache/doctrine'),
            false
        );

        $connection = $container->get(Connection::class);
        return EntityManager::create($connection, $config);
    },
];