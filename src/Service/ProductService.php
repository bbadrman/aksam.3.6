<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class ProductService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function create(Product $product): Product
    {
        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    public function update(Product $product): Product
    {
        $this->em->flush();

        return $product;
    }
}
