<?php

namespace App\Controller;

use Doctrine\ORM\Exception\NotSupported;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Doctrine\ORM\EntityManager;
use App\Entity\User;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class AuthController
{
    private Twig $view;
    private EntityManager $em;

    public function __construct(Twig $view, EntityManager $em)
    {
        $this->view = $view;
        $this->em = $em;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function loginPage(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'login.twig');
    }

    /**
     * @throws SyntaxError
     * @throws NotSupported
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $userRepository = $this->em->getRepository(User::class);
        $user = $userRepository->findOneBy(['username' => $username]);

        if ($user && $password === $user->getPassword()) {
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();

            return $response
                ->withHeader('Location', '/dashboard')
                ->withStatus(302);
        }

        return $this->view->render($response, 'login.twig', [
            'error' => 'Invalid username or password'
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        session_destroy();

        return $response
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }
}