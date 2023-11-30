<?php

namespace App\Controller;

use App\Entity\VacationRequest;
use App\Form\VacationRequestType;
use App\Mapper\WorkerMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WorkerController extends AbstractController
{
    #[Route('/worker', name: 'app_worker')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $worker */
        $worker = $this->getUser();

        $vacationRequests = $worker->getVacationRequests();
        $vacationData = WorkerMapper::mapVacationData($worker->getAvailableVacationDays(), $vacationRequests);
       
        return $this->render('worker/worker.html.twig', [
            'vacationData' => $vacationData,
        ]);
    }

    #[Route('/worker/request_vacation', name: 'app_request_vacation')]
    public function requestVacation(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $worker */
        $worker = $this->getUser();

        $vacationRequest = new VacationRequest();

        foreach($worker->getVacationRequests() as $vacationRequest)
        {
            if($vacationRequest->getStatus() === 'PENDING_BOTH' || $vacationRequest->getStatus() === 'PENDING_PROJECT_MANAGER'
            || $vacationRequest->getStatus() === 'PENDING_TEAM_LEADER')
            {
                return $this->redirectToRoute('app_vacation_request_already_sent');
            }
        }

        $vacationRequest->setUser($worker);
        $vacationRequest->setStatus('PENDING_BOTH');
        $form = $this->createForm(VacationRequestType::class, $vacationRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vacationRequest);
            $entityManager->flush();
            return $this->redirectToRoute('app_worker');
        }
        return $this->render('worker/vacation-request.html.twig', ['form' => $form]);
    }

    #[Route('/worker/vacation_request_already_sent', name: 'app_vacation_request_already_sent')]
    public function vacationRequestAlreadySent(): Response
    {
        return $this->render('worker/vacation-request-already-sent.html.twig');
    }
}
