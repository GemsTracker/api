<?php

namespace Gems\Rest\Fhir\Model;

use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\ManagingOrganizationTransformer;
use Gems\Rest\Fhir\Model\Transformer\PatientHumanNameTransformer;
use Gems\Rest\Fhir\Model\Transformer\PatientManagingOrganizationTransformer;
use Gems\Rest\Fhir\Model\Transformer\PatientTelecomTransformer;

class PatientModel extends \Gems_Model_RespondentModel
{
    public function __construct()
    {
        parent::__construct();

        $this->addTable('gems__organizations', ['gr2o_id_organization' => 'gor_id_organization'], 'gor', false);

        $this->addColumn(new \Zend_Db_Expr('CONCAT(gr2o_patient_nr, "@", gr2o_id_organization)'), 'id');
        //$this->addColumn('grc_success', 'active');
        $this->addColumn(new \Zend_Db_Expr("CASE grs_gender WHEN 'M' THEN 'male' WHEN 'F' THEN 'female' ELSE 'unknown' END"), 'gender');

        $this->addColumn(new \Zend_Db_Expr('\'Patient\''), 'resourceType');

        $this->set('resourceType', 'label', 'resourceType');

        $this->set('id', 'label', 'id');
        $this->set('grc_success', 'label', 'active', 'apiName', 'active');
        $this->set('gender', 'label', 'gender');
        $this->set('grs_birthday', 'label', 'birthDate', 'apiName', 'birthDate');

        $this->set('name', 'label', 'name');
        $this->set('gr2o_created', 'label', 'created', 'apiName', 'created');
        $this->set('gr2o_changed', 'label', 'changed', 'apiName', 'changed');

        // search options
        $this->set('family', 'label', 'name');
        $this->set('given', 'label', 'name');



        $this->set('telecom', 'label', 'telecom');
        // search options
        $this->set('email', 'label', 'email');
        $this->set('phone', 'label', 'phone');

        $this->set('managingOrganization', 'label', 'managingOrganization');
        // search options
        $this->set('organization', 'label', 'organization');
        $this->set('organization_name', 'label', 'organization_name');
        $this->set('organization_code', 'label', 'organization_code');

        $this->addTransformer(new PatientHumanNameTransformer());
        $this->addTransformer(new PatientTelecomTransformer());
        $this->addTransformer(new ManagingOrganizationTransformer('gr2o_id_organization', true));
        $this->addTransformer(new BooleanTransformer(['grc_success']));
    }

    /**
     * Calculates the total number of items in a model result with certain filters
     *
     * @param array $filter Filter array, num keys contain fixed expresions, text keys are equal or one of filters
     * @param array $sort Sort array field name => sort type
     * @return integer number of total items in model result
     * @throws \Zend_Db_Select_Exception
     */
    public function getItemCount($filter = true, $sort = true)
    {
        $count = parent::getItemCount($filter, $sort);

        return $count;
    }
}
