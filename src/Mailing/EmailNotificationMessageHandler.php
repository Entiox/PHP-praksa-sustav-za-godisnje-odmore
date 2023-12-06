<?php
namespace App\Mailing;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler()]
class EmailNotificationMessageHandler
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function __invoke(EmailNotificationMessage $message)
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($message->getRecipient())
            ->subject($message->getSubject())
            ->text($message->getBody());

        $this->mailer->send($email);
    }
}
