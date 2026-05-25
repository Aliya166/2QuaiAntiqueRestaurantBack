<?php

namespace App\Controller;
use App\Entity\Picture;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\PictureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class PictureController extends AbstractController
{
    #[Route('/api/pictures', name: 'api_pictures', methods: ['GET'])]
    public function getPictures(PictureRepository $pictureRepository): JsonResponse
    {
        $pictures = $pictureRepository->findAll();

        $data = [];

        foreach ($pictures as $picture) {
            $data[] = [
                'id' => $picture->getId(),
                'title' => $picture->getTitle(),
                'image_url' => $picture->getImageUrl(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/pictures', name: 'api_pictures_create', methods: ['POST'])]
public function createPicture(
    Request $request,
    EntityManagerInterface $entityManager
): JsonResponse
{
    $title = $request->request->get('title');
    $image = $request->files->get('image');

    if (!$title || !$image) {
        return $this->json([
            'message' => 'Titre et image obligatoires'
        ], 400);
    }

    $extension = pathinfo(
        $image->getClientOriginalName(),
        PATHINFO_EXTENSION
    );

    $newFilename = uniqid() . '.' . $extension;

    $image->move(
        $this->getParameter('kernel.project_dir')
        . '/../../2QuaiAntiqueRestaurantFront/images/gallery',
        $newFilename
    );

    $picture = new Picture();

    $picture->setTitle($title);

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    $picture->setSlug($slug . '-' . uniqid());

    $picture->setCreatedAt(new \DateTimeImmutable());


    $picture->setImageUrl(
        '/images/gallery/' . $newFilename
    );

    $restaurant = $entityManager->getReference(\App\Entity\Restaurant::class, 1);
    $picture->setRestaurant($restaurant);

    $entityManager->persist($picture);
    $entityManager->flush();

    return $this->json([
        'message' => 'Photo ajoutée'
    ]);
}

    #[Route('/api/pictures/{id<\d+>}/update', name: 'api_pictures_update', methods: ['POST'])]
    public function updatePicture(
        int $id,
        Request $request,
        PictureRepository $pictureRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
       $picture = $pictureRepository->find($id);

       if (!$picture) {
           return $this->json(['message' => 'Image introuvable'], 404);
        }

        $title = $request->request->get('title');
        $image = $request->files->get('image');

        if ($title) {
            $picture->setTitle($title);

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
            $picture->setSlug($slug . '-' . uniqid());
        }

        if ($image) {
            $extension = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $newFilename = uniqid() . '.' . $extension;

            $image->move(
                $this->getParameter('kernel.project_dir') . '/../../2QuaiAntiqueRestaurantFront/images/gallery',
                $newFilename
           );

           $picture->setImageUrl('/images/gallery/' . $newFilename);
       }

       $entityManager->flush();

       return $this->json(['message' => 'Photo modifiée']);
   }

    #[Route('/api/pictures/{id}', name: 'api_pictures_delete', methods: ['DELETE'])]

    public function deletePicture(

        int $id,

        PictureRepository $pictureRepository,

        EntityManagerInterface $entityManager
    ): JsonResponse {

        $picture = $pictureRepository->find($id);

        if (!$picture) {

            return $this->json(['message' => 'Image introuvable'], 404);

        }

        $entityManager->remove($picture);

        $entityManager->flush();

        return $this->json(['message' => 'Image supprimée']);

    }

}