<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\RefreshTokenEntity;
use Gems\Rest\Auth\RefreshTokenRepository;
use GemsTest\Rest\Test\ZendDbTestCase;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use PHPUnit\DbUnit\DataSet\DefaultDataSet;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Zalt\Loader\ProjectOverloader;

class RefreshTokenRepositoryTest extends ZendDbTestCase
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

    public function testGetNewRefreshToken()
    {
        $clientEntity = $this->prophesize(ClientEntityInterface::class)->reveal();
        $scopes = [];
        $userIdentifier = 'test';

        $refreshTokenRepository = $this->getRefreshTokenRepository();
        $accessToken = $refreshTokenRepository->getNewRefreshToken($clientEntity, $scopes, $userIdentifier);

        $this->assertInstanceOf(RefreshTokenEntityInterface::class, $accessToken, 'Refresh token not instance of ' . RefreshTokenEntityInterface::class);
        $this->assertEquals(0, $accessToken->getRevoked(), 'Refresh token should not be revoked by default');
    }

    public function testPersistNewRefreshToken()
    {
        $refreshTokenRepository = $this->getRefreshTokenRepository();

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

        $accessTokenProphecy = $this->prophesize(AccessTokenEntityInterface::class);
        $accessTokenProphecy->getIdentifier()->willReturn(123);
        $accessToken = $accessTokenProphecy->reveal();

        $refreshToken = $refreshTokenRepository->getNewRefreshToken($client, $scopes, $user->getIdentifier());
        $refreshToken->setExpiryDateTime(new \DateTime('Tomorrow'));
        $refreshToken->setAccessToken($accessToken);

        $refreshTokenRepository->persistNewRefreshToken($refreshToken);

        $token = $refreshTokenRepository->loadFirst(['id' => 1]);
        $this->assertInstanceOf(RefreshTokenEntityInterface::class, $token, 'Found refresh token not instance of ' . RefreshTokenEntityInterface::class);
        $this->assertEquals(1, $token->getIdentifier(), 'Found refresh token does not have the correct UserId');

        $refreshTokenRepository->revokeRefreshToken(1);

        $this->assertTrue($refreshTokenRepository->isRefreshTokenRevoked(1), 'Revoked token should return that it\'s revoked');

        $this->assertFalse($refreshTokenRepository->isRefreshTokenRevoked(10), 'Unknown refresh Token Id should return false');
    }

    protected function getRefreshTokenRepository()
    {
        $loaderProphecy = $this->prophesize(ProjectOverloader::class);
        $loaderProphecy->create('Rest\\Auth\\RefreshTokenEntity')->willReturn(new RefreshTokenEntity());
        $loader = $loaderProphecy->reveal();
        return new RefreshTokenRepository($this->db, $loader);
    }
}