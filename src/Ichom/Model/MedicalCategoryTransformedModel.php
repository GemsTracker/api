<?php

namespace Ichom\Model;

use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\IntTransformer;

class MedicalCategoryTransformedModel extends MedicalCategoryModel
{
    public function applySettings($detailed = true)
    {
        parent::applySettings($detailed);

        $this->addTransformer(new IntTransformer(['gmdc_id_medical_category']));
        $this->addTransformer(new BooleanTransformer(['gmdc_active']));
    }
}
