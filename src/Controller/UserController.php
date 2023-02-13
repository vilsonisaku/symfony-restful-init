<?php

namespace App\Controller;

use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Flex\Response;

class UserController extends AbstractController
{
    private $tokenStorage;

    public function __construct(private ManagerRegistry $doctrine, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/user', name: 'app_user', methods: ["GET", "POST"])]
    public function index(Request $request): JsonResponse
    {
        $user_id = $request->get('user_id');

        $em = $this->doctrine->getRepository(User::class)->find($user_id);


        return $this->json([
            'message' => 'Welcome to your new controller!',
            'test' => $request->query->get('test'),
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function createAction(Request $request): JsonResponse
    {
        $name = $request->get('name');
        $email = $request->get('email');
        $role = $request->get('role');
        $password = $request->get('password');

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setRole($role);
        $user->setPassword($password);
        $user->setCreatedAt( new \DateTimeImmutable() );

        $entityManager = $this->doctrine->getManager();

        // tells Doctrine you want to (eventually) save the user (no queries yet)
        $entityManager->persist($user);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        return $this->json('Saved new user with id ' . $user->getId());
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $email = $request->get('email');
        $password = $request->get('password');
        $user = $this->doctrine->getRepository(User::class)->findByColumn('email', $email);
        if ($user) {
            if ($user->getPassword() != $password) {
                return $this->json([
                    'message' => 'Wrong credentials'
                ], 400);
            } else {
                $token = $JWTManager->create($user);
                return $this->json([
                    'message' => 'Logged in successfully!',
                    'token' => $token
                ]);
            }
        } else {
            return $this->json(['message' => 'User does not exist'], 404);
        }
    }


    #[Route('add/user', name: 'add_user', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        // user is logged in
        $data = $request->request->all();
        $name = $data['name'];
        $email = $data['email'];
        $role = $data['role'];
        $password = 'testPassword';
        if (empty($name) || empty($role) || empty($email)) {
            throw new NotFoundHttpException('Expecting mandatory parameters!');
        }

        $this->doctrine->getRepository(User::class)->saveUser($name, $email, $role, $password);

        return new JsonResponse(['status' => 'User created successfully!'], \Symfony\Component\HttpFoundation\Response::HTTP_CREATED);
    }


    #[Route("/get/user/{id}", name: "get_one_user", methods: ["GET"])]
    public function get($id): JsonResponse
    {
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['id' => $id]);
        if ($user) {
            $data = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'password' => $user->getPassword(),
            ];

            return new JsonResponse($data, \Symfony\Component\HttpFoundation\Response::HTTP_OK);
        } else {
            return $this->json([
                'message' => 'User with id '.$id. ' does not exists!'
            ]);
        }
    }


    #[Route("/get/users", name:"get_all_users", methods:["GET"])]

    public function getAll(): JsonResponse
    {
        $users = $this->doctrine->getRepository(User::class)->findAll();
        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'password' => $user->getPassword(),
            ];
        }

        return new JsonResponse($data, \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }


    #[Route("/update/user/{id}", name: 'update_user', methods: ["PUT"])]
    public function update($id, Request $request): JsonResponse
    {
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['id' => $id]);
        $data = $request->request->all();
        empty($data['name']) ? true : $user->setName($data['name']);
        empty($data['email']) ? true : $user->setLastName($data['email']);
        empty($data['role']) ? true : $user->setEmail($data['role']);
        empty($data['password']) ? true : $user->setPhoneNumber($data['password']);

        $updatedUser = $this->doctrine->getRepository(User::class)->updateUser($user);

        return new JsonResponse($updatedUser->toArray(), \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }


    #[Route("/delete/user/{id}", name: "delete_user", methods: ["DELETE"])]
    public function delete($id): JsonResponse
    {
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['id' => $id]);
        $this->doctrine->getRepository(User::class)->removeUser($user);

        return new JsonResponse(['status' => 'User deleted'], \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
    }

}
