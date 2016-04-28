<?php

namespace Netgen\Bundle\EzSocialConnectBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use eZ\Publish\Core\MVC\Symfony\Event\InteractiveLoginEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;

class InteractiveLoginListener implements EventSubscriberInterface
{

    /** @var  \Doctrine\ORM\EntityManagerInterface */
    protected $entityManager;

    /** @var \Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper */
    protected $loginHelper;

    /** @var  \Symfony\Component\HttpFoundation\Session\SessionInterface */
    protected $session;

    /**
     * InteractiveLoginListener constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper $loginHelper
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        SocialLoginHelper $loginHelper,
        SessionInterface $session
    )
    {
        $this->entityManager = $entityManager;
        $this->loginHelper = $loginHelper;
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin'
        );
    }

    /**
     * Authenticates external user, or creates new eZ user if one does not already exist
     * If user id is in session, connect ez user to external one
     *
     * @param \eZ\Publish\Core\MVC\Symfony\Event\InteractiveLoginEvent $event
     */
    public function onInteractiveLogin( InteractiveLoginEvent $event )
    {
        // does nothing
    }
}
