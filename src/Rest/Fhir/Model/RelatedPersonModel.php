<?php

namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\PatientReferenceTransformer;
use Gems\Rest\Fhir\Model\Transformer\RelatedPersonHumanNameTransformer;
use Gems\Rest\Fhir\Model\Transformer\RelatedPersonTelecomTransformer;

class RelatedPersonModel extends \MUtil_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('relatedPerson', 'gems__respondent_relations', 'grr', true);

        $this->addTable('gems__respondents', ['grr_id_respondent' => 'grs_id_user']);
        $this->addTable('gems__respondent2org', ['grs_id_user' => 'gr2o_id_user']);

        $this->addColumn(new \Zend_Db_Expr('\'RelatedPerson\''), 'resourceType');
        $this->addColumn(new \Zend_Db_Expr("CASE grr_gender WHEN 'M' THEN 'male' WHEN 'F' THEN 'female' ELSE 'unknown' END"), 'gender');

        $this->set('resourceType', 'label', 'resourceType');

        $this->set('grr_id_relation', 'label', 'id', 'apiKey', 'id');
        $this->set('grr_active', 'label', 'active', 'apiKey', 'active');

        $this->set('patient', 'label', 'patient');

        // Relationship according to official specs should be a set code as in https://www.hl7.org/fhir/valueset-relatedperson-relationshiptype.html
        // For now the relation type used.
        $this->set('grr_type', 'label', 'relationship', 'apiKey', 'relationship');

        $this->set('name', 'label', 'name');
        $this->set('telecom', 'label', 'telecom');

        $this->set('gender', 'label', 'gender');
        $this->set('grr_birthdate', 'label', 'birthdate', 'apiKey', 'birthdate');

        $this->set('family', 'label', $this->_('family'));
        $this->set('given', 'label', $this->_('given'));
        $this->set('email', 'label', $this->_('email'));


        $this->addTransformer(new BooleanTransformer(['grr_active']));
        $this->addTransformer(new PatientReferenceTransformer('patient'));
        $this->addTransformer(new RelatedPersonHumanNameTransformer());
        $this->addTransformer(new RelatedPersonTelecomTransformer());
    }
}
