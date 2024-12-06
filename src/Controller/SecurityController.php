<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ResetPasswordType;
use App\Form\UpdatePasswordType;
use App\Model\UserInterface;
use App\Service\Contracts\Security\SecurityManagerInterface;
use App\Service\Contracts\MailerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    /**
     * @var FirewallMap
     */
    protected $firewallMap;

    public function __construct(
        FirewallMap $firewallMap
    ) {
        $this->firewallMap = $firewallMap;
    }

    /**
     * @Route("/login", name="app_login")
     */
    #[Route('/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils
    ): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('admin');
         }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@EasyAdmin/page/login.html.twig', [
            'page_title' => '<img src="/images/logo.png">',
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token_intention' => 'authenticate',
            'username_parameter' => 'email',
            'password_parameter' => 'password'
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @param EventDispatcherInterface $eventDispatcher
     * @param SessionInterface $session
     * @param PasswordHasherFactoryInterface $passwordHasherFactory
     * @param TranslatorInterface $translator
     * @param $token
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Route("/update-password/{token}", name="security_update_password")
     */
    public function updatePassword(
        EntityManagerInterface         $entityManager,
        Request                        $request,
        TokenStorageInterface          $tokenStorage,
        EventDispatcherInterface       $eventDispatcher,
        SessionInterface               $session,
        PasswordHasherFactoryInterface $passwordHasherFactory,
        TranslatorInterface            $translator,
                                       $token
    ) {
        $user = $entityManager->getRepository(User::class)->findOneBy(['updatePasswordToken' => $token]);

        if (!$user) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(UpdatePasswordType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUpdatePasswordToken(null);
            $passwordHasher = $passwordHasherFactory->getPasswordHasher($user);
            $user->setPassword($passwordHasher->hash($user->getPlainPassword(), $user->getSalt()));

            $entityManager->flush();

            $targetPath = '/';

            if (($firewallConfig = $this->firewallMap->getFirewallConfig($request)) &&
                $firewallConfig->getName()) {
                $targetPath = $session->get(implode('.', ['_security', $firewallConfig->getName(), 'target_path']), $targetPath);
            }

            $this->addFlash('success', $translator->trans('flash_messages.update_password.success'));

            return $this->redirect($targetPath ?? '/');
        }

        return $this->render('security/update_password.html.twig', [
            'form' => $form->createView(),
            'page_title' => '<img src="/images/logo.png"/>'
        ]);
    }

    /**
     * @param Request $request
     * @param TokenStorageInterface $tokenStorage
     * @param PasswordHasherFactoryInterface $passwordHasherFactory
     * @return Response
     * @Route(path="/change-password", name="security_change_password")
     */
    public function changePassword(
        Request                         $request,
        TokenStorageInterface           $tokenStorage,
        EntityManagerInterface          $entityManager,
        SecurityManagerInterface        $securityManager,
        TranslatorInterface             $translator,
    ): Response
    {
        /** @var UserInterface $user */
        $user = $tokenStorage->getToken()->getUser();

        $form = $this->createForm(ChangePasswordType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $securityManager->resetUserPassword($user);

            $entityManager->flush();
            $this->addFlash('success', $translator->trans('flash_messages.update_password.success'));
            return $this->redirectToRoute('admin');
        }

        return $this->render('security/change_password.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @param SecurityManagerInterface $securityManager
     * @param TranslatorInterface $translator
     * @param MailerInterface $mailer
     * @param Request $request
     * @return Response
     * @Route(path="/reset-password/request", name="security_reset_password_request")
     */
    public function requestResetPassword(
        SecurityManagerInterface $securityManager,
        TranslatorInterface $translator,
        MailerInterface $mailer,
        Request $request
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Email'
                ],
                'row_attr' => [
                    'class' => 'form-widget'
                ],
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'security.form.reset_password_request.submit',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg btn-block'
                ],
                'row_attr' => [
                    'class' => 'submit'
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            $user = $securityManager->loadUser($email);

            if (!$user) {
                $this->addFlash(
                    'success',
                    $translator->trans('flash_messages.reset_password_request.success', [
                        'login_url' => $this->generateUrl('app_login')
                    ])
                );
            } else {
                try {
                    $securityManager->prepareUserForResetPassword($user);
                } catch (\LogicException $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->redirect($this->generateUrl('security_reset_password_request'));
                }
                $mailer->sendResetPasswordEmail($user);
                $this->addFlash(
                    'success',
                    $translator->trans('flash_messages.reset_password_request.success', [
                        'login_url' => $this->generateUrl('app_login')
                    ])
                );
                return $this->redirect($this->generateUrl('security_reset_password_request'));
            }
        }

        return $this->render('security/request_reset_password.html.twig', [
            'form' => $form->createView(),
            'page_title' => '<img src="/images/logo.png"/>'
        ]);
    }

    /**
     * @param TranslatorInterface $translator
     * @param SecurityManagerInterface $securityManager
     * @param Request $request
     * @param $token
     * @return Response
     * @Route(path="/reset-password/reset/{token}", name="security_reset_password_reset")
     */
    public function resetPassword(
        TranslatorInterface $translator,
        SecurityManagerInterface $securityManager,
        ManagerRegistry $managerRegistry,
        Request $request,
        $token
    ) {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        $user = $managerRegistry->getRepository(User::class)->findOneBy([
            'resetPasswordToken' => $token
        ]);

        if (!$user) {
            throw $this->createNotFoundException($translator->trans('security.exception.reset_password_token_not_found'));
        }

        $form = $this->createForm(ResetPasswordType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $securityManager->resetUserPassword($user);

            $this->addFlash('success', $translator->trans('flash_messages.reset_password_reset.success'));

            return $this->redirect($this->generateUrl('app_login'));
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
            'page_title' => '<img src="/images/logo.png"/>'
        ]);
    }
}
