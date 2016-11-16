<?php

namespace Netgen\Bundle\EzSocialConnectBundle\Tests\OAuth;

use eZ\Publish\Core\Repository\Values\ContentType\FieldDefinition;
use Netgen\Bundle\EzSocialConnectBundle\Entity\OAuthEz;
use Netgen\Bundle\EzSocialConnectBundle\OAuth\OAuthEzUser;

class UserContentHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testGetUserCreateStruct()
    {
        $contentTypeMock = $this->getContentTypeMockBuilder()
            ->getMockForAbstractClass();

        $contentTypeMock->expects($this->once())->method('getFieldDefinition')->willReturn(new FieldDefinition());

        $userCreateStructMock = $this->getUserCreateStructMockBuilder()
            ->disableOriginalConstructor()->getMock();

        $userServiceMock = $this->getUserServiceMockBuilder()->disableOriginalConstructor();
        $userServiceMock = $userServiceMock->setMethods(array('newUserCreateStruct'))->getMockForAbstractClass();
        $userServiceMock->expects($this->once())->method('newUserCreateStruct')->with(
            'gdy', 'test@test.com', 'pa$$wordha$h', 'eng-GB', $contentTypeMock
        )->willReturn($userCreateStructMock);

        $repositoryMock = $this->getAPIRepositoryMock();
        $repositoryMock->expects($this->once())->method('getUserService')->willReturn($userServiceMock);

        $userContentHelperMock = $this->getUserContentHelperMockBuilder()
            ->disableOriginalConstructor()
            ->setMethods(array(
                'getRepository', 'createPassword', 'getImageIfExists', 'addFieldIfExists',
                'getFirstNameIdentifier', 'getLastNameIdentifier', 'isImageFieldDefined'
            ))
            ->getMock();
        $userContentHelperMock->expects($this->once())->method('getRepository')->willReturn($repositoryMock);
        $userContentHelperMock->expects($this->once())->method('createPassword')->willReturn('pa$$wordha$h');
        $userContentHelperMock->expects($this->once())->method('isImageFieldDefined')->willReturn(true);
        $userContentHelperMock->expects($this->once())->method('getImageIfExists')->willReturn($userCreateStructMock);

        $userContentHelperMock->expects($this->once())->method('getFirstNameIdentifier')->willReturn('first_name');
        $userContentHelperMock->expects($this->once())->method('getLastNameIdentifier')->willReturn('last_name');

        $userContentHelperMock->expects($this->exactly(2))->method('addFieldIfExists')->withConsecutive(
            array($userCreateStructMock, $contentTypeMock, 'first_name', 'John'),
            array($userCreateStructMock, $contentTypeMock, 'last_name', 'Doe')
        )->willReturn($userCreateStructMock);

        $userCreateStruct = $userContentHelperMock->getUserCreateStruct(
            $this->getOAuthEzUser(), $contentTypeMock, 'eng-GB'
        );
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

    protected function getUserContentHelperMockBuilder()
    {
        return $this->getMockBuilder('\Netgen\Bundle\EzSocialConnectBundle\Helper\UserContentHelper');
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
        $OAuthEzUser->setFirstName('John');
        $OAuthEzUser->setLastName('Doe');

        return $OAuthEzUser;
    }

    protected function getUserServiceMockBuilder()
    {
        return $this->getMockBuilder('\eZ\Publish\API\Repository\UserService');
    }

    protected function getContentTypeMockBuilder()
    {
        return $this->getMockBuilder('\eZ\Publish\API\Repository\Values\ContentType\ContentType');
    }

    protected function getUserCreateStructMockBuilder()
    {
        return $this->getMockBuilder('\eZ\Publish\API\Repository\Values\User\UserCreateStruct');
    }
}
