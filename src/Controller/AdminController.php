<?php

namespace App\Controller;

use App\Entity\Enum\Role;
use App\Entity\Team;
use App\Entity\User;
use App\Form\AddEmployeeType;
use App\Form\AddTeamType;
use App\Form\EditEmployeeType;
use App\Form\SearchUserType;
use App\Mailing\EmailNotificationMessage;
use App\Mapper\AdminMapper;
use App\Service\EmployeeFormHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SearchUserType::class);
        $form->handleRequest($request);

        $employees = AdminMapper::mapEmployees($entityManager->getRepository(User::class)->findAllEmployees());
        
        if ($form->isSubmitted() && $form->isValid()) {
            $employees = AdminMapper::mapEmployees($entityManager->getRepository(User::class)->findAllEmployeesByNames($form->get('fullName')->getData()));
        }
        return $this->render('admin/admin.html.twig', [
            'form' => $form,
            'employees' => $employees,
        ]);
    }

    #[Route('/admin/add_employee', name: 'app_add_employee', methods: ['GET', 'POST'])]
    public function addEmployee(Request $request, EmployeeFormHandler $employeeFormHandler, MessageBusInterface $bus): Response
    {
        $employee = new User();
        $form = $this->createForm(AddEmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $employeeFormHandler->handleEmployeeForm($form, $employee);

            $this->addFlash('success', 'Employee added successfully');
            return $this->redirectToRoute('app_admin');
        }
        return $this->render('admin/add-employee.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/edit_employee/{employeeId}', name: 'app_edit_employee', methods: ['GET', 'PATCH'])]
    public function editEmployee($employeeId, Request $request, EntityManagerInterface $entityManager, EmployeeFormHandler $employeeFormHandler): Response
    {
        $employee = $entityManager->getRepository(User::class)->find($employeeId);

        if (!$employee) {
            throw $this->createNotFoundException('Employee not found');
        } else if(in_array(Role::ADMIN->value, $employee->getRoles())) {
            return $this->redirectToRoute('app_admin');
        }
    
        $form = $this->createForm(EditEmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if($form->get("update")->isClicked()) {
                $employeeFormHandler->handleEmployeeForm($form, $employee);
                $this->addFlash('success', 'Employee edited successfully');
                return $this->redirectToRoute('app_admin');
            } else {
                return $this->redirectToRoute('app_delete_employee', ['employeeId' => $employeeId]);
            }
        }

        return $this->render('admin/edit-employee.html.twig', ['form' => $form->createView(), 'employeeId' => $employee->getId()]);
    }

    #[Route('/admin/delete_employee/{employeeId}', name: 'app_delete_employee', methods: ['GET', 'DELETE'])]
    public function deleteEmployee($employeeId, EntityManagerInterface $entityManager): Response
    {
        $employee = $entityManager->getRepository(User::class)->find($employeeId);

        if (!$employee) {
            throw $this->createNotFoundException('Employee not found');
        } else if(in_array(Role::ADMIN->value, $employee->getRoles())) {
            return $this->redirectToRoute('app_admin');
        }

        $entityManager->remove($employee);
        $entityManager->flush();
        $this->addFlash('success', 'Employee deleted successfully');

        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/add_team', name: 'app_add_team', methods: ['GET', 'POST'])]
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
}
