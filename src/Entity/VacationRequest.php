<?php

namespace App\Entity;

use App\Controller\WorkerController;
use App\Repository\VacationRequestRepository;
use App\Util\DateWorker;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: VacationRequestRepository::class)]
class VacationRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    private const STATUSES = ['PENDING_BOTH', 'PENDING_TEAM_LEADER', 'PENDING_PROJECT_MANAGER', 'APPROVED', 'REJECTED'];

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\GreaterThanOrEqual('today', message: 'The starting date must be greater than or equal to current date.')]
    #[Assert\LessThan('first day of January next year', message: 'The starting date must be inside current year.')]
    private ?\DateTimeInterface $startingDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\GreaterThan(propertyPath: 'startingDate', message: 'The ending date must be greater than starting date.')]
    private ?\DateTimeInterface $endingDate = null;

    #[ORM\Column]
    #[Assert\Choice(choices: VacationRequest::STATUSES, message: 'Choose a valid status.')]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'vacationRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartingDate(): ?\DateTimeInterface
    {
        return $this->startingDate;
    }

    public function setStartingDate(\DateTimeInterface $startingDate): static
    {
        $this->startingDate = $startingDate;

        return $this;
    }

    public function getEndingDate(): ?\DateTimeInterface
    {
        return $this->endingDate;
    }

    public function setEndingDate(\DateTimeInterface $endingDate): static
    {
        $this->endingDate = $endingDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public static function getStatuses()
    {
        return self::STATUSES;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    #[Assert\Callback]
    public function validateWorkingDays(ExecutionContextInterface $context, mixed $payload)
    {
        $workingDays = DateWorker::calculateWorkingDays($this->startingDate, $this->endingDate);

        if($workingDays > $this->getUser()->getAvailableVacationDays())
        {
            $context->buildViolation('Requested vacation exceeds available vacation days.')
                ->addViolation();
        }
    }
}
