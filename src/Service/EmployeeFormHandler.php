<?php
namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EmployeeFormHandler
{
    public function __construct(private EntityManagerInterface $entityManager, private UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    public function handleEmployeeForm(Form $form, User $employee)
    {
        $password = $form->get('plainPassword')->getData();
        if ($password) {
            $employee->setPassword(
                $this->userPasswordHasher->hashPassword($employee, $password)
            );
        }
        $employee->setAvailableVacationDays(20);
        $this->entityManager->persist($employee);
        $this->entityManager->flush();
    }
}
