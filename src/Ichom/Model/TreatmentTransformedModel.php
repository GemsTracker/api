<?php

namespace Ichom\Model;


use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\IntTransformer;
use Ichom\Model\Transformer\MedicalCategoryReferenceTransformer;

class TreatmentTransformedModel extends TreatmentModel
{
    public function applyBrowseSettings()
    {
        parent::applyBrowseSettings();

        $this->addLeftTable('gems__medical_categories',
            ['gmdc_id_medical_category' => 'gtrt_id_medical_category'],
            'gmdc',
            false
        );

        $this->addTransformer(new IntTransformer(['gtrt_id_treatment']));
        $this->addTransformer(new BooleanTransformer(['gtrt_active']));
        $this->addTransformer(new MedicalCategoryReferenceTransformer('gtrt_id_medical_category'));

    }
}
