<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UserController extends AbstractController
{

    /**
     * Méthode permettant de récupérer l'ensemble des utilisateurs
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des utilisateurs",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre d'éléments que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Tag(name="Utilisateurs")
     *
     * @Route("/api/users", name="users", methods={"GET"})
     */
    public function index(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {

        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUser = $serializer->serialize($userRepository->findAll(), 'json', $context);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
     * Méthode permettant de récupérer la liste des utilisateurs par client référencé
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des utilisateurs par client",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre d'éléments que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="L'id du client pour lequel sont référencés les utilisateurs",
     *     @OA\Schema(type="string")
     * )
     *
     * @OA\Tag(name="Utilisateurs")
     *
     * @Route("/api/users/client-{id}", name="usersByClient", methods={"GET"})
     * @throws InvalidArgumentException
     */
    public function usersByClient(
            TagAwareCacheInterface $cachePool,
            Request $request,
            UserRepository $userRepository,
            ClientRepository $clientRepository,
            SerializerInterface $serializer,
            $id
    ): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);

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
     * Méthode permettant de récupérer les détails d'un utilisateur
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne les détails d'un utilisateur",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     *
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="L'id de l'utilisateur que l'on veut récupérer",
     *     @OA\Schema(type="string")
     * )
     *
     * @OA\Tag(name="Utilisateurs")
     *
     * @Route("/api/users/{id}", name="detailUser", methods={"GET"})
     */
    public function getDetailUser(UserRepository $userRepository, SerializerInterface $serializer, $id): JsonResponse
    {
        if (!is_int($id)) {
            return new JsonResponse("Wrong id, you must enter a number", Response::HTTP_BAD_REQUEST);
        }
        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUser = $serializer->serialize($userRepository->findOneById($id), 'json', $context);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }


    /**
     * Méthode permettant de créer un nouvel utilisateur
     *
     * @OA\Response(
     *     response=201,
     *     description="Crée un utilisateur",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     *
     * @OA\Tag(name="Utilisateurs")
     *
     * @Route("/api/users", name="user_new", methods={"GET", "POST"})
     */
    public function new(Request $request,UserPasswordHasherInterface $userPasswordHasher, UserRepository $userRepository, ClientRepository $clientRepository, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        // On vérifie les erreurs
        $errors = $validator->validate($user);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $user->setPassword($userPasswordHasher->hashPassword($user, "password"));
        $content = $request->toArray();
        $client = $content['client_id'] ?? -1;
        $user->setClient($clientRepository->findOneById($client));

        $userRepository->add($user, true);

        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUser = $serializer->serialize($user, 'json', $context);

        $location = $urlGenerator->generate('detailUser', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $cachePool->invalidateTags(["usersCache"]);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
     * Méthode permettant de modifier un utilisateur
     *
     * @OA\Response(
     *     response=204,
     *     description="Modifie un utilisateur",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     *
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="L'id de l'utilisateur que l'on veut modifier",
     *     @OA\Schema(type="string")
     * )
     *
     * @OA\Tag(name="Utilisateurs")
     *
     * @Route("/api/users/{id}", name="user_edit", methods={"PUT"})
     */
    public function edit(Request $request, UserRepository $userRepository, User $currentUser, ClientRepository $clientRepository, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator, $id, TagAwareCacheInterface $cachePool, UserPasswordHasherInterface $userPasswordHasher, ValidatorInterface $validator): JsonResponse
    {
        if (!is_int($id)) {
            return new JsonResponse("Wrong id, you must enter a number", Response::HTTP_BAD_REQUEST);
        }

        $updatedUser = $serializer->deserialize($request->getContent(), User::class, 'json');
        $currentUser->setEmail($updatedUser->getEmail());
        $currentUser->setPassword($userPasswordHasher->hashPassword($currentUser, $updatedUser->getPassword()));

        // On vérifie les erreurs
        $errors = $validator->validate($currentUser);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $client = $content['client_id'] ?? -1;
        $currentUser->setClient($clientRepository->findOneById($client));

        $userRepository->add($currentUser, true);

        $cachePool->invalidateTags(["usersCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Méthode permettant de récupérer les détails d'un utilisateur
     *
     * @OA\Response(
     *     response=204,
     *     description="Supprime un utilisateur",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     *
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="L'id de l'utilisateur que l'on veut supprimer",
     *     @OA\Schema(type="string")
     * )
     *
     * @OA\Tag(name="Utilisateurs")
     *
     * @Route("/api/users/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, UserRepository $userRepository, $id, TagAwareCacheInterface $cachePool): JsonResponse
    {

        if (!is_int($id)) {
            return new JsonResponse("Wrong id, you must enter a number", Response::HTTP_BAD_REQUEST);
        }

        $cachePool->invalidateTags(["usersCache"]);

        $userRepository->remove($userRepository->findOneById($id), true);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
