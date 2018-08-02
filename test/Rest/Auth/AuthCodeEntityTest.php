<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\AuthCodeEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use PHPUnit\Framework\TestCase;

class AuthCodeEntityTest extends TestCase
{
    public function testGettersAndSetters()
    {
        $scopeProphecy = $this->prophesize(ScopeEntityInterface::class);
        $scopeProphecy->getIdentifier()->willReturn('personal_data');
        $scopes = [$scopeProphecy->reveal()];

        $authCode = new AuthCodeEntity('testUser', $scopes);

        $this->assertEquals('testUser', $authCode->getUserIdentifier());

        $clientProphecy = $this->prophesize(ClientEntityInterface::class);
        $clientProphecy->getIdentifier()->willReturn('testClient');
        $authCode->setClient($clientProphecy->reveal());

        $this->assertInstanceOf(ClientEntityInterface::class, $authCode->getClient());
        $this->assertEquals('testClient', $authCode->getClientId());

        $now = new \DateTime();
        $authCode->setExpiryDateTime($now);
        $this->assertEquals($now, $authCode->getExpiryDateTime());

        $authCode->setExpiresAt($now);
        $this->assertEquals($now, $authCode->getExpiresAt());

        $authCode->setId('testId');
        $this->assertEquals('testId', $authCode->getId());
        $authCode->setIdentifier('testId2');
        $this->assertEquals('testId2', $authCode->getIdentifier());

        $authCode->setRevoked(true);
        $this->assertTrue($authCode->getRevoked());

        $this->assertEquals($scopes, $authCode->getScopes());

        $authCode = new AuthCodeEntity('testUser');
        $this->assertNull($authCode->getScopes());

        $authCode->setRedirectUri('test.nl');
        $this->assertEquals('test.nl', $authCode->getRedirectUri());
    }
}