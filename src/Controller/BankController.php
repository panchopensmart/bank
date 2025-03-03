<?php

namespace App\Controller;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Random\RandomException;
use Slim\Views\Twig;
use Doctrine\ORM\EntityManager;
use App\Entity\User;
use App\Entity\Transaction;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class BankController
{
    private Twig $view;
    private EntityManager $em;

    public function __construct(Twig $view, EntityManager $em)
    {
        $this->view = $view;
        $this->em = $em;
    }

    /**
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws NotSupported
     * @throws TransactionRequiredException
     */
    public function dashboard(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user_id'];
        $user = $this->em->find(User::class, $userId);

        $userRepository = $this->em->getRepository(User::class);
        $allUsers = $userRepository->findAll();

        $transactionRepository = $this->em->getRepository(Transaction::class);
        $transactions = $transactionRepository->findBy(
            ['sender' => $userId],
            null,
            5
        );

        return $this->view->render($response, 'dashboard.twig', [
            'user' => $user,
            'balance' => $user->getBalance(),
            'users' => $allUsers,
            'transactions' => $transactions
        ]);
    }

    /**
     * @throws RandomException
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws TransactionRequiredException
     * @throws NotSupported
     */
    public function transferPage(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user_id'];
        $user = $this->em->find(User::class, $userId);

        $userRepository = $this->em->getRepository(User::class);
        $allUsers = $userRepository->createQueryBuilder('u')
            ->where('u.id != :id')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getResult();

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return $this->view->render($response, 'transfer.twig', [
            'user' => $user,
            'balance' => $user->getBalance(),
            'users' => $allUsers,
            'csrf_token' => $_SESSION['csrf_token']
        ]);
    }

    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function transfer(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $senderId = $_SESSION['user_id'];
        $recipientId = $data['recipient_id'] ?? '';
        $amount = floatval($data['amount']) ?? '';

        if ($amount <= 0) {
            return $this->view->render($response, 'transfer.twig', [
                'error' => 'Amount must be greater than zero',
                'csrf_token' => $_SESSION['csrf_token']
            ]);
        }

        $this->em->beginTransaction();

        try {
            $sender = $this->em->find(User::class, $senderId);
            $recipient = $this->em->find(User::class, $recipientId);

            if (!$recipient) {
                throw new \Exception('Recipient not found');
            }

            if ($sender->getBalance() < $amount) {
                throw new \Exception('Insufficient balance');
            }

            $sender->setBalance($sender->getBalance() - $amount);
            $recipient->setBalance($recipient->getBalance() + $amount);

            $transaction = new Transaction();
            $transaction->setSender($sender);
            $transaction->setRecipient($recipient);
            $transaction->setAmount($amount);
            $transaction->setCreatedAt(new \DateTime());

            $this->em->persist($transaction);
            $this->em->flush();

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();

            return $this->view->render($response, 'transfer.twig', [
                'error' => $e->getMessage(),
                'csrf_token' => $_SESSION['csrf_token']
            ]);
        }

        return $response
            ->withHeader('Location', '/dashboard?success=1')
            ->withStatus(302);
    }
}