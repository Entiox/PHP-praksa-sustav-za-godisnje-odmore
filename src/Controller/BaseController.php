<?php

namespace App\Controller;

use App\Entity\Enum\Role;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BaseController extends AbstractController
{
    #[Route('/', name: 'app_base', methods: ['GET'])]
    public function index(): Response
    {
        if($this->getUser()) {
            if($this->isGranted(Role::ADMIN->value)) {
                return $this->redirectToRoute('app_admin');
            } else if($this->isGranted(Role::PROJECT_MANAGER->value) || $this->isGranted(Role::TEAM_LEADER->value)) {
                return $this->redirectToRoute('app_leader');
            } else {
                return $this->redirectToRoute('app_worker');
            }
        }
        return $this->render('base.html.twig');
    }
}
