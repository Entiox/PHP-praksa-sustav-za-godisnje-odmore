<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:worker:refresh-vacation-days',
    description: 'Command for renewing available vacation days',
)]
class RefreshAvailableVacationDaysCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();

        foreach($users as $user)
        {
            $user->setAvailableVacationDays(20);
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();

        $io = new SymfonyStyle($input, $output);
        $io->success('Vacation days refreshed.');

        return Command::SUCCESS;
    }
}
