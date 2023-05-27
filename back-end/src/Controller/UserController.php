<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

#[Route('/users')]
class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        $this->entityManager = $entityManager;
    }

    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Perform user authentication (example only, update with your own logic)
        $user = $this->entityManager->getRepository(User::class)->findOneByEmail($data['email']);

        if (!$user) {
            return $this->json(['message' => 'Invalid Credentials']);
        }

        if ($user->getPassword() !== $data['password']) {
            return $this->json(['message' => 'Invalid Credentials']);
        }

        // Generate the JWT token
        $token = $JWTManager->create($user);

        #return $this->json(['message' => $data['email']]);
        return $this->json(['message' => $user->getId()]);
    }

    #[Route('/', name: 'user_list', methods: ['GET'])]
    #[Security('is_granted("ROLE_USER")')]
    public function listUsers(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();

        $response = [];
        foreach ($users as $user) {
            $response[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ];
        }

        return $this->json($response);
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    #[Security('is_granted("ROLE_USER")')]
    public function showUser(User $user): JsonResponse
    {
        $response = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];

        return $this->json($response);
    }

    #[Route('/inscription', name: 'user_create', methods: ['POST'])]
    public function createUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $request->request->get('email');

        $userFound = $this->entityManager->getRepository(User::class)->findOneByEmail($data['email']);

        if ($userFound) {
            return $this->json(['message' => 'User already exists with the same mail']);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($data['password']);
        $user->setRoles($data['roles'] ?? []);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => 'User created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'])]
    #[Security('is_granted("ROLE_USER")')]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(['message' => 'User deleted successfully']);
    }
}
