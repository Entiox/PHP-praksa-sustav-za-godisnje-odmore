<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\VacationRequest;
use App\Form\SearchUserType;
use App\Mapper\LeaderMapper;
use App\Util\DateWorker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LeaderController extends AbstractController
{
    #[Route('/leader', name: 'app_leader')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $leader */
        $leader = $this->getUser();
        
        $form = $this->createForm(SearchUserType::class);
        $form->handleRequest($request);

        $workers = LeaderMapper::mapWorkers($entityManager->getRepository(User::class)->findAllWorkersInTeam($leader->getTeam()));
        
        if ($form->isSubmitted() && $form->isValid()) {
            $workers = LeaderMapper::mapWorkers($entityManager->getRepository(User::class)->findAllWorkersInTeamByName($leader->getTeam(),
                $form->get('fullName')->getData()));
            return $this->render('leader/leader.html.twig', [
                'form' => $form,
                'workers' => $workers,
                'team' => $leader->getTeam()->getName()
            ]);
        }
        return $this->render('leader/leader.html.twig', [
            'form' => $form,
            'workers' => $workers,
            'team' => $leader->getTeam()->getName()
        ]);
    }

    #[Route('/leader/worker_vacation_details/{workerId}', name: 'app_worker_vacation_details')]
    public function displayWorkerVacationDetails($workerId, EntityManagerInterface $entityManager): Response
    {
        $vacationRequests = $entityManager->getRepository(VacationRequest::class)->findApprovedAndOfCurrentYearByUserId($workerId);
        $vacationDates = LeaderMapper::mapVacationRequestsToDates($vacationRequests);
        return $this->render('leader/worker-vacation-details.html.twig', [
            'vacationDates' => $vacationDates,
        ]);
    }

    #[Route('/leader/pending_vacation_requests', name: 'app_pending_vacation_requests')]
    public function displayPendingVacationRequests(EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $vacationRequests = in_array('ROLE_PROJECT_MANAGER', $user->getRoles()) ? 
            $entityManager->getRepository(VacationRequest::class)->findOfCurrentYearByStatusesAndTeam(['PENDING_BOTH', 'PENDING_PROJECT_MANAGER'], $user->getTeam()) :
            $entityManager->getRepository(VacationRequest::class)->findOfCurrentYearByStatusesAndTeam(['PENDING_BOTH', 'PENDING_TEAM_LEADER'], $user->getTeam());

        $mappedVacationRequests = LeaderMapper::mapVacationRequestsForUsers($vacationRequests);
        return $this->render('leader/pending-vacation-requests.html.twig', [
            'vacationRequests' => $mappedVacationRequests,
        ]);
    }

    #[Route('/leader/approve_vacation_request/{workerId}/{vacationRequestId}', name: 'app_approve_vacation_request')]
    public function approveVacationRequest($workerId, $vacationRequestId, EntityManagerInterface $entityManager): Response
    {
        $worker = $entityManager->getRepository(User::class)->find($workerId);
        $vacationRequest = $entityManager->getRepository(VacationRequest::class)->find($vacationRequestId);

        $entityManager->beginTransaction();
        if($vacationRequest->getStatus() === 'PENDING_BOTH')
        {
            if(in_array('ROLE_PROJECT_MANAGER', $this->getUser()->getRoles()))
            {
                $vacationRequest->setStatus('PENDING_TEAM_LEADER');
            }
            else
            {
                $vacationRequest->setStatus('PENDING_PROJECT_MANAGER');
            }
        }
        else {
            $vacationRequest->setStatus('APPROVED');
            $worker->setAvailableVacationDays($worker->getAvailableVacationDays() - 
                DateWorker::calculateWorkingDays($vacationRequest->getStartingDate(), $vacationRequest->getEndingDate()));
        }
        $entityManager->persist($vacationRequest);
        $entityManager->persist($worker);
        $entityManager->flush();
        $entityManager->commit();
        return $this->redirectToRoute('app_pending_vacation_requests');
    }

    #[Route('/leader/reject_vacation_request/{vacationRequestId}', name: 'app_reject_vacation_request')]
    public function rejectVacationRequest($vacationRequestId, EntityManagerInterface $entityManager): Response
    {
        $vacationRequest = $entityManager->getRepository(VacationRequest::class)->find($vacationRequestId);
        $vacationRequest->setStatus('REJECTED');
        $entityManager->persist($vacationRequest);
        $entityManager->flush();
        return $this->redirectToRoute('app_pending_vacation_requests');
    }
}
