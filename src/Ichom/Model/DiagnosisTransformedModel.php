<?php

namespace Ichom\Model;


use Gems\Rest\Fhir\Model\Transformer\IntTransformer;
use Ichom\Model\Transformer\MedicalCategoryReferenceTransformer;
use Ichom\Model\Transformer\SubModelApiNamesTransformer;
use Ichom\Model\Transformer\SubTreatmentReferenceTransformer;

class DiagnosisTransformedModel extends Diagnosis2TrackModel
{
    public function __construct()
    {
        parent::__construct();
        $this->addLeftTable('gems__medical_categories',
            ['gmdc_id_medical_category' => 'gdt_id_medical_category'],
            'gmdc',
            false
        );
    }

    public function applyApiSettings()
    {
        parent::applyApiSettings();

        $this->addTransformer(new IntTransformer(['gdt_id_diagnosis', 'gdt_priority']));
        $this->addTransformer(new MedicalCategoryReferenceTransformer('gdt_id_medical_category'));
    }

    public function applyFullManyToMany()
    {
        parent::applyFullManyToMany();

        $this->addTransformer(new SubTreatmentReferenceTransformer());
    }
}
