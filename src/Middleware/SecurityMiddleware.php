<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Random\RandomException;
use Slim\Psr7\Response;


class SecurityMiddleware
{
    private $allowedDomains;

    public function __construct($container)
    {
        $this->allowedDomains = $container->get('allowedDomains');
    }

    /**
     * @throws RandomException
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Проверка подлинности домена
        $origin = $request->getHeaderLine('Origin');
        $referer = $request->getHeaderLine('Referer');

        if (empty($origin) && empty($referer)) {
            $response = new Response();
            $response->getBody()->write('Invalid request origin or referer.');
            return $response->withStatus(400);
        }

        if (!empty($origin) && !in_array($origin, $this->allowedDomains)) {
            $response = new Response();
            $response->getBody()->write('Invalid request origin.');
            return $response->withStatus(400);
        }

        if (empty($origin) && !empty($referer)) {
            $refererDomain = parse_url($referer, PHP_URL_HOST);
            $allowedHosts = array_map(function ($domain) {
                return parse_url($domain, PHP_URL_HOST);
            }, $this->allowedDomains);

            if (!in_array($refererDomain, $allowedHosts)) {
                $response = new Response();
                $response->getBody()->write('Invalid request referer.');
                return $response->withStatus(400);
            }
        }

        // Проверка CSRF-токена
        $data = $request->getParsedBody();
        $submittedToken = $data['csrf_token'] ?? '';

        if (empty($submittedToken)) {
            $response = new Response();
            $response->getBody()->write('CSRF token is missing.');
            return $response->withStatus(400);
        }

        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
            $response = new Response();
            $response->getBody()->write('Invalid CSRF token.');
            return $response->withStatus(400);
        }


        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return $handler->handle($request);
    }
}