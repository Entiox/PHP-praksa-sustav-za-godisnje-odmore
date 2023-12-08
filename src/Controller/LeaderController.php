<?php

namespace App\Controller;

use App\Entity\Enum\Role;
use App\Entity\Enum\Status;
use App\Entity\User;
use App\Entity\VacationRequest;
use App\Event\VacationReuqestApproved\VacationRequestApprovedEvent;
use App\Form\SearchUserType;
use App\Mailing\EmailNotificationMessage;
use App\Mapper\LeaderMapper;
use App\Util\DateWorker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class LeaderController extends AbstractController
{
    #[Route('/leader', name: 'app_leader', methods: ['GET'])]
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
        }
        return $this->render('leader/leader.html.twig', [
            'form' => $form,
            'workers' => $workers,
            'team' => $leader->getTeam()->getName()
        ]);
    }

    #[Route('/leader/worker_vacation_details/{workerId}', name: 'app_worker_vacation_details', methods: ['GET'])]
    public function displayWorkerVacationDetails($workerId, EntityManagerInterface $entityManager): Response
    {
        $vacationRequests = $entityManager->getRepository(VacationRequest::class)->findApprovedAndOfCurrentYearByUserId($workerId);
        $vacationDates = LeaderMapper::mapVacationRequestsToDates($vacationRequests);
        return $this->render('leader/worker-vacation-details.html.twig', [
            'vacationDates' => $vacationDates,
        ]);
    }

    #[Route('/leader/pending_vacation_requests', name: 'app_pending_vacation_requests', methods: ['GET'])]
    public function displayPendingVacationRequests(EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $vacationRequests = in_array(Role::PROJECT_MANAGER->value, $user->getRoles()) ? 
            $entityManager->getRepository(VacationRequest::class)->findOfCurrentYearByStatusesAndTeam([Status::PENDING_BOTH->value,
                Status::PENDING_PROJECT_MANAGER->value], $user->getTeam()) : $entityManager->getRepository(VacationRequest::class)->
                findOfCurrentYearByStatusesAndTeam([Status::PENDING_BOTH->value, Status::PENDING_TEAM_LEADER->value], $user->getTeam());

        $mappedVacationRequests = LeaderMapper::mapVacationRequestsForUsers($vacationRequests);
        return $this->render('leader/pending-vacation-requests.html.twig', [
            'vacationRequests' => $mappedVacationRequests,
        ]);
    }

    #[Route('/leader/approve_vacation_request/{vacationRequestId}', name: 'app_approve_vacation_request', methods: ['GET', 'PUT'])]
    public function approveVacationRequest($vacationRequestId, EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher, MessageBusInterface $bus): Response
    {
        $vacationRequest = $entityManager->getRepository(VacationRequest::class)->find($vacationRequestId);
        $worker = $vacationRequest->getUser();
        $shouldEmailNotificationBeSent = $vacationRequest->getStatus === Status::PENDING_PROJECT_MANAGER || $vacationRequest->getStatus === Status::PENDING_TEAM_LEADER;
        $event = new VacationRequestApprovedEvent($worker, $this->getUser(), $vacationRequest);
        $eventDispatcher->dispatch($event);

        if($shouldEmailNotificationBeSent) {
            $bus->dispatch(new EmailNotificationMessage($worker->getEmail(), "Vacation request", 'Your vacation request has been approved.'));
        }

        $this->addFlash('success', 'Vacation request approved');

        return $this->redirectToRoute('app_pending_vacation_requests');
    }

    #[Route('/leader/reject_vacation_request/{vacationRequestId}', name: 'app_reject_vacation_request', methods: ['GET', 'PUT'])]
    public function rejectVacationRequest($vacationRequestId, EntityManagerInterface $entityManager, MessageBusInterface $bus): Response
    {

        $vacationRequest = $entityManager->getRepository(VacationRequest::class)->find($vacationRequestId);
        $worker = $vacationRequest->getUser();

        $vacationRequest->setStatus(Status::REJECTED->value);
        $entityManager->persist($vacationRequest);
        $entityManager->flush();

        $bus->dispatch(new EmailNotificationMessage($worker->getEmail(), "Vacation request", 'Your vacation request has been rejected.'));

        $this->addFlash('success', 'Vacation request rejected');
        
        return $this->redirectToRoute('app_pending_vacation_requests');
    }
}
