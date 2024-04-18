<?php

namespace Pulse\Api\Fhir\Model;


use Pulse\Api\Fhir\Model\Transformer\AppointmentIdentifierTransformer;
use Pulse\Api\Fhir\Model\Transformer\AppointmentInfoTransformer;
use Pulse\Api\Fhir\Model\Transformer\AppointmentMedicalCategoryInfoTransformer;
use Pulse\Api\Fhir\Repository\AppointmentMedicalCategoryRepository;
use Zalt\Loader\ProjectOverloader;

class AppointmentModel extends \Gems\Rest\Fhir\Model\AppointmentModel
{
    /**
     * @var ProjectOverloader
     */
    protected $overLoader;

    public function __construct()
    {
        parent::__construct();
        $this->addTransformer(new AppointmentIdentifierTransformer());
        $this->addTransformer(new AppointmentInfoTransformer());
        $this->set('identifier', [
           'label' => 'identifier',
        ]);
        $this->set('medicalCategory', [
            'filterValue' => true
        ]);
        $this->set('medical-category', [
            'filterValue' => true
        ]);
    }

    public function afterRegistry()
    {
        parent::afterRegistry();
        /**
         * @var AppointmentMedicalCategoryRepository $appointmentMedicalCategoryRepository
         */
        $appointmentMedicalCategoryRepository = $this->overLoader->getServiceManager()->get(AppointmentMedicalCategoryRepository::class);
        $this->addTransformer(new AppointmentMedicalCategoryInfoTransformer($appointmentMedicalCategoryRepository));

    }
}
