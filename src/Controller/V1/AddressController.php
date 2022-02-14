<?php

namespace App\Controller\V1;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/addresess', name: 'address')]

class AddressController extends AbstractController
{

    private $user;

    public function __construct(TokenStorageInterface $tokenStorageInterface, JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
        $this->tokenStorageInterface = $tokenStorageInterface;
        $decodedJwtToken = $this->jwtManager->decode($this->tokenStorageInterface->getToken());
        $this->user = $decodedJwtToken['username'];
    }


    #[Route('', methods: 'post', name: 'addressCreate')]
    public function store(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator)
    {
        $entityManager = $doctrine->getManager();
     
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $this->user]);

       

        try {

            $address = new Address();
            $address->setTitle($request->get('title'));
            $address->setAddress($request->get('address'));
            $address->setCity($request->get('city'));
            $address->setProvince($request->get('province'));
            $address->setUser($user);
            $errors = $validator->validate($address);
    
            if (count($errors) > 0) {
                return $this->getErrorMessages($errors);
            }

            $entityManager->persist($address);
            $entityManager->flush();

        } catch (Exception $e) {

            return $this->json([
                'message' => 'Something went wrong'
            ], 400);
        }

        return $this->json([
            'message' => 'Address Created',
            'address_id' => $address->getId()
        ], 201);
    }

    #[Route('', methods: 'put', name: 'addressUpdate')]
    public function update(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator)
    {

        $formData = json_decode($request->getContent());
        $entityManager = $doctrine->getManager();

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $this->user]);
        $address = $entityManager->getRepository(Address::class)->findOneBy(['id' => $formData->id, 'user' => $user->getId()]);
      
        if (!$address) {
                return $this->json([
                    'status' => 'error',
                    'data' => 'Address Not Found'
                ], 400);
        }
       
        try {

            $address->setTitle($formData->title);
            $address->setAddress($formData->address);
            $address->setCity($formData->city);
            $address->setProvince($formData->province);
            $address->setUser($user);
    
            $errors = $validator->validate($address);
    
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
            'message' => 'Address Updated',
            'address_id' => $address->getId()
        ], 200);
       
    }



    #[Route('/{id}', methods: 'delete', name: 'addressDelete')]
    public function delete(int $id, ManagerRegistry $doctrine)
    {
       
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $this->user]);
        $address = $entityManager->getRepository(Address::class)->findOneBy(['id' => $id, 'user' => $user->getId()]);

        if (!$address) {
            return $this->json([
                'status' => 'error',
                'data' => 'Address Not Found'
            ], 400);
        }

        try {

            $entityManager->remove($address);
            $entityManager->flush();

        } catch (Exception $e) {

            return $this->json([
                'message' => 'Something went wrong'
            ], 400);
        }

        return $this->json([
            'message' => 'Address Deleted',
        ], 200);
      
    }

    #[Route('', methods: 'get', name: 'addressReadAll')]
    public function readAll(ManagerRegistry $doctrine)
    {
      
        $entityManager = $doctrine->getManager();

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $this->user]);
        $address = $user->getAddresses();

        $response = [];

        foreach ($address as $item) {
            array_push($response, [
                'id' => $item->getId(),
                'title' => $item->getTitle(),
                'address' => $item->getAddress(),
                'city' => $item->getCity(),
                'province' => $item->getProvince(),
            ]);
        }

        return $this->json([
            'status' => 'ok',
            'data' => $response
        ]);
        
    }


    #[Route('/{id}', methods: 'get', name: 'addressRead')]
    public function read(int $id, ManagerRegistry $doctrine)
    {
       
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $this->user]);
        $address = $entityManager->getRepository(Address::class)->findOneBy(['id' => $id,'user' => $user->getId()]);

        if (!$address) {
            return $this->json([
                'message' => 'Address Not Found'
            ], 400);
        }

        $response = [
            'id' => $address->getId(),
            'title' => $address->getTitle(),
            'address' => $address->getAddress(),
            'city' => $address->getCity(),
            'province' => $address->getProvince(),
        ];


        return $this->json([
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
