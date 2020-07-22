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

        $this->set('id', 'label', $this->_('id'));
        $this->set('grc_success', 'label', $this->_('active'), 'apiName', 'active');
        $this->set('gender', 'label', $this->_('gender'));
        $this->set('grs_birthday', 'label', $this->_('birthDate'), 'apiName', 'birthDate');

        $this->set('name', 'label', $this->_('name'));
        $this->set('gr2o_created', 'label', $this->_('created'), 'apiName', 'created');
        $this->set('gr2o_changed', 'label', $this->_('changed'), 'apiName', 'changed');

        // search options
        $this->set('family', 'label', $this->_('name'));
        $this->set('given', 'label', $this->_('name'));



        $this->set('telecom', 'label', $this->_('telecom'));
        // search options
        $this->set('email', 'label', $this->_('email'));
        $this->set('phone', 'label', $this->_('phone'));

        $this->set('managingOrganization', 'label', $this->_('managingOrganization'));
        // search options
        $this->set('organization', 'label', $this->_('organization'));
        $this->set('organization_name', 'label', $this->_('organization_name'));
        $this->set('organization_code', 'label', $this->_('organization_code'));

        $this->addTransformer(new PatientHumanNameTransformer());
        $this->addTransformer(new PatientTelecomTransformer());
        $this->addTransformer(new ManagingOrganizationTransformer('gr2o_id_organization', true));
        $this->addTransformer(new BooleanTransformer(['grc_success']));

    }
}
