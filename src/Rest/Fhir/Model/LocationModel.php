<?php


namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\LocationAddressTransformer;
use Gems\Rest\Fhir\Model\Transformer\LocationStatusTransformer;
use Gems\Rest\Fhir\Model\Transformer\LocationTelecomTransformer;

class LocationModel extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('location', 'gems__locations', 'glo', true);

        $this->addColumn(new \Zend_Db_Expr('\'Location\''), 'resourceType');

        $this->set('resourceType', 'label', 'resourceType');

        $this->set('glo_id_location', 'label', 'id', 'apiName', 'id');

        $this->set('glo_active', 'label', 'status', 'apiName', 'status');
        $this->set('glo_name', 'label', 'name', 'apiName', 'name');
        $this->set('telecom', 'label', 'telecom');

        $this->set('address', 'label', 'address');
        // Search options
        $this->set('address-city', 'label', 'address-city');
        $this->set('address-country', 'label', 'address-country');
        $this->set('address-postalcode', 'label', 'address-postalcode');



        $this->addTransformer(new LocationStatusTransformer());
        $this->addTransformer(new LocationTelecomTransformer());
        $this->addTransformer(new LocationAddressTransformer());
        $this->addTransformer(new BooleanTransformer(['glo_active']));

    }
}
