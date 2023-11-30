<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Form\AddEmployeeType;
use App\Form\AddTeamType;
use App\Form\EditEmployeeType;
use App\Form\SearchUserType;
use App\Mapper\AdminMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SearchUserType::class);
        $form->handleRequest($request);

        $employees = AdminMapper::mapEmployees($entityManager->getRepository(User::class)->findAllEmployees());
        
        if ($form->isSubmitted() && $form->isValid()) {
            $employees = AdminMapper::mapEmployees($entityManager->getRepository(User::class)->findAllEmployeesByNames($form->get('fullName')->getData()));
            return $this->render('admin/admin.html.twig', [
                'form' => $form,
                'employees' => $employees,
            ]);
        }
        return $this->render('admin/admin.html.twig', [
            'form' => $form,
            'employees' => $employees,
        ]);
    }

    #[Route('/admin/add_employee', name: 'app_add_employee')]
    public function addEmployee(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $employee = new User();
        $form = $this->createForm(AddEmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $employee->setPassword(
                $userPasswordHasher->hashPassword(
                    $employee,
                    $form->get('plainPassword')->getData()
                )
            );
            $employee->setAvailableVacationDays(20);
            $entityManager->persist($employee);
            $entityManager->flush();
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/add-employee.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/edit_employee/{employeeId}', name: 'app_edit_employee')]
    public function editEmployee($employeeId, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $employee = $entityManager->getRepository(User::class)->find($employeeId);

        if (!$employee)
        {
            throw $this->createNotFoundException('Employee not found');
        }
        else if(in_array('ROLE_ADMIN', $employee->getRoles()))
        {
            return $this->redirectToRoute('app_admin');
        }
    
        $form = $this->createForm(EditEmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $employee->setUsername($form->get('username')->getData());
            $newPassword = $form->get('plainPassword')->getData();
            if ($newPassword) {
                $employee->setPassword(
                    $userPasswordHasher->hashPassword($employee, $newPassword)
                );
            }
            $entityManager->persist($employee);
            $entityManager->flush();
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/edit-employee.html.twig', ['form' => $form->createView(), 'employeeId' => $employee->getId()]);
    }

    #[Route('/admin/delete_employee/{employeeId}', name: 'app_delete_employee')]
    public function deleteEmployee($employeeId, EntityManagerInterface $entityManager): Response
    {
        $employee = $entityManager->getRepository(User::class)->find($employeeId);

        if (!$employee) {
            throw $this->createNotFoundException('Employee not found');
        }
        else if(in_array('ROLE_ADMIN', $employee->getRoles()))
        {
            return $this->redirectToRoute('app_admin');
        }

        $entityManager->remove($employee);
        $entityManager->flush();
        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/add_team', name: 'app_add_team')]
    public function addTeam(Request $request, EntityManagerInterface $entityManager): Response
    {
        $team = new Team();
        $form = $this->createForm(AddTeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($team);
            $entityManager->flush();
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/add-team.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/cannot_modify_admin', name: 'app_admin_cannot_modify_admin')]
    public function displayCannotModifyAdminError(): Response
    {
        return $this->render('admin/cannot-modify-admin.html.twig');
    }
}
