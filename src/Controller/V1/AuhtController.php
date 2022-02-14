<?php

namespace App\Controller\V1;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


#[Route('auth', name: 'auth')]

class AuhtController extends AbstractController
{

    #[Route('/register', methods: 'post', name: 'register')]
    public function register(Request $request, UserPasswordHasherInterface $encoder, ManagerRegistry $doctrine, ValidatorInterface $validator)
    {
        /**
         * Bos veri hashPassword() metodu ile dolu hale geldigi icin Validator bu kısımda çalışmıyor
         */

        if (!$request->get('password')) {
            return $this->json([
                'message' => [
                    'password' => 'Password is Required'
                ]
            ], 422);
        }


        if ($request->get('password') !== $request->get('password_confirmation')) {
            return $this->json([
                'message' => [
                    'password' => 'Passwords do not match'
                ]
            ], 422);
        }


        $entityManager = $doctrine->getManager();

        $control = $entityManager->getRepository(User::class)->findOneBy(['email' => $request->get('email')]);

        if($control){
            return $this->json([
                'message' => [
                    'password' => 'This email is already registered'
                ]
            ], 400);
        }


        try {


            $user = new User();
            $user->setEmail($request->get('email'));
            $user->setPassword($encoder->hashPassword($user, $request->get('password')));
            $user->setRoles(['ROLE_USER']);

            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                return $this->getErrorMessages($errors);
            }

            $entityManager->persist($user);
            $entityManager->flush();

        } catch (Exception $e) {
            return $this->json([
                'message' => 'Something went wrong'
            ], 400);
        }


        return $this->json([
            'message' => 'User Created'
        ], 201);
    }



    #[Route('/admin/register', methods: 'post', name: 'adminRegister')]
    public function adminRegister(Request $request, UserPasswordHasherInterface $encoder, ManagerRegistry $doctrine, ValidatorInterface $validator)
    {

        /**
         * Bos veri hashPassword() metodu ile dolu hale geldigi icin Validator bu kısımda çalışmıyor
         */

        if (!$request->get('password')) {
            return $this->json([
                'messages' => [
                    'password' => 'Password is Required'
                ]
            ], 422);
        }

        if ($request->get('password') !== $request->get('password_conformation')) {
            return $this->json([
                'messages' => [
                    'password' => 'Passwords do not match'
                ]
            ], 422);
        }


        try {

            $user = new User();
            $user->setEmail($request->get('email'));
            $user->setPassword($encoder->hashPassword($user, $request->get('password')));
            $user->setRoles(['ROLE_ADMIN']);

            $errors = $validator->validate($user);

            if (count($errors) > 0) {
                return $this->getErrorMessages($errors);
            }

            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

        } catch (Exception $e) {

            return $this->json([
                'message' => 'Something went wrong'
            ], 400);

        }

        return $this->json([
            'message' => 'Admin Created'
        ], 201);

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
