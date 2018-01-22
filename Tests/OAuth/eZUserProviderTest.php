<?php


namespace Netgen\Bundle\EzSocialConnectBundle\Tests\OAuth;

use Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;

class eZUserProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetUserIfAlreadyLinked()
    {
        $userStub = $this->getUserStub();
        $userResponseMock = $this->getUserResponseStub();
        $OAuthEzEntity = $this->getOAuthEzEntity();
        $OAuthEzUser = $this->getOAuthEzUser();

        $userContentHelperMock = $this->getUserContentHelperMock();
        $OAuthEzRepositoryMock = $this->getOAuthEzRepositoryMock();

        $OAuthEzRepositoryMock->method('loadFromTableByResourceUserId')
            ->willReturn($OAuthEzEntity);

        $eZUserProviderMock = $this->getEzUserProviderMock()
                ->setConstructorArgs(array($this->getAPIRepositoryMock(), $userContentHelperMock, $OAuthEzRepositoryMock))
                ->setMethods(array('getLinkedUser', 'generateOAuthEzUser'))
            ->getMock();

        $eZUserProviderMock
            ->expects($this->once())
            ->method('generateOAuthEzUser')
            ->with($userResponseMock)
            ->willReturn($OAuthEzUser);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('getLinkedUser')
            ->with($OAuthEzEntity, $OAuthEzUser)
            ->willReturn($userStub);

        $eZUserProviderMock->loadUserByOAuthUserResponse($userResponseMock);
    }

    public function testGetUserIfLinkNotFound()
    {
        $userStub = $this->getUserStub();
        $userResponseMock = $this->getUserResponseStub();
        $OAuthEzEntity = $this->getOAuthEzEntity();
        $OAuthEzUser = $this->getOAuthEzUser();

        $userContentHelperMock = $this->getUserContentHelperMock();
        $OAuthEzRepositoryMock = $this->getOAuthEzRepositoryMock();

        $eZUserContentObject = $this->getEzUserMock();

        $OAuthEzRepositoryMock->method('loadFromTableByResourceUserId')
            ->willReturn($OAuthEzEntity);

        $userContentHelperMock->method('createEzUser')
            ->willReturn($eZUserContentObject);

        $OAuthEzRepositoryMock
            ->expects($this->once())
            ->method('addToTable')
            ->with($eZUserContentObject, $OAuthEzUser, false);

        $eZUserProviderMock = $this->getEzUserProviderMock()
            ->setConstructorArgs(array($this->getAPIRepositoryMock(), $userContentHelperMock, $OAuthEzRepositoryMock))
            ->setMethods(array('getLinkedUser', 'generateOAuthEzUser', 'loadUserByAPIUser', 'getMergeAccounts'))
            ->getMock();

        $eZUserProviderMock
            ->expects($this->once())
            ->method('generateOAuthEzUser')
            ->with($userResponseMock)
            ->willReturn($OAuthEzUser);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('getLinkedUser')
            ->with($OAuthEzEntity, $OAuthEzUser)
            ->willReturn($this->getEzUserMock());

        $eZUserProviderMock
            ->expects($this->once())
            ->method('getMergeAccounts')
            ->willReturn(false);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('loadUserByAPIUser')
            ->with($eZUserContentObject)
            ->willReturn($userStub);

        $eZUserProviderMock->loadUserByOAuthUserResponse($userResponseMock);
    }

    public function testIfNotLinkedUserMergeAccountsTrue()
    {
        $userResponseMock = $this->getUserResponseStub();
        $OAuthEzEntity = $this->getOAuthEzEntity();
        $OAuthEzUser = $this->getOAuthEzUser();

        $userContentHelperMock = $this->getUserContentHelperMock();
        $OAuthEzRepositoryMock = $this->getOAuthEzRepositoryMock();

        $eZUserContentObject = $this->getEzUserMock();

        $OAuthEzRepositoryMock
            ->expects($this->once())
            ->method('loadFromTableByResourceUserId')
            ->willReturn($OAuthEzEntity);

        $userContentHelperMock
            ->expects($this->never())
            ->method('createEzUser')
            ->willReturn($eZUserContentObject);

        $OAuthEzRepositoryMock
            ->expects($this->once())
            ->method('addToTable')
            ->with($eZUserContentObject, $OAuthEzUser, true);

        $eZUserProviderMock = $this->getEzUserProviderMock()
            ->setConstructorArgs(array($this->getAPIRepositoryMock(), $userContentHelperMock, $OAuthEzRepositoryMock))
            ->setMethods(array('getLinkedUser', 'generateOAuthEzUser', 'loadUserByAPIUser', 'getMergeAccounts', 'getFirstUserByEmail'))
            ->getMock();

        $eZUserProviderMock
            ->expects($this->once())
            ->method('generateOAuthEzUser')
            ->with($userResponseMock)
            ->willReturn($OAuthEzUser);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('getLinkedUser')
            ->with($OAuthEzEntity, $OAuthEzUser)
            ->willReturn(null);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('getMergeAccounts')
            ->willReturn(true);

        $eZUserInterfaceMock = $this->getEzUserInterfaceMock()
            ->setMethods(array('getAPIUser'))
            ->getMockForAbstractClass();

        $eZUserInterfaceMock
            ->expects($this->once())
            ->method('getAPIUser')
            ->willReturn($eZUserContentObject);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('getFirstUserByEmail')
            ->with($OAuthEzUser->getEmail())
            ->willReturn($eZUserInterfaceMock);

        $eZUserProviderMock->loadUserByOAuthUserResponse($userResponseMock);
    }

    public function testFirstUserByEmailNotFound()
    {
        $userResponseMock = $this->getUserResponseStub();
        $OAuthEzEntity = $this->getOAuthEzEntity();
        $OAuthEzUser = $this->getOAuthEzUser();

        $userContentHelperMock = $this->getUserContentHelperMock();
        $OAuthEzRepositoryMock = $this->getOAuthEzRepositoryMock();

        $eZUserContentObject = $this->getEzUserMock();

        $OAuthEzRepositoryMock
            ->expects($this->once())
            ->method('loadFromTableByResourceUserId')
            ->willReturn($OAuthEzEntity);

        $userContentHelperMock
            ->expects($this->never())
            ->method('createEzUser')
            ->willReturn($eZUserContentObject);

        $OAuthEzRepositoryMock
            ->expects($this->once())
            ->method('addToTable')
            ->with($eZUserContentObject, $OAuthEzUser, true);

        $eZUserProviderMock = $this->getEzUserProviderMock()
            ->setConstructorArgs(array($this->getAPIRepositoryMock(), $userContentHelperMock, $OAuthEzRepositoryMock))
            ->setMethods(array(
                'getLinkedUser', 'generateOAuthEzUser', 'loadUserByAPIUser',
                'getMergeAccounts', 'getFirstUserByEmail', 'loadUserByUserName'
            ))
            ->getMock();

        $eZUserProviderMock
            ->expects($this->once())
            ->method('generateOAuthEzUser')
            ->with($userResponseMock)
            ->willReturn($OAuthEzUser);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('getLinkedUser')
            ->with($OAuthEzEntity, $OAuthEzUser)
            ->willReturn(null);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('getMergeAccounts')
            ->willReturn(true);

        $eZUserInterfaceMock = $this->getEzUserInterfaceMock()
            ->setMethods(array('getAPIUser'))
            ->getMockForAbstractClass();

        $eZUserInterfaceMock
            ->expects($this->once())
            ->method('getAPIUser')
            ->willReturn($eZUserContentObject);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('getFirstUserByEmail')
            ->with('test@test.com')
            ->willReturn(null);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('loadUserByUserName')
            ->with('gdy')
            ->willReturn($eZUserInterfaceMock);

        $eZUserProviderMock->loadUserByOAuthUserResponse($userResponseMock);
    }

    public function testUserByUsernameNotFoundFallbackToCreate()
    {
        $userResponseMock = $this->getUserResponseStub();

        $OAuthEzEntity = $this->getOAuthEzEntity();
        $OAuthEzUser = $this->getOAuthEzUser();

        $userContentHelperMock = $this->getUserContentHelperMock();
        $OAuthEzRepositoryMock = $this->getOAuthEzRepositoryMock();

        $eZUserContentObject = $this->getEzUserMock();

        $OAuthEzRepositoryMock->method('loadFromTableByResourceUserId')
            ->willReturn($OAuthEzEntity);

        $userContentHelperMock
            ->expects($this->once())
            ->method('createEzUser')
            ->willReturn($eZUserContentObject);

        $OAuthEzRepositoryMock
            ->expects($this->once())
            ->method('addToTable')
            ->with($eZUserContentObject, $OAuthEzUser, false);

        $eZUserProviderMock = $this->getEzUserProviderMock()
            ->setConstructorArgs(array($this->getAPIRepositoryMock(), $userContentHelperMock, $OAuthEzRepositoryMock))
            ->setMethods(array(
                'getLinkedUser', 'generateOAuthEzUser', 'loadUserByAPIUser',
                'getMergeAccounts', 'getFirstUserByEmail', 'loadUserByUserName'
            ))
            ->getMock();

        $eZUserProviderMock
            ->expects($this->once())
            ->method('generateOAuthEzUser')
            ->with($userResponseMock)
            ->willReturn($OAuthEzUser);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('getLinkedUser')
            ->with($OAuthEzEntity, $OAuthEzUser)
            ->willReturn(null);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('getMergeAccounts')
            ->willReturn(true);

        $eZUserInterfaceMock = $this->getEzUserInterfaceMock()
            ->setMethods(array('getAPIUser'))
            ->getMockForAbstractClass();

        $eZUserProviderMock
            ->expects($this->once())
            ->method('getFirstUserByEmail')
            ->with('test@test.com')
            ->willReturn(null);

        $eZUserProviderMock
            ->expects($this->once())
            ->method('loadUserByUserName')
            ->with('gdy')
            ->willThrowException(new \Symfony\Component\Security\Core\Exception\UsernameNotFoundException());

        $eZUserProviderMock
            ->expects($this->once())
            ->method('loadUserByAPIUser')
            ->willReturn($eZUserInterfaceMock);

        $eZUserProviderMock->loadUserByOAuthUserResponse($userResponseMock);
    }

    protected function getEzUserProviderMock()
    {
        return $this->getMockBuilder('\Netgen\Bundle\EzSocialConnectBundle\OAuth\eZUserProvider');
    }

    protected function getResourceOwnerMapMock()
    {
        return $this->getMockBuilder('\HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap')
            ->disableOriginalConstructor()->getMock();
    }

    protected function getOAuthTokenMock()
    {
        return $this->getMockBuilder('\HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken')
            ->disableOriginalConstructor()->getMock();
    }

    protected function getResourceOwnerMock()
    {
        return $this->getMockBuilder('\HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface');
    }

    protected function getUserResponseStub($emptyStub = null)
    {
        $pathUserResponseMock = $this->getMockBuilder('\HWI\Bundle\OAuthBundle\OAuth\Response\PathUserResponse')
            ->disableOriginalConstructor()
            ->setMethods(array('getUsername', 'getResourceOwner', 'getRealName', 'getImageLink',
                'getProfilePicture', 'getOriginalId', 'getEmail', 'getNickname'
            ))
            ->getMock();

        if (!$emptyStub) {
            $resourceOwnerStub = $this->getMockForAbstractClass('\HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface');
            $resourceOwnerStub->expects($this->any())->method('getName')->willReturn('facebook');

            $pathUserResponseMock->expects($this->any())->method('getRealName')->willReturn('Seamus Finnegan');
            $pathUserResponseMock->expects($this->any())->method('getUsername')->willReturn('gdy');
            $pathUserResponseMock->expects($this->any())->method('getEmail')->willReturn('geordi@mail.com');
            $pathUserResponseMock->expects($this->any())->method('getOriginalId')->willReturn('123456');
            $pathUserResponseMock->expects($this->any())->method('getNickname')->willReturn('some_hash_from_oauth_owner');
            $pathUserResponseMock->expects($this->atLeastOnce())->method('getResourceOwner')->willReturn($resourceOwnerStub);
            $pathUserResponseMock->expects($this->any())->method('getProfilePicture')->willReturn(null);
        }

        return $pathUserResponseMock;
    }

    protected function getUserCheckerMock()
    {
        return $this->createMock('\Symfony\Component\Security\Core\User\UserCheckerInterface');
    }

    protected function getUserStub()
    {
        $user = new \Symfony\Component\Security\Core\User\User('testuser', 'secretpassword');

        return $user;
    }

    protected function getEzUserMock()
    {
        return $this->getMockBuilder('\eZ\Publish\API\Repository\Values\User\User')
            ->disableOriginalConstructor()
            ->setMethods(array('getAPIUser', 'getVersionInfo', 'getFields', 'getFieldValue', 'getFieldsByLanguage', 'getField'))
            ->getMock();
    }

    protected function getAPIRepositoryMock()
    {
        return $this->getMockBuilder('\eZ\Publish\API\Repository\Repository')->disableOriginalConstructor()->getMock();
    }

    protected function getUserContentHelperMock()
    {
        return $this->getMockBuilder('\Netgen\Bundle\EzSocialConnectBundle\Helper\UserContentHelper')
            ->disableOriginalConstructor()
            ->setMethods(array('createEzUser'))
            ->getMock();
    }

    protected function getOAuthEzRepositoryMock()
    {
        return $this->getMockBuilder('Netgen\Bundle\EzSocialConnectBundle\Entity\Repository\OAuthEzRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('loadFromTableByResourceUserId', 'addToTable'))
            ->getMock();
    }

    protected function getOAuthEzEntity()
    {
        $OAuthEzEntity = new OAuthEz();
        $OAuthEzEntity->setDisconnectable(true);
        $OAuthEzEntity->setEzUserId('1234');
        $OAuthEzEntity->setResourceName('facebook');
        $OAuthEzEntity->setResourceUserId('4321');

        return $OAuthEzEntity;
    }

    protected function getEzUserInterfaceMock()
    {
        return $this->getMockBuilder('\eZ\Publish\Core\MVC\Symfony\Security\UserInterface');
    }

    protected function getOAuthEzUser()
    {
        $OAuthEzUser = new OAuthEzUser('gdy', 'secretpassword');
        $OAuthEzUser->setEmail('test@test.com');

        return $OAuthEzUser;
    }
}
