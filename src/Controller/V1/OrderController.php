<?php

namespace App\Controller\V1;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/orders', name: 'address')]

class OrderController extends AbstractController
{

    private $user;

    public function __construct(TokenStorageInterface $tokenStorageInterface, JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
        $this->tokenStorageInterface = $tokenStorageInterface;
        $decodedJwtToken = $this->jwtManager->decode($this->tokenStorageInterface->getToken());
        $this->user = $decodedJwtToken['username'];
    }


    #[Route('', methods: 'post', name: 'orderCreate')]
    public function store(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator)
    {
        $entityManager = $doctrine->getManager();

        $product = $entityManager->getRepository(Product::class)->find($request->get('product_id'));

        if ($request->get('quantity') > $product->getStock()) {
            return $this->json([
                'message' => 'Out of stock'
            ], 400);
        }

        if (!$product) {
            return $this->json([
                'message' => 'Product Not Found'
            ], 400);
        }

        $address = $entityManager->getRepository(Address::class)->find($request->get('address_id'));

        if (!$address) {
            return $this->json([
                'message' => 'Address Not Found'
            ], 400);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $this->user]);

        $date = new DateTime('now +3 day');

        try {

            $order = new Order();
            $order->setCode($this->generateRandomString());
            $order->setProduct($product);
            $order->setAddress($address);
            $order->setQuantity($request->get('quantity'));
            $order->setShippingDate($date);
            $order->setUser($user);
            $errors = $validator->validate($order);

            if (count($errors) > 0) {
                return $this->getErrorMessages($errors);
            }

            $entityManager->persist($order);
            $entityManager->flush();

            $product->setStock($product->getStock() - $request->get('quantity'));
            $entityManager->persist($product);
            $entityManager->flush();
        } catch (Exception $e) {

            return $this->json([
                'message' => 'Something went wrong'
            ], 400);
        }

        return $this->json([
            'message' => 'Order Created',
            'order_id' => $order->getId()
        ], 201);
    }



    #[Route('', methods: 'put', name: 'orderUpdate')]
    public function update(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator)
    {

        $formData = json_decode($request->getContent());
        $entityManager = $doctrine->getManager();

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $this->user]);
        $order = $entityManager->getRepository(Order::class)->findOneBy(['id' => $formData->id, 'user' => $user->getId()]);
        $address = $entityManager->getRepository(Address::class)->find($formData->address_id);

        if (!$order) {
            return $this->json([
                'status' => 'error',
                'data' => 'Order Not Found'
            ], 400);
        }

        $dateDiff = date_diff(new DateTime(), $order->getShippingDate());

        if ($dateDiff === 0) {
            return $this->json([
                'status' => 'error',
                'message' => 'The order is past the delivery date and cannot be updated.'
            ], 400);
        }


        if ($formData->quantity > $order->getProduct()->getStock()) {
            return $this->json([
                'status' => 'error',
                'data' => 'Out of stock'
            ], 400);
        }


        try {

            $order->setAddress($address);
            $order->setQuantity($formData->quantity);
            $errors = $validator->validate($order);
            if (count($errors) > 0) {
                return $this->getErrorMessages($errors);
            }
            $entityManager->persist($order);
            $entityManager->flush();

        } catch (Exception $e) {

            return $this->json([
                'message' => 'Something went wrong'
            ], 400);
        }

        return $this->json([
            'message' => 'Order Updated',
            'address_id' => $order->getId()
        ], 200);
    }


    #[Route('', methods: 'get', name: 'orderReadAll')]
    public function readAll(ManagerRegistry $doctrine)
    {

        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $this->user]);
        $address = $user->getOrders();

        $response = [];

        foreach ($address as $item) {
            array_push($response, [
                'id' => $item->getId(),
                'code' => $item->getCode(),
                'address' => [
                    'title' => $item->getAddress()->getTitle(),
                    'address' => $item->getAddress()->getAddress(),
                    'city' => $item->getAddress()->getProvince(),
                    'province' => $item->getAddress()->getProvince()
                ],
                'product' => $item->getProduct()->getTitle(),
            ]);
        }

        return $this->json([
            'status' => 'ok',
            'data' => $response
        ]);
    }

    #[Route('/{id}', methods: 'get', name: 'orderRead')]
    public function read(int $id, ManagerRegistry $doctrine)
    {

        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $this->user]);
        $order = $entityManager->getRepository(Order::class)->findOneBy(['id' => $id, 'user' => $user->getId()]);

        if (!$order) {
            return $this->json([
                'status' => 'error',
                'data' => 'Order Not Found'
            ], 400);
        }

        $response = [
            'id' => $order->getId(),
            'code' => $order->getCode(),
            'address' => [
                'title' => $order->getAddress()->getTitle(),
                'address' => $order->getAddress()->getAddress(),
                'city' => $order->getAddress()->getProvince(),
                'province' => $order->getAddress()->getProvince()
            ],
            'product' => $order->getProduct()->getTitle(),
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

    protected function generateRandomString($length = 10)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
