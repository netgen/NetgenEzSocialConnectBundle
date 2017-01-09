<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Slot;

use eZ\Publish\Core\SignalSlot\Signal;
use eZ\Publish\Core\SignalSlot\Slot as BaseSlot;
use Netgen\Bundle\EzSocialConnectBundle\Entity\Repository\OAuthEzRepository;

class DeleteOAuthEzOnUDeleteUserSlot extends BaseSlot
{
    /**
     * @var OAuthEzRepository
     */
    private $oAuthEzRepository;


    public function __construct(OAuthEzRepository $oAuthEzRepository)
    {
        $this->oAuthEzRepository = $oAuthEzRepository;
    }

    public function receive(Signal $signal)
    {
        if (!$signal instanceof Signal\UserService\DeleteUserSignal) {
            return;
        }

        $users = $this->oAuthEzRepository->loadAllFromTableByEzId($signal->userId);

        foreach ($users as $user) {
            $this->oAuthEzRepository->removeFromTable($user);
        }
    }
}
