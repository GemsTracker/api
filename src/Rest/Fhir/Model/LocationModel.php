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

        $this->set('glo_id_location', 'label', $this->_('id'), 'apiName', 'id');

        $this->set('glo_active', 'label', $this->_('status'), 'apiName', 'status');
        $this->set('glo_name', 'label', $this->_('name'), 'apiName', 'name');
        $this->set('telecom', 'label', $this->_('telecom'));

        $this->set('address', 'label', $this->_('address'));
        // Search options
        $this->set('address-city', 'label', $this->_('address-city'));
        $this->set('address-country', 'label', $this->_('address-country'));
        $this->set('address-postalcode', 'label', $this->_('address-postalcode'));



        $this->addTransformer(new LocationStatusTransformer());
        $this->addTransformer(new LocationTelecomTransformer());
        $this->addTransformer(new LocationAddressTransformer());
        $this->addTransformer(new BooleanTransformer(['glo_active']));

    }
}
