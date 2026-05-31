<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class ApiUserController extends AbstractController
{
    #[Route('/api/user/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Non autorisé'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'allergy' => $user->getAllergy(),
        ]);
    }

    #[Route('/api/user/me', methods: ['POST'])]
    public function updateMe(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Non autorisé'], 401);
        }

        $user->setFirstName($request->request->get('firstName'));
        $user->setLastName($request->request->get('lastName'));
        $user->setEmail($request->request->get('email'));
        $user->setAllergy($request->request->get('allergy'));
        $user->setUpdatedAt(new DateTimeImmutable());

        $entityManager->flush();

        return $this->json([
            'message' => 'Profil modifié'
        ]);
    }

    #[Route('/api/user/password', methods: ['POST'])]
    public function updatePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Non autorisé'], 401);
        }

        $password = $request->request->get('password');
        $passwordConfirm = $request->request->get('passwordConfirm');

        if (!$password || !$passwordConfirm) {
            return $this->json(['message' => 'Champs manquants'], 400);
        }

        if ($password !== $passwordConfirm) {
            return $this->json(['message' => 'Les mots de passe ne correspondent pas'], 400);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $password);

        $user->setPassword($hashedPassword);
        $user->setUpdatedAt(new DateTimeImmutable());

        $entityManager->flush();

        return $this->json([
            'message' => 'Mot de passe modifié'
        ]);
    }
}
