<?php


namespace Netgen\Bundle\EzSocialConnectBundle\Tests\OAuth;

use HWI\Bundle\OAuthBundle\OAuth\Response\PathUserResponse;
use Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;

class eZUserProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetLinkedUser()
    {
        $userStub = $this->getUserStub();
        $userResponseMock = $this->getUserResponseMock();
        $OAuthEzEntity = $this->getOAuthEzEntity();
        $OAuthEzUser = $this->getOAuthEzUser();

        $loginHelperMock = $this->getLoginHelperMock();

        $loginHelperMock->method('loadFromTableByResourceUserId')
            ->willReturn($OAuthEzEntity);

        $eZUserProviderMock = $this->getEzUserProviderMock()
                ->setConstructorArgs(array($this->getAPIRepositoryMock(), $loginHelperMock))
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

    public function testLinkedUserNotFound()
    {
        $userStub = $this->getUserStub();
        $userResponseMock = $this->getUserResponseMock();
        $OAuthEzEntity = $this->getOAuthEzEntity();
        $OAuthEzUser = $this->getOAuthEzUser();

        $loginHelperMock = $this->getLoginHelperMock();

        $eZUserContentObject = $this->getEzUserMock();

        $loginHelperMock->method('loadFromTableByResourceUserId')
            ->willReturn($OAuthEzEntity);

        $loginHelperMock->method('createEzUser')
            ->willReturn($eZUserContentObject);

        $loginHelperMock
            ->expects($this->once())
            ->method('addToTable')
            ->with($eZUserContentObject, $OAuthEzUser, false);

        $eZUserProviderMock = $this->getEzUserProviderMock()
            ->setConstructorArgs(array($this->getAPIRepositoryMock(), $loginHelperMock))
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

    public function testNotLinkedUserMergeAccountsTrue()
    {
        $userResponseMock = $this->getUserResponseMock();
        $OAuthEzEntity = $this->getOAuthEzEntity();
        $OAuthEzUser = $this->getOAuthEzUser();

        $loginHelperMock = $this->getLoginHelperMock();

        $eZUserContentObject = $this->getEzUserMock();

        $loginHelperMock
            ->expects($this->once())
            ->method('loadFromTableByResourceUserId')
            ->willReturn($OAuthEzEntity);

        $loginHelperMock
            ->expects($this->never())
            ->method('createEzUser')
            ->willReturn($eZUserContentObject);

        $loginHelperMock
            ->expects($this->once())
            ->method('addToTable')
            ->with($eZUserContentObject, $OAuthEzUser, true);

        $eZUserProviderMock = $this->getEzUserProviderMock()
            ->setConstructorArgs(array($this->getAPIRepositoryMock(), $loginHelperMock))
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
        $userResponseMock = $this->getUserResponseMock();
        $OAuthEzEntity = $this->getOAuthEzEntity();
        $OAuthEzUser = $this->getOAuthEzUser();

        $loginHelperMock = $this->getLoginHelperMock();

        $eZUserContentObject = $this->getEzUserMock();

        $loginHelperMock
            ->expects($this->once())
            ->method('loadFromTableByResourceUserId')
            ->willReturn($OAuthEzEntity);

        $loginHelperMock
            ->expects($this->never())
            ->method('createEzUser')
            ->willReturn($eZUserContentObject);

        $loginHelperMock
            ->expects($this->once())
            ->method('addToTable')
            ->with($eZUserContentObject, $OAuthEzUser, true);

        $eZUserProviderMock = $this->getEzUserProviderMock()
            ->setConstructorArgs(array($this->getAPIRepositoryMock(), $loginHelperMock))
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
        $userResponseMock = $this->getUserResponseMock();
        $OAuthEzEntity = $this->getOAuthEzEntity();
        $OAuthEzUser = $this->getOAuthEzUser();

        $loginHelperMock = $this->getLoginHelperMock();

        $eZUserContentObject = $this->getEzUserMock();

        $loginHelperMock->method('loadFromTableByResourceUserId')
            ->willReturn($OAuthEzEntity);

        $loginHelperMock
            ->expects($this->once())
            ->method('createEzUser')
            ->willReturn($eZUserContentObject);

        $loginHelperMock
            ->expects($this->once())
            ->method('addToTable')
            ->with($eZUserContentObject, $OAuthEzUser, false);

        $eZUserProviderMock = $this->getEzUserProviderMock()
            ->setConstructorArgs(array($this->getAPIRepositoryMock(), $loginHelperMock))
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
        return $this->createMock('\HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface');
    }

    protected function getUserResponseMock()
    {
        $pathUserResponse = new PathUserResponse();

        $pathUserResponse->setPaths(array(
            'identifier'     => 'Test',
            'nickname'       => 'gdy',
            'firstname'      => 'Geordi',
            'lastname'       => 'laForge',
            'realname'       => 'Seamus Finnegan',
            'email'          => 'gordi@mail.com',
            'profilepicture' => null,
        ));

        $pathUserResponse->setOAuthToken($this->getOAuthTokenMock());
        $pathUserResponse->setResourceOwner($this->getResourceOwnerMock());

        return $pathUserResponse;
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
            ->setMethods(array('getAPIUser', 'getVersionInfo', 'getFields', 'getFieldValue', 'getFieldsByLanguage'))
            ->getMock();
    }

    protected function getAPIRepositoryMock()
    {
        return $this->getMockBuilder('\eZ\Publish\API\Repository\Repository')->disableOriginalConstructor()->getMock();
    }

    protected function getLoginHelperMock()
    {
        return $this->getMockBuilder('\Netgen\Bundle\EzSocialConnectBundle\Helper\SocialLoginHelper')
            ->disableOriginalConstructor()
            ->setMethods(array('loadFromTableByResourceUserId', 'createEzUser', 'addToTable'))
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
