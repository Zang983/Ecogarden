<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class UserController extends AbstractController
{
    #[Route('/api/user', name: 'api_register', methods: [Request::METHOD_POST])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $user = $this->createUserFromRequest($request, $validator, $passwordHasher);
        if ($user instanceof JsonResponse) {
            return $user;
        }
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['status' => 'User created!'], Response::HTTP_CREATED);
    }

    #[Route('/api/auth', name: 'api_login', methods: [Request::METHOD_POST])]
    public function login(JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            return new JsonResponse(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }
        $token = $jwtManager->create($user);

        return new JsonResponse(['token' => $token], Response::HTTP_OK);
    }

    #[isGranted('ROLE_ADMIN')]
    #[Route('/api/user/{id}', name: 'api_delete_user', methods: [Request::METHOD_DELETE])]
    public function deleteUser(
        User $user,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['status' => 'User deleted'], Response::HTTP_OK);
    }

    #[isGranted('ROLE_ADMIN')]
    #[Route('/api/user/{id}', name: 'api_update_user', methods: [Request::METHOD_PUT])]
    public function updateUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $newUser = $this->createUserFromRequest($request, $validator, $passwordHasher);
        if ($newUser instanceof JsonResponse) {
            return $newUser;
        }
        $user->setEmail($newUser->getEmail());
        $user->setCity($newUser->getCity());
        $user->setZipCode($newUser->getZipCode());
        $user->setPassword($newUser->getPassword());
        //If the request is a PUT, we don't want to override the roles if they are not provided
        if (empty($newUser->getRoles())) {
            $user->setRoles($newUser->getRoles());
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['status' => 'User updated!'], Response::HTTP_OK);
    }

    private function createUserFromRequest(
        Request $request,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher
    ): User|JsonResponse {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }
        $user = new User();
        $user->setEmail($data['email'] ?? "");
        $user->setCity($data['city'] ?? null);
        $user->setZipCode($data['zip_code'] ?? null);
        $user->setPassword($data['password'] ?? "");
        //If the request is a PUT, we don't want to override the roles if they are not provided
        if ($request->getMethod() === 'PUT') {
            isset($data['roles']) ? $user->setRoles($data['roles']) : $user->setRoles([]);
        } else {
            $user->setRoles($data['roles'] ?? ['ROLE_USER']);
        }
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => (string)$errors], Response::HTTP_BAD_REQUEST);
        }
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        return $user;
    }

}