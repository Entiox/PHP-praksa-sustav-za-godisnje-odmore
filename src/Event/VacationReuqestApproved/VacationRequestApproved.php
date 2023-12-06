<?php
namespace App\Event\VacationReuqestApproved;

use App\Entity\User;
use App\Entity\VacationRequest;
use Symfony\Contracts\EventDispatcher\Event;

class VacationRequestApprovedEvent extends Event
{
    public function __construct(private User $worker, private User $leader, private VacationRequest $vacationRequest)
    {
    }

    public function getWorker(): User
    {
        return $this->worker;
    }

    public function getLeader(): User
    {
        return $this->leader;
    }

    public function getVacationRequest(): VacationRequest
    {
        return $this->vacationRequest;
    }
}
