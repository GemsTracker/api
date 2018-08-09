<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\ClientEntity;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use PHPUnit\Framework\TestCase;

class ClientEntityTest extends TestCase
{
    public function testGettersAndSetters()
    {
        $scopeProphecy = $this->prophesize(ScopeEntityInterface::class);
        $scopeProphecy->getIdentifier()->willReturn('personal_data');
        $scopes = [$scopeProphecy->reveal()];

        $client = new ClientEntity();

        $client->setRedirect('test.nl');
        $this->assertEquals('test.nl', $client->getRedirect());
        $this->assertEquals('test.nl', $client->getRedirectUri());

        $client->setActive(true);
        $this->assertTrue($client->getActive());

        $client->setName('testName');
        $this->assertEquals('testName', $client->getName());

        $client->setSecret('testSecret');
        $this->assertEquals('testSecret', $client->getSecret());

        $client->setUserId('testUserId');
        $this->assertEquals('testUserId', $client->getUserId());
    }
}