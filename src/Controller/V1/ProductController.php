<?php

namespace App\Controller\V1;

use App\Entity\Order;
use App\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('admin/products', name: 'product')]

class ProductController extends AbstractController
{

    #[Route('', methods: 'post', name: 'productCreate')]
    public function store(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator)
    {

        try {

            $entityManager = $doctrine->getManager();
            $product = new Product();
            $product->setTitle($request->get('title'));
            $product->setContent($request->get('content'));
            $product->setStock($request->get('stock'));

            $errors = $validator->validate($product);

            if (count($errors) > 0) {
                return $this->getErrorMessages($errors);
            }

            $entityManager->persist($product);
            $entityManager->flush();
        } catch (Exception $e) {

            return $this->json([
                'message' => 'Something went wrong'
            ], 400);
        }

        return $this->json([
            'message' => 'Product Created',
            'product_id' => $product->getId()
        ], 201);
    }


    #[Route('', methods: 'put', name: 'productUpdate')]
    public function update(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator)
    {

        $formData = json_decode($request->getContent());
        $entityManager = $doctrine->getManager();

        $product = $entityManager->getRepository(Product::class)->find($formData->id);

        if (!$product) {
            return $this->json([
                'message' => 'Product Not Found'
            ], 400);
        }


        try {

            $product->setTitle($formData->title);
            $product->setContent($formData->content);
            $product->setStock($formData->stock);

            $errors = $validator->validate($product);

            if (count($errors) > 0) {
                return $this->getErrorMessages($errors);
            }
            $entityManager = $doctrine->getManager();
            $entityManager->flush();
            
        } catch (Exception $e) {
            return $this->json([
                'message' => 'Something went wrong'
            ], 400);
        }

        return $this->json([
            'message' => 'Product Updated',
            'product_id' => $product->getId()
        ], 201);

    }


    #[Route('/{id}', methods: 'delete', name: 'productDelete')]
    public function delete(int $id, ManagerRegistry $doctrine)
    {
        $entityManager = $doctrine->getManager();
        $product = $entityManager->getRepository(Product::class)->find($id);

        if (!$product) {
            return $this->json([
                'message' => 'Product Not Found'
            ], 400);
        }

        try {
            $entityManager->remove($product);
            $entityManager->flush();
        } catch (Exception $e) {

            return $this->json([
                'message' => 'Something went wrong'
            ], 400);
        }

        return $this->json([
            'message' => 'Product Deleted',
        ], 200);
    }



    #[Route('', methods: 'get', name: 'productReadAll')]
    public function readAll(ManagerRegistry $doctrine)
    {
        $entityManager = $doctrine->getManager();
        $product = $entityManager->getRepository(Product::class)->findAll();

        $response = [];

        foreach ($product as $item) {
            array_push($response, [
                'id' => $item->getId(),
                'title' => $item->getTitle(),
                'content' => $item->getContent(),
                'stock' => $item->getStock()
            ]);
        }


        return $this->json([
            'data' => $response
        ],200);
    }


    #[Route('/{id}', methods: 'get', name: 'productRead')]
    public function read(int $id, ManagerRegistry $doctrine)
    {
        $entityManager = $doctrine->getManager();

        $product = $entityManager->getRepository(Product::class)->find($id);

        if (!$product) {
            return $this->json([
                'data' => 'Product Not Found'
            ], 400);
        }

        $response = [
            'id' => $product->getId(),
            'title' => $product->getTitle(),
            'content' => $product->getContent(),
            'stock' => $product->getStock()
        ];


        return $this->json([
            'status' => 'ok',
            'data' => $response
        ]);
    }


    /**
     * @var $errors validation error messages 
     */

    protected function getErrorMessages($errors)
    {
        $messages = [];

        foreach ($errors as $e) {
            array_push($messages, [$e->getPropertyPath() => $e->getMessage()]);
        }

        return $this->json(['status' => 'error', 'message' => $messages], 422);
    }
}
