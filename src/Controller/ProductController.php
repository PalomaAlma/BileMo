<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductController extends AbstractController
{
    /**
     * @Route("/api/products", name="product", methods={"GET"})
     */
    public function getProductList(ProductRepository $productRepository, Request $request, TagAwareCacheInterface $cachePool, SerializerInterface $serializer): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllProducts-" . $page . "-" . $limit;

        $productList = $cachePool->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit) {

            $item->tag("productsCache");

            return $productRepository->findAllWithPagination($page, $limit);

        });

        $jsonProductList = $serializer->serialize($productList, 'json');
        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/products/{id}", name="detailProduct", methods={"GET"})
     */
    public function getDetailProduct(Product $product): JsonResponse
    {
        return $this->json([
            'product' => $product,
        ]);
    }
}
