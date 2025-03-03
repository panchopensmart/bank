<?php

use App\Middleware\SecurityMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use App\Controller\AuthController;
use App\Controller\BankController;
use App\Middleware\AuthMiddleware;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response) {
    return $this->get('view')->render($response, 'home.twig');
})->setName('home');

$app->get('/login',function (Request $request, Response $response) {
    return $this->get('view')->render($response, 'login.twig');
})->setName('login');
$app->post('/login', [AuthController::class, 'login']);
$app->get('/logout', [AuthController::class, 'logout'])->setName('logout');

$app->group('', function (RouteCollectorProxy $group) {
    $group->get('/dashboard', [BankController::class, 'dashboard'])->setName('dashboard');
    $group->get('/transfer', [BankController::class, 'transferPage'])->setName('transfer');
    $group->post('/transfer', [BankController::class, 'transfer'])
        ->add($this->get(SecurityMiddleware::class));
})->add(new AuthMiddleware());