<?php

namespace App\Entity;

use App\Entity\Enum\Role;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Email(message: 'The email {{ value }} is not a valid email.',)]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 20])]
    #[Assert\PositiveOrZero]
    private ?int $availableVacationDays = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Team $team = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: VacationRequest::class, orphanRemoval: true)]
    private Collection $vacationRequests;

    public function __construct()
    {
        $this->vacationRequests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
            $this->roles = $roles;

        return $this;
    }

    public function getAvailableVacationDays(): ?int
    {
        return $this->availableVacationDays;
    }

    public function setAvailableVacationDays(int $availableVacationDays): static
    {
        $this->availableVacationDays = $availableVacationDays;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, VacationRequest>
     */
    public function getVacationRequests(): Collection
    {
        return $this->vacationRequests;
    }

    public function addVacationRequest(VacationRequest $vacationRequest): static
    {
        if (!$this->vacationRequests->contains($vacationRequest)) {
            $this->vacationRequests->add($vacationRequest);
            $vacationRequest->setUser($this);
        }

        return $this;
    }

    public function removeVacationRequest(VacationRequest $vacationRequest): static
    {
        if ($this->vacationRequests->removeElement($vacationRequest)) {
            // set the owning side to null (unless already changed)
            if ($vacationRequest->getUser() === $this) {
                $vacationRequest->setUser(null);
            }
        }

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    #[Assert\Callback]
    public function validateRoleCombination(ExecutionContextInterface $context, mixed $payload): void
    {
        if(in_array(Role::WORKER->value, $this->getRoles()) && in_array(Role::PROJECT_MANAGER->value, $this->getRoles()) &&
            in_array(Role::TEAM_LEADER->value, $this->getRoles())) {
            $context->buildViolation('User cannot have team leader, project manager and worker roles all together')
                ->atPath('roles')
                ->addViolation();
        } else if(in_array(Role::WORKER->value, $this->getRoles()) && in_array(Role::PROJECT_MANAGER->value, $this->getRoles())) {
            $context->buildViolation('User cannot have both worker and project manager roles')
                ->atPath('roles')
                ->addViolation();
        } else if(in_array(Role::WORKER->value, $this->getRoles()) && in_array(Role::TEAM_LEADER->value, $this->getRoles())) {
            $context->buildViolation('User cannot have both worker and team leader roles')
                ->atPath('roles')
                ->addViolation();
        } else if(in_array(Role::TEAM_LEADER->value, $this->getRoles()) && in_array(Role::PROJECT_MANAGER->value, $this->getRoles())) {
            $context->buildViolation('User cannot have both team leader and project manager roles')
                ->atPath('roles')
                ->addViolation();
        }
    }

    #[Assert\Callback]
    public function validateIfTeamLeaderAlreadyInTeam(ExecutionContextInterface $context, mixed $payload): void
    {
        if(in_array(Role::TEAM_LEADER->value, $this->getRoles())) {
            foreach ($this->getTeam()->getUsers() as $user) {
                if (in_array(Role::TEAM_LEADER->value, $user->getRoles()) && $user !== $this) {
                    $context->buildViolation('This team already has a team leader')
                        ->atPath('roles')
                        ->addViolation();
    
                    return;
                }
            }
        }
    }

    #[Assert\Callback]
    public function validateIfProjectManagerAlreadyInTeam(ExecutionContextInterface $context, mixed $payload): void
    {
        if(in_array(Role::PROJECT_MANAGER->value, $this->getRoles())) {
            foreach ($this->getTeam()->getUsers() as $user) {
                if (in_array(Role::PROJECT_MANAGER->value, $user->getRoles()) && $user !== $this) {
                    $context->buildViolation('This team already has a project manager')
                        ->atPath('roles')
                        ->addViolation();
    
                    return;
                }
            }
        }
    }
}
