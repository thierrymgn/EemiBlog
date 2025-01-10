<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserBannedListener
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        $allowedPaths = [
            '/banned',
            '/logout',
            '/login',
        ];

        if (in_array($path, $allowedPaths)) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();

        if ($user && $user->isBanned()) {
            $response = new RedirectResponse($this->urlGenerator->generate('app_banned'));
            $event->setResponse($response);
        }
    }
}
