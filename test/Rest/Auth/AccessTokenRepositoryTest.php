<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\AccessTokenEntity;
use Gems\Rest\Auth\AccessTokenRepository;
use GemsTest\Rest\Test\ZendDbTestCase;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use PHPUnit\DbUnit\DataSet\DefaultDataSet;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Zalt\Loader\ProjectOverloader;

class AccessTokenRepositoryTest extends ZendDbTestCase
{
    protected $loadZendDb2 = true;

    /**
     * Returns the test dataset.
     *
     * @return IDataSet
     */
    protected function getDataSet()
    {
        return new DefaultDataSet();
        $file = str_replace('.php', '.yml', __FILE__);
        return new YamlDataSet($file);
    }

    public function testGetNewToken()
    {
        $clientEntity = $this->prophesize(ClientEntityInterface::class)->reveal();
        $scopes = [];
        $userIdentifier = 'test';

        $accessTokenRepository = $this->getAccessTokenRepository();
        $accessToken = $accessTokenRepository->getNewToken($clientEntity, $scopes, $userIdentifier);

        $this->assertInstanceOf(AccessTokenEntityInterface::class, $accessToken, 'Access token not instance of ' . AccessTokenEntityInterface::class);
        $this->assertEquals(0, $accessToken->getRevoked(), 'Access token should not be revoked by default');
    }

    public function testPersistNewAccessToken()
    {
        $accessTokenRepository = $this->getAccessTokenRepository();

        $userProphecy = $this->prophesize(UserEntityInterface::class);
        $userProphecy->getIdentifier()->willReturn(1);
        $user = $userProphecy->reveal();
        $clientProphecy = $this->prophesize(ClientEntityInterface::class);
        $clientProphecy->getIdentifier()->willReturn(1);
        $client = $clientProphecy->reveal();


        $scopeProphecy = $this->prophesize(ScopeEntityInterface::class);
        $scopeProphecy->getIdentifier()->willReturn('private_data');
        $scope = $scopeProphecy->reveal();
        $scopes = [$scope];


        $accessToken = $accessTokenRepository->getNewToken($client, $scopes, $user->getIdentifier());
        $accessToken->setExpiryDateTime(new \DateTime('Tomorrow'));

        $accessTokenRepository->persistNewAccessToken($accessToken);

        $token = $accessTokenRepository->findValidToken($user, $client);
        $this->assertInstanceOf(AccessTokenEntityInterface::class, $token, 'Found access token not instance of ' . AccessTokenEntityInterface::class);
        $this->assertEquals(1, $token->getUserIdentifier(), 'Found access token does not have the correct UserId');

        $userProphecy2 = $this->prophesize(UserEntityInterface::class);
        $userProphecy2->getIdentifier()->willReturn(2);
        $user2 = $userProphecy2->reveal();

        $token2 = $accessTokenRepository->findValidToken($user2, $client);
        $this->assertFalse($token2, 'No Token should have been found');

        $accessTokenRepository->revokeAccessToken(1);
        $token3 = $accessTokenRepository->findValidToken($user, $client);
        $this->assertFalse($token3, 'Revoked token should not have been found');

        $this->assertTrue($accessTokenRepository->isAccessTokenRevoked(1), 'Revoked token should return that it\'s revoked');

        $this->assertFalse($accessTokenRepository->isAccessTokenRevoked(10), 'Unknown Access Token Id should return false');
    }

    private function getAccessTokenRepository()
    {
        $loaderProphecy = $this->prophesize(ProjectOverloader::class);
        $loaderProphecy->create('Rest\\Auth\\AccessTokenEntity')->willReturn(new AccessTokenEntity);
        $loader = $loaderProphecy->reveal();
        return new AccessTokenRepository($this->db, $loader);
    }
}