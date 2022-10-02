<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController
{
    /**
     * @Route("/api/products", name="product", methods={"GET"})
     */
    public function getProductList(ProductRepository $productRepository): JsonResponse
    {
        $productList = $productRepository->findAll();

        return $this->json([
            'products' => $productList,
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ProductController.php',
        ]);
    }

    /**
     * @Route("/api/products/{id}", name="detailProduct", methods={"GET"})
     */
    public function getDetailProduct(Product $product): JsonResponse
    {
        return $this->json([
            'product' => $product,
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ProductController.php',
        ]);
    }
}
