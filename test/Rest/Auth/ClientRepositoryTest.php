<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\ClientEntity;
use Gems\Rest\Auth\ClientRepository;
use GemsTest\Rest\Test\ZendDbTestCase;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use PHPUnit\DbUnit\DataSet\DefaultDataSet;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Zalt\Loader\ProjectOverloader;

class ClientRepositoryTest extends ZendDbTestCase
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

    public function testGetClientEntity()
    {
        $clientRepository = $this->getClientRepository();

        $newClient = [
            'user_id' => 1,
            'name' => 'Test client',
            'secret' => password_hash('testPassword', PASSWORD_DEFAULT),
            'redirect' => null,
            'active' => 1,
            'changed' => '2017-12-07 12:00:00',
            'changed_by' => 1,
            'created' => '2017-12-07 12:00:00',
            'created_by' => 1,
        ];

        $clientRepository->save($newClient);

        $client = $clientRepository->getClientEntity(1, null, 'testPassword', true);

        $this->assertInstanceOf(ClientEntityInterface::class, $client, 'Returned client not instance of '. ClientEntityInterface::class);
        $this->assertEquals(1, $client->getIdentifier(), 'Returned client does not have the correct UserID');
        $this->assertEquals($newClient['user_id'], $client->getId(), 'Client Entity user ID not as expected');

        $client = $clientRepository->getClientEntity(1, null, 'wrongPassword', true);
        $this->assertNull($client, 'Client with wrong password should return null');
    }


    protected function getClientRepository()
    {
        $loaderProphecy = $this->prophesize(ProjectOverloader::class);
        $loaderProphecy->create('Rest\\Auth\\ClientEntity')->willReturn(new ClientEntity);
        $loader = $loaderProphecy->reveal();
        return new ClientRepository($this->db, $loader);
    }

}