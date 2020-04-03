<?php

namespace Gems\Rest\Fhir\Model;

class PatientModel extends \Gems_Model_RespondentModel
{
    public function __construct()
    {
        parent::__construct();

        $this->addColumn(new \Zend_Db_Expr('CONCAT(gr2o_patient_nr, "@", gr2o_id_organization)'), 'identifier');
        //$this->addColumn('grc_success', 'active');
        $this->addColumn(new \Zend_Db_Expr("CASE grs_gender WHEN 'M' THEN 'male' WHEN 'F' THEN 'female' ELSE 'unknown' END"), 'gender');

        $this->set('identifier', 'label', $this->_('identifier'));
        $this->set('grc_success', 'label', $this->_('active'), 'apiName', 'active');
        $this->set('gender', 'label', $this->_('gender'));
        $this->set('grs_birthday', 'label', $this->_('birthDate'), 'apiName', 'birthDate');

        $this->addTransformer(new PatientHumanNameTransformer());
        $this->addTransformer(new PatientTelecomTransformer());
        $this->addTransformer(new BooleanTransformer(['grc_success']));

    }
}
