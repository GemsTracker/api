<?php


namespace Gems\Rest\Fhir\Model;

use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\PractitionerHumanNameTransformer;
use Gems\Rest\Fhir\Model\Transformer\PractitionerTelecomTransformer;

class PractitionerModel extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('practitioner', 'gems__agenda_staff', 'gas', true);
        $this->addLeftTable('gems__staff', ['gas_id_user' => 'gsf_id_user'], 'gsf', true);

        $this->addColumn(new \Zend_Db_Expr('\'Practitioner\''), 'resourceType');

        $this->set('resourceType', 'label', 'resourceType');

        $this->addColumn(new \Zend_Db_Expr("CASE gsf_gender WHEN 'M' THEN 'male' WHEN 'F' THEN 'female' ELSE 'unknown' END"), 'gender');

        $this->set('gas_id_staff', 'label', 'id', 'apiName', 'id');

        $this->set('gas_active', 'label', 'active', 'apiName', 'active');
        $this->set('name', 'label', 'name');
        $this->set('gender', 'label', 'gender');
        $this->set('telecom', 'label', 'telecom');

        $this->set('family', 'label', 'family');
        $this->set('given', 'label', 'given');
        $this->set('email', 'label', 'email');
        $this->set('phone', 'label', 'phone');

        $this->addTransformer(new PractitionerHumanNameTransformer());
        $this->addTransformer(new PractitionerTelecomTransformer());
        $this->addTransformer(new BooleanTransformer(['gas_active']));

    }
}
