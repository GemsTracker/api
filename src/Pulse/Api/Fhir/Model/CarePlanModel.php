<?php

namespace Pulse\Api\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\CarePlanActityTransformer;
use Gems\Rest\Fhir\Model\Transformer\CareplanAuthorTransformer;
use Gems\Rest\Fhir\Model\Transformer\CarePlanContributorTransformer;
use Gems\Rest\Fhir\Model\Transformer\CarePlanPeriodTransformer;
use Gems\Rest\Fhir\Model\Transformer\IntTransformer;
use Gems\Rest\Fhir\Model\Transformer\PatientReferenceTransformer;
use Pulse\Api\Fhir\Model\Transformer\CarePlanInfoTransformer;

class CarePlanModel extends \Gems\Rest\Fhir\Model\CarePlanModel
{
    public function __construct()
    {
        parent::__construct();
        $this->addColumn(new \Zend_Db_Expr('
            CASE 
                WHEN gr2t_reception_code = \'staff-only\' THEN \'1\'  
                ELSE \'0\' 
            END'), 'staffOnly');

        $this->addTransformer(new BooleanTransformer(['staffOnly']));
    }

    protected function addTransformers()
    {
        $this->addTransformer(new IntTransformer(['gr2t_id_respondent_track']));
        $this->addTransformer(new PatientReferenceTransformer('subject'));
        $this->addTransformer(new CareplanAuthorTransformer());
        $this->addTransformer(new CarePlanContributorTransformer());
        $this->addTransformer(new CarePlanPeriodTransformer());
        $this->addTransformer(new CarePlanInfoTransformer());

        $tracker = $this->loader->getTracker();
        $this->addTransformer(new CarePlanActityTransformer($tracker));
    }
}
