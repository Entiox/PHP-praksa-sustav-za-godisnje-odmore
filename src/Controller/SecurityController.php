<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Mapper\UserMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if($this->getUser()) {
            if($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_admin');
            } else if($this->isGranted('ROLE_PROJECT_MANAGER') || $this->isGranted('ROLE_TEAM_LEADER')) {
                return $this->redirectToRoute('app_leader');
            } else {
                return $this->redirectToRoute('app_worker');
            }
        }
        
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/personal/change_password', name: 'app_change_password', methods: ['GET', 'PATCH'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $data = $form->getData();
            $oldPassword = $data['oldPassword'];
            $newPassword = $data['newPassword'];

            if ($passwordHasher->isPasswordValid($user, $oldPassword)) {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('app_logout');
            } else {
                $this->addFlash('error', 'Incorrect old password.');
            }
        }

        return $this->render('common/password-change.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/personal/personal_data', name: 'app_personal_data', methods: ['GET'])]
    public function personalData(EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        return $this->render('common/personal-data.html.twig', ['data' => UserMapper::mapPersonalData($user)]);
    }
}
