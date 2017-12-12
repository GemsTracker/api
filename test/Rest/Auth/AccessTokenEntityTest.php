<?php


namespace GemsTest\Rest\Auth;


use Gems\Rest\Auth\AccessTokenEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use PHPUnit\Framework\TestCase;

class AccessTokenEntityTest extends TestCase
{
    public function testGettersAndSetters()
    {
        $scopeProphecy = $this->prophesize(ScopeEntityInterface::class);
        $scopeProphecy->getIdentifier()->willReturn('personal_data');
        $scopes = [$scopeProphecy->reveal()];

        $accessToken = new AccessTokenEntity('testUser', $scopes);

        $this->assertEquals('testUser', $accessToken->getUserIdentifier());

        $clientProphecy = $this->prophesize(ClientEntityInterface::class);
        $clientProphecy->getIdentifier()->willReturn('testClient');
        $accessToken->setClient($clientProphecy->reveal());

        $this->assertInstanceOf(ClientEntityInterface::class, $accessToken->getClient());
        $this->assertEquals('testClient', $accessToken->getClientId());

        $now = new \DateTime();
        $accessToken->setExpiryDateTime($now);
        $this->assertEquals($now, $accessToken->getExpiryDateTime());

        $accessToken->setExpiresAt($now);
        $this->assertEquals($now, $accessToken->getExpiresAt());

        $accessToken->setId('testId');
        $this->assertEquals('testId', $accessToken->getId());
        $accessToken->setIdentifier('testId2');
        $this->assertEquals('testId2', $accessToken->getIdentifier());

        $accessToken->setRevoked(true);
        $this->assertEquals(true, $accessToken->getRevoked());

        $this->assertEquals($scopes, $accessToken->getScopes());

        $accessToken = new AccessTokenEntity('testUser');
        $this->assertNull($accessToken->getScopes());

    }

}