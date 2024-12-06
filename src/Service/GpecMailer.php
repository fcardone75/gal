<?php

namespace App\Service;

use App\Service\Contracts\EmailInterface;
use Bni\Gpec\Gpec\GpecService;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Email;

class GpecMailer implements EmailInterface
{

    public function __construct()
    {
    }

    public function send(Email $email, ?Envelope $envelope = null): void
    {
        $gpecEnvPath = $_ENV['GPEC_ENV_UP_FOLDER']; // Ideally fetch from parameters or env
        $this->gpecService = new GpecService($gpecEnvPath);
        // Itera tutti i destinatari e invia la mail
        foreach ($email->getTo() as $to) {
            $this->gpecService->sendMail($to->getAddress(), $email->getSubject(), $email->getHtmlBody());
        }
    }
}
