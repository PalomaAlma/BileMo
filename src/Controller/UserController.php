<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/api/users", name="user")
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
    public function getDetailUser(UserRepository $userRepository, $id): JsonResponse
    {
        return $this->json([
            'user' => $userRepository->findOneById($id),
        ]);
    }


    /**
     * @Route("/api/users/new", name="user_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UserRepository $userRepository, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $userRepository->add($user, true);

        $jsonUser = $serializer->serialize($user, 'json');

        $location = $urlGenerator->generate('detailUser', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * @Route("/api/users/{id}/edit", name="user_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, UserRepository $userRepository, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator, $id): JsonResponse
    {
        $updatedUser = $serializer->deserialize($request->getContent(),
            User::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $userRepository->findOneById($id)]);

        $userRepository->add($updatedUser, true);

        $jsonUser = $serializer->serialize($updatedUser, 'json');

        $location = $urlGenerator->generate('detailUser', ['id' => $updatedUser->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * @Route("/api/users/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserRepository $userRepository, $id, UrlGeneratorInterface $urlGenerator, SerializerInterface $serializer): JsonResponse
    {
        $userRepository->remove($userRepository->findOneById($id), true);

        $jsonUser = $serializer->serialize($userRepository->findAll(), 'json');

        $location = $urlGenerator->generate('user');

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }
}
