<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine, EntityManagerInterface $entityManager)
    {
    }


    #[Route('/user', name: 'app_user', methods: ["GET", "POST"])]
    public function index(Request $request): JsonResponse
    {
        $user_id = $request->query->get('user_id');

        $em = $this->doctrine->getRepository(User::class)->find($user_id);


        return $this->json([
            'message' => 'Welcome to your new controller!',
            'test' => $request->query->get('test'),
        ]);
    }


    public function createAction(Request $request): JsonResponse
    {
        $name = $request->query->get('name');
        $email = $request->query->get('email');
        $password = $request->query->get('password');

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($password);

        $entityManager = $this->doctrine->getManager();

        // tells Doctrine you want to (eventually) save the user (no queries yet)
        $entityManager->persist($user);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        return $this->json('Saved new user with id ' . $user->getId());
    }


}
