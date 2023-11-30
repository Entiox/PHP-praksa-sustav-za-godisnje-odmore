<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BaseController extends AbstractController
{
    #[Route('/', name: 'app_base')]
    public function index(): Response
    {
        if($this->getUser())
        {
            if($this->isGranted('ROLE_ADMIN'))
            {
                return $this->redirectToRoute('app_admin');
            }
            else if($this->isGranted('ROLE_PROJECT_MANAGER') || $this->isGranted('ROLE_TEAM_LEADER'))
            {
                return $this->redirectToRoute('app_leader');
            }
            else
            {
                return $this->redirectToRoute('app_worker');
            }
        }
        return $this->render('base.html.twig');
    }
}
