<?php

namespace App\Security\Http\Permissions;

use App\Entity\Application;
use App\Model\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ApplicationVoter extends Voter
{

    final public const APPLICATION_VIEW_CONFIDI = 'application_view_confidi';

    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }


    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::APPLICATION_VIEW_CONFIDI && $subject instanceof Application;
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }


        if ($this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN') || $this->authorizationChecker->isGranted('ROLE_ADMIN') || $this->authorizationChecker->isGranted('ROLE_OPERATORE_ARTIGIANCASSA')) {
            return true;
        }

        if (
            $this->authorizationChecker->isGranted('ROLE_APPLICATION_INDEX') ||
            $this->authorizationChecker->isGranted('ROLE_APPLICATION_DETAIL') ||
            $this->authorizationChecker->isGranted('ROLE_APPLICATION_DELETE')
        ) {
            /** @var Application $subject */
            return $subject->getConfidi()->getId() == $user->getConfidi()->getId();
        }

        return false;
    }
}
