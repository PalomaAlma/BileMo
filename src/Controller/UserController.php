<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route("/api/users", name="app_user")
     */
    public function index(UserRepository $userRepository): JsonResponse
    {
        return $this->json([
            'users' => $userRepository->findAll(),
        ]);
    }


    /**
     * @Route("/api/users/{id}", name="detailUser", methods={"GET"})
     */
    public function getDetailUser(User $user): JsonResponse
    {
        return $this->json([
            'user' => $user,
        ]);
    }
}
