<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\ProductSize;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:load-products',
    description: 'Charge les 10 produits de la boutique Stubborn'
)]
class LoadProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $products = [
            ['Blackbelt', 2990, true],
            ['BlueBelt', 2990, false],
            ['Street', 3450, false],
            ['Pokeball', 4500, true],
            ['PinkLady', 2990, false],
            ['Snow', 2850, false],
            ['Greyback', 4500, false],
            ['BlueCloud', 3200, false],
            ['BornInUsa', 5990, true],
            ['GreenSchool', 4220, false],
        ];

        $sizes = ['XS', 'S', 'M', 'L', 'XL'];

        foreach ($products as [$name, $price, $featured]) {
            $product = $this->entityManager
                ->getRepository(Product::class)
                ->findOneBy(['name' => $name]);

            if (!$product) {
                $product = new Product();
                $product->setName($name);
            }

            $product->setDescription(
                'Découvre le sweat ' . $name .
                ' de Stubborn. Un style affirmé, sans compromis.'
            );
            $product->setPrice($price);
            $product->setIsFeatured($featured);
            $product->setImage(
                '/images/products/' . strtolower($name) . '.jpg'
            );

            $this->entityManager->persist($product);

            foreach ($sizes as $sizeName) {
                $sizeStock = null;

                foreach ($product->getSizes() as $existingSize) {
                    if ($existingSize->getSize() === $sizeName) {
                        $sizeStock = $existingSize;
                        break;
                    }
                }

                if (!$sizeStock) {
                    $sizeStock = new ProductSize();
                    $sizeStock->setSize($sizeName);
                    $sizeStock->setQuantity(2);
                    $sizeStock->setProduct($product);
                    $product->addSize($sizeStock);

                    $this->entityManager->persist($sizeStock);
                }
            }
        }

        $this->entityManager->flush();

        $output->writeln(
            '<info>Les 10 produits Stubborn sont chargés !</info>'
        );
        $output->writeln(
            '<info>5 tailles par produit, avec au moins 2 unités en stock.</info>'
        );

        return Command::SUCCESS;
    }
}
