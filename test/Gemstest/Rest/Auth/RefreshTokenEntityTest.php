<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\RefreshTokenEntity;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use PHPUnit\Framework\TestCase;

class RefreshTokenEntityTest extends TestCase
{
    public function testGettersAndSetters()
    {
        $refreshToken = new RefreshTokenEntity();

        $tomorrow = new \DateTime('Tomorrow');
        $refreshToken->setExpiryDateTime($tomorrow);
        $this->assertEquals($tomorrow, $refreshToken->getExpiryDateTime());

        $nextDay  = new \DateTime('tomorrow + 1day');
        $refreshToken->setExpiresAt($nextDay);
        $this->assertEquals($nextDay, $refreshToken->getExpiresAt());

        $refreshToken->setIdentifier('testIdentifier');
        $this->assertEquals('testIdentifier', $refreshToken->getIdentifier());

        $refreshToken->setRevoked(true);
        $this->assertTrue($refreshToken->getRevoked());

        $accessTokenProphecy = $this->prophesize(AccessTokenEntityInterface::class);
        $accessTokenProphecy->getIdentifier()->willReturn('testId');
        $accessToken = $accessTokenProphecy->reveal();

        $refreshToken->setAccessToken($accessToken);
        $this->assertEquals($accessToken, $refreshToken->getAccessToken());
        $this->assertEquals('testId', $refreshToken->getAccessTokenId());
    }
}