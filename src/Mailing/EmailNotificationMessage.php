<?php
namespace App\Mailing;

class EmailNotificationMessage
{
    public function __construct(private string $recipient, private string $subject, private string $body)
    {
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
