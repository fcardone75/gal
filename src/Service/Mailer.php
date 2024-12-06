<?php


namespace App\Service;


use App\Entity\ApplicationMessage;
use App\Entity\FinancingProvisioningCertification;
use App\Model\UserInterface;
use App\Service\Contracts\MailerInterface;
use App\Controller\Admin\ApplicationCrudController;
use App\Entity\User;
use App\Service\Contracts\Security\RoleProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class Mailer implements MailerInterface
{
    /**
     * @var \Symfony\Component\Mailer\MailerInterface
     */
    private $mailer;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var AdminUrlGenerator
     */
    private $adminUrlGenerator;

    /**
     * @var TranslatorInterface
     */
    private $translator;



    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RoleProviderInterface
     */
    private $roleProvider;

    /**
     * @var array
     */
    private $options;

    /**
     * @var LoggerInterface
     */
    private $logger;


    public function __construct(
        GpecMailer             $mailer,
        Environment            $twig,
        UrlGeneratorInterface  $urlGenerator,
        AdminUrlGenerator      $adminUrlGenerator,
        TranslatorInterface    $translator,
        EntityManagerInterface $entityManager,
        RoleProviderInterface  $roleProvider,
        LoggerInterface        $logger,
                               $options = []
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->roleProvider = $roleProvider;
        $this->options = $options;
        $this->logger = $logger;
    }

    public function sendResetPasswordEmail(UserInterface $user)
    {
        $action = 'reset_password';

        if (!$this->hasConfiguredTemplateForAction($action)) {
            $this->logMissingTemplateForAction($action);
            return;
        }

        if (!$this->hasConfiguredSenderForAction($action)) {
            $this->logMissingSenderForAction($action);
            return;
        }

        if (!$user->getResetPasswordToken()) {
            $this->logger->error('User "{{user}}" has no reset password token', [
                'user' => $user->getId()
            ]);
            return;
        }

        try {
            $email = new Email();
            $templateVars = [
                'user' => $user,
                'url' => $this->urlGenerator->generate(
                    'security_reset_password_reset',
                    [
                        'token' => $user->getResetPasswordToken()
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ];
            $email
                ->subject($this->translator->trans('mail.reset_password.subject'))
                ->from($this->getConfiguredSenderForAction($action))
                ->html($this->twig->render($this->getConfiguredTemplateForAction($action), $templateVars))
                ->to($user->getUsername());
            if ($txtTemplate = $this->getFallbackTextTemplateForAction($action)) {
                $email->text($this->twig->render($txtTemplate, $templateVars));
            }
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            $this->logMessageCreationException($e);
            return;
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logSendException($e);
            return;
        }
    }

    public function sendApplicationMessageNotification(ApplicationMessage $applicationMessage)
    {
        $action = 'application_message_added';

        if (!$this->hasConfiguredTemplateForAction($action)) {
            $this->logMissingTemplateForAction($action);
            return;
        }

        if (!$this->hasConfiguredSenderForAction($action)) {
            $this->logMissingSenderForAction($action);
            return;
        }

        $application = $applicationMessage->getApplication();
        $applicationCreatedBy = $application->getCreatedBy();
        $applicationMessageCreatedBy = $applicationMessage->getCreatedBy();

        if($applicationMessageCreatedBy == null) {
            return;
        }

        if($this->roleProvider->userHasRoleArtigiancassa($applicationMessageCreatedBy)) {
            if($applicationCreatedBy->getConfidi() != null) {
                $confidiUsers = $this->entityManager->getRepository(User::class)->findConfidiUsersByConfidiId($applicationCreatedBy->getConfidi()->getId());
                $to = array_map(function (User $user) {
                    return $user->getEmail();
                }, $confidiUsers);
            } else {
                $to = [$applicationCreatedBy->getEmail()];
            }
        } else if($this->roleProvider->userHasRoleConfidi($applicationMessageCreatedBy)) {
            $artigiancassaUsers = $this->entityManager->getRepository(User::class)->findByRole("ROLE_OPERATORE_ARTIGIANCASSA");
            $to = array_map(function (User $user){
                return $user->getEmail();
            }, $artigiancassaUsers);
        } else {
            $to = [$applicationCreatedBy->getEmail()];
        }

        $to = $this->interceptMailNotInProduction($to);
        $bcc = [$this->options['redirect']['recipient_target']];

        try {
            $email = new Email();
            $url = $this->adminUrlGenerator
                ->setController(ApplicationCrudController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($application->getId())
                ->generateUrl();
            $templateVars = [
                'url' => $url
            ];
            $email
                ->subject($this->translator->trans('mail.application_message.subject', [
                    'practiceId' => $application->getPracticeId()
                ]))
                ->from($this->getConfiguredSenderForAction($action))
                ->html($this->twig->render($this->getConfiguredTemplateForAction($action), $templateVars))
                ->to(...$to)
                ->bcc(...$bcc)
            ;
            if ($txtTemplate = $this->getFallbackTextTemplateForAction($action)) {
                $email->text($this->twig->render($txtTemplate, $templateVars));
            }
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            $this->logMessageCreationException($e);
            return;
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logSendException($e);
            return;
        }
    }

    public function sendUserCreatedEmail(UserInterface $user)
    {
        $action = 'user_created';

        if (!$this->hasConfiguredTemplateForAction($action)) {
            // log error
            $this->logMissingTemplateForAction($action);
            return;
        }

        if (!$this->hasConfiguredSenderForAction($action)) {
            // log error
            $this->logMissingSenderForAction($action);
            return;
        }

        try {
            $email = new Email();
            $templateVars = [
                'user' => $user
            ];
            $email
                ->subject($this->translator->trans('mail.user_created.subject'))
                ->from($this->getConfiguredSenderForAction($action))
                ->html($this->twig->render($this->getConfiguredTemplateForAction($action), $templateVars))
                ->to($user->getUsername());
            if ($txtTemplate = $this->getFallbackTextTemplateForAction($action)) {
                $email->text($this->twig->render($txtTemplate, $templateVars));
            }
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            $this->logMessageCreationException($e);
            return;
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logSendException($e);
            return;
        }
    }

    public function sendNsiaErrorMessageNotification(string $message)
    {
        $action = 'nsia_error_message';

        if (!$this->hasConfiguredTemplateForAction($action)) {
            $this->logMissingTemplateForAction($action);
            return;
        }

        if (!$this->hasConfiguredSenderForAction($action)) {
            $this->logMissingSenderForAction($action);
            return;
        }

        $recipients = $this->getConfiguredRecipientsForAction($action);
        $to = $recipients['to'] ?? [];
        $bcc = $recipients['bcc'] ?? [];
        $to = $this->interceptMailNotInProduction($to);
        try {
            $email = new Email();
            $templateVars = [
                'message' => $message
            ];
            $email
                ->subject($this->translator->trans('mail.nsia_error_message.subject'))
                ->from($this->getConfiguredSenderForAction($action))
                ->html($this->twig->render($this->getConfiguredTemplateForAction($action), $templateVars))
                ->to(...$to)
                ->bcc(...$bcc);

            if ($txtTemplate = $this->getFallbackTextTemplateForAction($action)) {
                $email->text($this->twig->render($txtTemplate, $templateVars));
            }
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            $this->logMessageCreationException($e);
            return;
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logSendException($e);
            return;
        }
    }

    public function sendFPCertificationNotification(FinancingProvisioningCertification $financingProvisioningCertification)
    {
        $action = 'fpc_notification';

        if (!$this->hasConfiguredTemplateForAction($action)) {
            $this->logMissingTemplateForAction($action);
            return;
        }

        if (!$this->hasConfiguredSenderForAction($action)) {
            $this->logMissingSenderForAction($action);
            return;
        }

        $recipients = $this->getConfiguredRecipientsForAction($action);
        $to = $recipients['to'] ?? [];
        $to = $this->interceptMailNotInProduction($to);
        try {
            $email = new Email();
            $templateVars = [
                'FPCertification' => $financingProvisioningCertification,
            ];
            $email->getHeaders()->addTextHeader('X-Transport', 'pec');
            $email
                ->subject($this->translator->trans('mail.fpc_notification.subject', [
                    'application_number' => $financingProvisioningCertification->getApplication()->getPracticeId()
                ]))
                ->from($this->getConfiguredSenderForAction($action))
                ->html($this->twig->render($this->getConfiguredTemplateForAction($action), $templateVars))
                ->to(...$to);

            if ($txtTemplate = $this->getFallbackTextTemplateForAction($action)) {
                $email->text($this->twig->render($txtTemplate, $templateVars));
            }
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            $this->logMessageCreationException($e);
            return;
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logSendException($e);
            return;
        }
    }

    protected function hasConfiguredTemplateForAction($action): bool
    {
        return isset($this->options['templates']) && isset($this->options['templates'][$action]);
    }

    protected function getConfiguredTemplateForAction($action): ?string
    {
        if ($this->hasConfiguredTemplateForAction($action)) {
            return $this->options['templates'][$action];
        }
        return null;
    }

    protected function hasConfiguredSenderForAction($action): bool
    {
        return isset($this->options['sender']) && isset($this->options['sender'][$action]);
    }

    protected function getConfiguredSenderForAction($action): ?string
    {
        if ($this->hasConfiguredSenderForAction($action)) {
            return $this->options['sender'][$action];
        }
        return null;
    }

    protected function hasConfiguredRecipientsForAction($action): bool
    {
        return isset($this->options['recipients']) && isset($this->options['recipients'][$action]);
    }

    protected function getConfiguredRecipientsForAction($action): ?array
    {
        if ($this->hasConfiguredRecipientsForAction($action)) {
            return $this->options['recipients'][$action];
        }
        return null;
    }

    protected function isHtmlTemplate($template): bool
    {
        return (bool) preg_match('/^.*\.html\.twig$/', $template);
    }

    protected function getFallbackTextTemplateForAction($action): ?string
    {
        if ($this->hasConfiguredTemplateForAction($action) &&
            (($template = $this->getConfiguredTemplateForAction($action)) && $this->isHtmlTemplate($template))) {
            $templatePlaintext = preg_replace('/\.html.twig$/', '.txt.twig', $template);
            return $this->templateFileExists($templatePlaintext) ? $templatePlaintext : null;
        }
        return null;
    }

    protected function templateFileExists($template): bool
    {
        return $this->twig->getLoader()->exists($template);

    }

    protected function logMissingTemplateForAction(string $action)
    {
        $this->logger->warning('No configured template for action {{action}}', [
            'action' => $action
        ]);
    }

    protected function logMissingSenderForAction(string $action)
    {
        $this->logger->warning('No configured sender for action {{action}}', [
            'action' => $action
        ]);
    }

    protected function logMessageCreationException(\Exception $e)
    {
        $this->logger->error('Unable to create mail object due to an exception', ['exception' => $e]);
    }

    protected function logSendException(TransportExceptionInterface $e)
    {
        $this->logger->error('Unable to send mail due to an exception', ['exception' => $e]);
    }

    /**
     * @param array $to
     * @return array
     */
    protected function interceptMailNotInProduction(array $to): array
    {
        return $this->options['redirect']['flag'] === 'yes' ? [$this->options['redirect']['recipient_target']] : $to;
    }

}
