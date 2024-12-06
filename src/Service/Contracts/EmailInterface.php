<?php

namespace App\Service\Contracts;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Email;

interface EmailInterface
{
    public function send(Email $email, ?Envelope $envelope=null): void;
}
