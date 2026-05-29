<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Menu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class ApiMenuController extends AbstractController
{
    #[Route('/api/menus', name: 'api_menus', methods: ['GET'])]
    public function index(MenuRepository $menuRepository): JsonResponse
    {
        $menus = $menuRepository->findAll();

        $data = [];

        foreach ($menus as $menu) {
            $data[] = [
                'id' => $menu->getId(),
                'name' => $menu->getName(),
                'description' => $menu->getDescription(),
                'price' => $menu->getPrice(),
                'category' => $menu->getCategory(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/menus', name: 'api_menus_create', methods: ['POST'])]
    public function createMenu(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $menu = new Menu();

        $menu->setName($request->request->get('name'));
        $menu->setDescription($request->request->get('description'));
        $menu->setPrice((float)$request->request->get('price'));
        $menu->setCategory($request->request->get('category'));

        $entityManager->persist($menu);
        $entityManager->flush();

        return $this->json([
            'message' => 'Menu créé'
       ]);
    }

    #[Route('/api/menus/{id}/update', name: 'api_menus_update', methods: ['POST'])]
    public function updateMenu(
        int $id,
        Request $request,
        MenuRepository $menuRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $menu = $menuRepository->find($id);

        if (!$menu) {
            return $this->json([
                'message' => 'Menu introuvable'
            ], 404);
        }

        $menu->setName($request->request->get('name'));
        $menu->setDescription($request->request->get('description'));
        $menu->setPrice((float)$request->request->get('price'));
        $menu->setCategory($request->request->get('category'));

        $entityManager->flush();

        return $this->json([
            'message' => 'Menu modifié'
        ]);
    }

    #[Route('/api/menus/{id}', name: 'api_menus_delete', methods: ['DELETE'])]
    public function deleteMenu(
        int $id,
        MenuRepository $menuRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

       $menu = $menuRepository->find($id);

       if (!$menu) {
           return $this->json([
               'message' => 'Menu introuvable'
            ], 404);
        }

        $entityManager->remove($menu);
        $entityManager->flush();

        return $this->json([
            'message' => 'Menu supprimé'
    ]);
}
   
}