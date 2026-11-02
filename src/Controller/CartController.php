<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(Request $request): Response
    {
        $cart = $request->getSession()->get('cart', []);
        $total = 0;

        foreach ($cart as $key => $item) {
            $price = (float) ($item['price'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            $cart[$key]['subtotal'] = round($price * $quantity, 2);
            $total += $cart[$key]['subtotal'];
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => round($total, 2),
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request): Response
    {
        $sizeName = trim((string) $request->request->get('size', ''));
        $quantity = max(1, (int) $request->request->get('quantity', 1));

        $selectedSize = null;

        foreach ($product->getSizes() as $stock) {
            if ($stock->getSize() === $sizeName) {
                $selectedSize = $stock;
                break;
            }
        }

        if (!$selectedSize || $selectedSize->getQuantity() < 1) {
            $this->addFlash('error', 'Cette taille est indisponible.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        $cart = $request->getSession()->get('cart', []);
        $key = $product->getId() . '_' . $sizeName;
        $currentQuantity = (int) ($cart[$key]['quantity'] ?? 0);
        $newQuantity = $currentQuantity + $quantity;

        if ($newQuantity > $selectedSize->getQuantity()) {
            $this->addFlash('error', 'Stock insuffisant pour cette quantité.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        $cart[$key] = [
            'productId' => $product->getId(),
            'name' => $product->getName(),
            'image' => $product->getImage(),
            'price' => $product->getPrice() / 100,
            'size' => $sizeName,
            'quantity' => $newQuantity,
        ];

        $request->getSession()->set('cart', $cart);
        $this->addFlash('success', 'Produit ajouté au panier !');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/quantity/{key}', name: 'app_cart_quantity', methods: ['POST'])]
    public function quantity(
        string $key,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $cart = $request->getSession()->get('cart', []);
        $quantity = (int) $request->request->get('quantity', 1);

        if (!isset($cart[$key])) {
            return $this->redirectToRoute('app_cart');
        }

        if ($quantity <= 0) {
            unset($cart[$key]);
        } else {
            $product = $em->getRepository(Product::class)->find($cart[$key]['productId']);

            if (!$product) {
                unset($cart[$key]);
            } else {
                $stockFound = false;

                foreach ($product->getSizes() as $stock) {
                    if ($stock->getSize() === $cart[$key]['size']) {
                        $stockFound = true;

                        if ($quantity > $stock->getQuantity()) {
                            $this->addFlash('error', 'Stock insuffisant.');
                            return $this->redirectToRoute('app_cart');
                        }

                        break;
                    }
                }

                if (!$stockFound) {
                    unset($cart[$key]);
                } else {
                    $cart[$key]['quantity'] = $quantity;
                }
            }
        }

        $request->getSession()->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{key}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(string $key, Request $request): Response
    {
        $cart = $request->getSession()->get('cart', []);
        unset($cart[$key]);

        $request->getSession()->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }
}
