<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use eZ\Publish\API\Repository\Values\User\User;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;

class OAuthEzRepository extends EntityRepository
{
    /**
     * Adds entry to the table.
     *
     * If disconnectable is true, this link can be deleted.
     * Otherwise, it is assumed to be the main social login which created the eZ user initially.
     *
     * @param \eZ\Publish\API\Repository\Values\User\User            $user
     * @param \Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser $authEzUser
     * @param bool                                                   $disconnectable
     */
    public function addToTable(User $user, OAuthEzUser $authEzUser, $disconnectable = false)
    {
        $OAuthEzEntity = new OAuthEz();
        $OAuthEzEntity
            ->setEzUserId($user->id)
            ->setResourceUserId($authEzUser->getOriginalId())
            ->setResourceName($authEzUser->getResourceOwnerName())
            ->setDisconnectable($disconnectable);

        $this->getEntityManager()->persist($OAuthEzEntity);
        $this->getEntityManager()->flush();
    }

    /**
     * Removes entry from the table.
     *
     * @param \Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz $userEntity
     */
    public function removeFromTable(OAuthEz $userEntity)
    {
        $this->getEntityManager()->remove($userEntity);
        $this->getEntityManager()->flush();
    }


    /**
     * Loads from table by eZ user id and resource name.
     *
     * @param string $ezUserId
     * @param string $resourceOwnerName
     *
     * @return null|\Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser
     */
    public function loadFromTableByEzId($ezUserId, $resourceOwnerName, $onlyDisconnectable = false)
    {
        return $this->loadFromTableByCriteria(array(
            'ezUserId' => $ezUserId,
            'resourceName' => $resourceOwnerName,
        ), $onlyDisconnectable);
    }

    /**
     * Loads from table by resource user id and resource name.
     *
     * @param string $resourceUserId
     * @param string $resourceOwnerName
     *
     * @return null|\Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz
     */
    public function loadFromTableByResourceUserId($resourceUserId, $resourceOwnerName, $onlyDisconnectable = false)
    {
        return $this->loadFromTableByCriteria(array(
            'resourceUserId' => $resourceUserId,
            'resourceName' => $resourceOwnerName,
        ), $onlyDisconnectable);
    }

    /**
     * Loads from table by criteria.
     *
     * @param array     $criteria
     * @param bool      $onlyDisconnectable
     *
     * @return null|\Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser
     */
    protected function loadFromTableByCriteria(array $criteria, $onlyDisconnectable = false)
    {
        if ($onlyDisconnectable) {
            $criteria['disconnectable'] = true;
        }

        return $this->findOneBy(
            $criteria,
            array('ezUserId' => 'DESC')     // Get last inserted item.
        );
    }
}
