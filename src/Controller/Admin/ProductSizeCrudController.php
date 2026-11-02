<?php

namespace App\Controller\Admin;

use App\Entity\ProductSize;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class ProductSizeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductSize::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('product', 'Produit');
        yield TextField::new('size', 'Taille');
        yield IntegerField::new('quantity', 'Stock');
    }
}
