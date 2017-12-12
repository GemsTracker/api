<?php


namespace Gemstest\Rest\Auth;

use Gems\Rest\Auth\AuthCodeEntity;
use Gems\Rest\Auth\AuthCodeRepository;
use GemsTest\Rest\Test\ZendDbTestCase;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use PHPUnit\DbUnit\DataSet\DefaultDataSet;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Zalt\Loader\ProjectOverloader;

class AuthTokenRepositoryTest extends ZendDbTestCase
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
        $authCodeRepository = $this->getAuthCodeRepository();
        $authCode = $authCodeRepository->getNewAuthCode();

        $this->assertInstanceOf(AuthCodeEntityInterface::class, $authCode, 'Auth Code not instance of ' . AuthCodeEntityInterface::class);
        $this->assertEquals(0, $authCode->getRevoked(), 'Auth Code should not be revoked by default');
    }

    public function testPersistNewAccessToken()
    {
        $clientProphecy = $this->prophesize(ClientEntityInterface::class);
        $clientProphecy->getIdentifier()->willReturn(1);
        $client = $clientProphecy->reveal();

        $authCodeRepository = $this->getAuthCodeRepository();
        $authCode = $authCodeRepository->getNewAuthCode();
        $authCode->setClient($client);

        $scopeProphecy = $this->prophesize(ScopeEntityInterface::class);
        $scopeProphecy->getIdentifier()->willReturn('private_data');
        $scope = $scopeProphecy->reveal();
        $authCode->addScope($scope);

        $authCodeRepository->persistNewAuthCode($authCode);

        $result = $authCodeRepository->loadFirst(['id' => 1]);
        $this->assertInstanceOf(AuthCodeEntityInterface::class, $result);
        $this->assertEquals(1, $result->getIdentifier());

        $authCodeRepository->revokeAuthCode(1);

        $this->assertTrue($authCodeRepository->isAuthCodeRevoked(1), 'Revoked token should return that it\'s revoked');

        $this->assertFalse($authCodeRepository->isAuthCodeRevoked(10), 'Unknown refresh Token Id should return false');

    }

    protected function getAuthCodeRepository()
    {
        $loaderProphecy = $this->prophesize(ProjectOverloader::class);
        $loaderProphecy->create('Rest\\Auth\\AuthCodeEntity')->willReturn(new AuthCodeEntity);
        $loader = $loaderProphecy->reveal();
        return new AuthCodeRepository($this->db, $loader);
    }
}