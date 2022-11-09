<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UserController extends AbstractController
{

    /**
     * @Route("/api/users", name="users")
     */
    public function index(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {

        $jsonUser = $serializer->serialize($userRepository->findAll(), 'json');

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/users/client-{id}", name="usersByClient")
     * @throws InvalidArgumentException
     */
    public function usersByClient(TagAwareCacheInterface $cachePool, Request $request, UserRepository $userRepository, ClientRepository $clientRepository, SerializerInterface $serializer, $id): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllUsersByClient-" . $id . "-" . $page . "-" . $limit;

        $jsonUserList = $cachePool->get($idCache, function (ItemInterface $item) use ($userRepository, $page, $limit, $id, $serializer) {

            $item->tag("usersCache");

            $userList = $userRepository->findAllWithPagination($page, $limit, $id);

            $context = SerializationContext::create()->setGroups(['getUsers']);
            return $serializer->serialize($userList, 'json', $context);
        });

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/users/{id}", name="detailUser", methods={"GET"})
     */
    public function getDetailUser(UserRepository $userRepository, SerializerInterface $serializer, $id): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUser = $serializer->serialize($userRepository->findOneById($id), 'json', $context);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }


    /**
     * @Route("/api/users/new", name="user_new", methods={"GET", "POST"})
     */
    public function new(Request $request,UserPasswordHasherInterface $userPasswordHasher, UserRepository $userRepository, ClientRepository $clientRepository, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $user->setPassword($userPasswordHasher->hashPassword($user, "password"));
        $content = $request->toArray();
        $client = $content['client_id'] ?? -1;
        $user->setClient($clientRepository->findOneById($client));

        $userRepository->add($user, true);

        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUser = $serializer->serialize($user, 'json', $context);

        $location = $urlGenerator->generate('detailUser', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * @Route("/api/users/{id}", name="user_edit", methods={"PUT"})
     */
    public function edit(Request $request, UserRepository $userRepository, User $currentUser, ClientRepository $clientRepository, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator, $id, TagAwareCacheInterface $cachePool, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        $updatedUser = $serializer->deserialize($request->getContent(), User::class, 'json');
        $currentUser->setEmail($updatedUser->getEmail());
        $currentUser->setPassword($userPasswordHasher->hashPassword($currentUser, $updatedUser->getPassword()));

        // On vérifie les erreurs
        /*$errors = $validator->validate($currentUser);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }*/

        $content = $request->toArray();
        $client = $content['client_id'] ?? -1;
        $currentUser->setClient($clientRepository->findOneById($client));

        $userRepository->add($currentUser, true);

        $cachePool->invalidateTags(["usersCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/api/users/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserRepository $userRepository, $id, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $cachePool->invalidateTags(["usersCache"]);

        $userRepository->remove($userRepository->findOneById($id), true);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
