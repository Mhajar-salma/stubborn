<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request, LoggerInterface $logger): Response
    {
        $cart = $request->getSession()->get('cart', []);

        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

        $secretKey = $_ENV['STRIPE_SECRET_KEY']
            ?? $_SERVER['STRIPE_SECRET_KEY']
            ?? getenv('STRIPE_SECRET_KEY')
            ?: '';

        if ($secretKey === '') {
            $this->addFlash('error', 'Stripe n’est pas configuré. Vérifiez votre clé secrète.');
            return $this->redirectToRoute('app_cart');
        }

        $lineItems = [];

        foreach ($cart as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item['name'] . ' - Taille ' . $item['size'],
                    ],
                    'unit_amount' => (int) round(((float) $item['price']) * 100),
                ],
                'quantity' => (int) $item['quantity'],
            ];
        }

        try {
            $stripe = new StripeClient($secretKey);

            $checkoutSession = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                'line_items' => $lineItems,
                'success_url' => $this->generateUrl(
                    'app_checkout_success',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'cancel_url' => $this->generateUrl(
                    'app_checkout_cancel',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]);

            return $this->redirect($checkoutSession->url, Response::HTTP_SEE_OTHER);

        } catch (\Throwable $exception) {
            $logger->error('Erreur Stripe Checkout : ' . $exception->getMessage());

            $this->addFlash('error', 'Le paiement a échoué. Vérifiez votre configuration Stripe ou réessayez.');
            return $this->redirectToRoute('app_cart');
        }
    }

    #[Route('/checkout/success', name: 'app_checkout_success', methods: ['GET'])]
    public function success(): Response
    {
        return $this->render('checkout/success.html.twig');
    }

    #[Route('/checkout/cancel', name: 'app_checkout_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        return $this->render('checkout/cancel.html.twig');
    }
}
