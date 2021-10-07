<?php

namespace Pulse\Api\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;

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
}
