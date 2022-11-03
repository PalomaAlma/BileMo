<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController
{
    /**
     * @Route("/api/products", name="product", methods={"GET"})
     */
    public function getProductList(ProductRepository $productRepository, Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        return $this->json([
            'products' => $productRepository->findAllWithPagination($page, $limit),
        ]);
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
