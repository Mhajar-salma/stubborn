<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products')]
    public function index(
        ProductRepository $productRepository,
        Request $request
    ): Response {
        $min = (float) $request->query->get('min', 0);
        $max = (float) $request->query->get('max', 1000);

        $products = $productRepository->createQueryBuilder('p')
            ->andWhere('p.price >= :min')
            ->andWhere('p.price <= :max')
            ->setParameter('min', (int) round($min * 100))
            ->setParameter('max', (int) round($max * 100))
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'min' => $min,
            'max' => $max,
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_show', requirements: ['id' => '\d+'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
