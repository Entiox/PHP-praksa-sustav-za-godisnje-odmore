<?php
namespace App\Event\VacationReuqestApproved;

use App\Entity\Enum\Role;
use App\Entity\Enum\Status;
use App\Util\DateWorker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: VacationRequestApprovedEvent::class, method: 'onVacationRequestApproved')]
class VacationRequestApprovedListener
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onVacationRequestApproved(VacationRequestApprovedEvent $event)
    {
        $worker = $event->getWorker();
        $vacationRequest = $event->getVacationRequest();

        $this->entityManager->beginTransaction();
        if($vacationRequest->getStatus() === Status::PENDING_BOTH->value) {
            if(in_array(Role::PROJECT_MANAGER->value, $event->getLeader()->getRoles())) {
                $vacationRequest->setStatus(Status::PENDING_TEAM_LEADER->value);
            } else {
                $vacationRequest->setStatus(Status::PENDING_PROJECT_MANAGER->value);
            }
        }
        else {
            $vacationRequest->setStatus(Status::APPROVED->value);
            $worker->setAvailableVacationDays($worker->getAvailableVacationDays() - 
                DateWorker::calculateWorkingDays($vacationRequest->getStartingDate(), $vacationRequest->getEndingDate()));
        }
        $this->entityManager->persist($vacationRequest);
        $this->entityManager->persist($worker);
        $this->entityManager->flush();
        $this->entityManager->commit();
    }
}
