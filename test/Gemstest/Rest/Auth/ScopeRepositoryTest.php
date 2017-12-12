<?php


namespace Gemstest\Rest\Auth;

use Gems\Rest\Auth\ScopeEntity;
use Gems\Rest\Auth\ScopeRepository;
use GemsTest\Rest\Test\ZendDbTestCase;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use Zalt\Loader\ProjectOverloader;
use Zend\Db\Sql\Sql;

class ScopeRepositoryTest extends ZendDbTestCase
{
    protected $loadZendDb1 = true;
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

    protected function getDataArray($tableName=false)
    {
        $file = str_replace('.php', '.yml', __FILE__);

        $result = Yaml::parse(file_get_contents($file));

        if ($tableName) {
            return $result[$tableName];
        }

        return $result;
    }

    public function testGetScopeEntityByIdentifier()
    {
        $scopeRepository = $this->getScopeRepository();

        $scope = $scopeRepository->getScopeEntityByIdentifier('private_data');

        $this->assertInstanceOf(ScopeEntityInterface::class, $scope, 'Returned scope should be an instance of '. ScopeEntityInterface::class);

        $scopes = [$scope];
        $grantType = 'Implicit';
        $client = $this->prophesize(ClientEntityInterface::class)->reveal();
        $userId = 'testUserId';

        $returnedScopes = $scopeRepository->finalizeScopes($scopes, $grantType, $client, $userId);

        $this->assertEquals($scopes, $returnedScopes, 'All scopes should be returned if no filter is set');
    }

    public function testLoadResultWithLimit()
    {
        $scopeRepository = $this->getScopeRepository();
        $result = $scopeRepository->load(['limit' => [1,0]]);

        $this->assertCount(1, $result, 'Expecting only one result');

        $result = $scopeRepository->loadFirst(['limit' => [1,0]]);
        $this->assertCount(1, $result, 'Expecting only one result');
    }

    public function testEntityGettersWithoutSetters()
    {
        $scopeRepository = $this->getScopeRepository();
        $scope = $scopeRepository->getScopeEntityByIdentifier('private_data');

        $this->assertEquals(1, $scope->getId(), 'Scope ID not matching expected result');
        $this->assertEquals('private_data', $scope->getIdentifier(), 'Scope ID not matching the name');
        $this->assertEquals('private_data', $scope->jsonSerialize(), 'Data not matching expected result');
    }

    /**
     * @return ScopeRepository
     */
    private function getScopeRepository()
    {
        $loaderProphecy = $this->prophesize(ProjectOverloader::class);
        $loaderProphecy->create('Rest\\Auth\\ScopeEntity')->willReturn(new ScopeEntity);
        $loader = $loaderProphecy->reveal();
        return new ScopeRepository($this->db, $loader);
    }
}