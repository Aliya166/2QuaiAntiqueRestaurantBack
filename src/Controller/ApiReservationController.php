<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/reservations')]
class ApiReservationController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function getReservations(ReservationRepository $reservationRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'message' => 'Non autorisé'
            ], 401);
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $reservations = $reservationRepository->findAll();
        } else {
            $reservations = $reservationRepository->findBy([
                'user' => $user
            ]);
        }

        $data = [];

        foreach ($reservations as $reservation) {
            $data[] = [
                'id' => $reservation->getId(),
                'date' => $reservation->getDate()?->format('Y-m-d H:i'),
                'guests' => $reservation->getGuests(),
                'comment' => $reservation->getComment(),
                'user' => [
                    'email' => $reservation->getUser()?->getEmail()
                ]
            ];
        }

        return $this->json($data);
    }

    #[Route('', methods: ['POST'])]
    public function createReservation(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $token = $request->request->get('token');

        $user = $entityManager
            ->getRepository(\App\Entity\User::class)
            ->findOneBy(['apiToken' => $token]);

        if (!$user) {
            return $this->json([
                'message' => 'Token reçu mais user introuvable',
                'token' => $token
            ], 401);
        }

        $reservation = new Reservation();

        $reservation->setDate(
            new \DateTimeImmutable($request->request->get('date'))
        );

        $reservation->setGuests(
            (int) $request->request->get('guests')
        );

        $reservation->setComment(
            $request->request->get('comment')
        );

        $reservation->setUser($user);

        $entityManager->persist($reservation);
        $entityManager->flush();

        return $this->json([
            'message' => 'Réservation créée'
        ]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteReservation(
        int $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $reservation = $entityManager
            ->getRepository(Reservation::class)
            ->find($id);

        if (!$reservation) {
            return $this->json([
                'message' => 'Réservation introuvable'
            ], 404);
        }

        $entityManager->remove($reservation);
        $entityManager->flush();

        return $this->json([
            'message' => 'Réservation supprimée'
        ]);
    }

    #[Route('/{id}/update', methods: ['POST'])]
    public function updateReservation(
        int $id,
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Non autorisé'], 401);
        }

        $reservation = $reservationRepository->find($id);

        if (!$reservation) {
            return $this->json(['message' => 'Réservation introuvable'], 404);
        }

        if (
            $reservation->getUser() !== $user
            && !in_array('ROLE_ADMIN', $user->getRoles())
        ) {
            return $this->json(['message' => 'Accès refusé'], 403);
        }

        $date = $request->request->get('date');
        $guests = $request->request->get('guests');
        $comment = $request->request->get('comment');

        $reservation->setDate(new \DateTimeImmutable($date));
        $reservation->setGuests((int) $guests);
        $reservation->setComment($comment);

        $entityManager->flush();

        return $this->json([
            'message' => 'Réservation modifiée'
        ]);
    }
}
