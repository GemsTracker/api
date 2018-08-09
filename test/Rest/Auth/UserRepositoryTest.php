<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\UserEntity;
use Gems\Rest\Auth\UserRepository;
use GemsTest\Rest\Test\ZendDbTestCase;
use Interop\Container\ContainerInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use PHPUnit\DbUnit\DataSet\DefaultDataSet;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Prophecy\Argument;
use Zalt\Loader\ProjectOverloader;
use Zend\Authentication\Result;

class UserRepositoryTest extends ZendDbTestCase
{
    protected $loadZendDb2 = true;

    /**
     * Returns the test dataset.
     *
     * @return IDataSet
     */
    protected function getDataSet()
    {
        $file = str_replace('.php', '.yml', __FILE__);
        return new YamlDataSet($file);
    }

    public function testGetUserEntityByUserCredentials()
    {
        $username = 'testUser@testOrganization';
        $password = 'testPassword';
        $grantType = 'AuthCode';
        $clientEntity = $this->prophesize(ClientEntityInterface::class)->reveal();

        $result = $this->prophesize(Result::class);
        $result->isValid()->willReturn(true);

        $user = $this->prophesize(\Gems_User_User::class);
        $user->authenticate(Argument::type('string'))->willReturn($result->reveal());
        $user->getUserId()->willReturn(1);

        $userLoader = $this->prophesize(\Gems_User_UserLoader::class);
        $userLoader->getUser(Argument::type('string'), Argument::type('string'))->willReturn($user->reveal());

        $legacyLoader = $this->prophesize(\Gems_Loader::class);
        $legacyLoader->getUserLoader()->willReturn($userLoader->reveal());

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('LegacyLoader')->willReturn($legacyLoader->reveal());

        $loader = $this->prophesize(ProjectOverloader::class);
        $loader->getServiceManager()->willReturn($container->reveal());

        $userRepository = new UserRepository($this->db, $loader->reveal());
        $userEntity = $userRepository->getUserEntityByUserCredentials($username, $password, $grantType, $clientEntity);

        $this->assertInstanceOf(UserEntityInterface::class, $userEntity);
    }

    public function testGetUserEntityByUserCredentialsWithNoUser()
    {
        $username = 'testUser@testOrganization';
        $password = 'testPassword';
        $grantType = 'AuthCode';
        $clientEntity = $this->prophesize(ClientEntityInterface::class)->reveal();

        $userLoader = $this->prophesize(\Gems_User_UserLoader::class);
        $userLoader->getUser(Argument::type('string'), Argument::type('string'))->willReturn(false);

        $legacyLoader = $this->prophesize(\Gems_Loader::class);
        $legacyLoader->getUserLoader()->willReturn($userLoader->reveal());

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('LegacyLoader')->willReturn($legacyLoader->reveal());

        $loader = $this->prophesize(ProjectOverloader::class);
        $loader->getServiceManager()->willReturn($container->reveal());

        $userRepository = new UserRepository($this->db, $loader->reveal());
        $result = $userRepository->getUserEntityByUserCredentials($username, $password, $grantType, $clientEntity);

        $this->assertNull($result, 'When no user is found, null should be returned');
    }

    public function testGetUserEntityByUserCredentialsWithFailedUser()
    {
        $username = 'testUser@testOrganization';
        $password = 'testPassword';
        $grantType = 'AuthCode';
        $clientEntity = $this->prophesize(ClientEntityInterface::class)->reveal();

        $result = $this->prophesize(Result::class);
        $result->isValid()->willReturn(false);

        $user = $this->prophesize(\Gems_User_User::class);
        $user->authenticate(Argument::type('string'))->willReturn($result->reveal());
        $user->getUserId()->willReturn(1);

        $userLoader = $this->prophesize(\Gems_User_UserLoader::class);
        $userLoader->getUser(Argument::type('string'), Argument::type('string'))->willReturn($user->reveal());

        $legacyLoader = $this->prophesize(\Gems_Loader::class);
        $legacyLoader->getUserLoader()->willReturn($userLoader->reveal());

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('LegacyLoader')->willReturn($legacyLoader->reveal());

        $loader = $this->prophesize(ProjectOverloader::class);
        $loader->getServiceManager()->willReturn($container->reveal());

        $userRepository = new UserRepository($this->db, $loader->reveal());
        $result = $userRepository->getUserEntityByUserCredentials($username, $password, $grantType, $clientEntity);

        $this->assertNull($result, 'When a user login is not valid, null should be returned');
    }

    public function testExtractUserInfo()
    {
        $loader = $this->prophesize(ProjectOverloader::class);
        $userRepository = new UserRepository($this->db, $loader->reveal());

        $result = $userRepository->extractUserInfo('testUser@testOrganization');
        $this->assertEquals(['username' => 'testUser', 'organizationId' => 1], $result, 'testOrganization should return OrganizationId 1');

        $result = $userRepository->extractUserInfo('testUser@1');
        $this->assertEquals(['username' => 'testUser', 'organizationId' => 1], $result, 'OrganizationId 1 should return OrganizationId 1');
    }

    public function testExtractUserInfoException()
    {
        $loader = $this->prophesize(ProjectOverloader::class);
        $userRepository = new UserRepository($this->db, $loader->reveal());


        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No organization has been embedded in username');
        $userRepository->extractUserInfo('testUser');
    }

    public function testGetOrganizationIdException()
    {
        $loader = $this->prophesize(ProjectOverloader::class);
        $userRepository = new UserRepository($this->db, $loader->reveal());

        $organizationId = 'test';
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(sprintf('No organization found with identifier %s', $organizationId));
        $userRepository->getOrganizationId($organizationId);
    }



    protected function getUserRepository()
    {
        $loaderProphecy = $this->prophesize(ProjectOverloader::class);
        $loaderProphecy->create('Rest\\Auth\\UserEntity')->willReturn(new UserEntity);
        $loader = $loaderProphecy->reveal();
        return new UserRepository($this->db, $loader);
    }
}