<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductSize;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Stubborn');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Gestion de la boutique');

        yield MenuItem::linkToCrud(
            'Produits',
            'fas fa-tshirt',
            Product::class
        );

        yield MenuItem::linkToCrud(
            'Tailles et stocks',
            'fas fa-boxes-stacked',
            ProductSize::class
        );
    }
}
