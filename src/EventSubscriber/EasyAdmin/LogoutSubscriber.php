<?php
namespace App\EventSubscriber\EasyAdmin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Psr\Log\LoggerInterface;
class LogoutSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => 'onLogout'];
    }

    public function onLogout(LogoutEvent $event): void
    {
        // get the security token of the session that is about to be logged out
        $user = $event->getToken()->getUser();
        $client_ip = $event->getRequest()->getClientIp();

        $this->logger->info("User logout: Account {user} has logged out. Client IP: {client_ip}", [
            'user' => $user->getEmail(),
            "client_ip" => $client_ip
        ]);
    }
}