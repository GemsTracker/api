<?php

namespace Pulse\Api\Model;


use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\IntTransformer;
use Pulse\Api\Model\Transformer\FlatRespondentTrackFieldTransformer;
use Pulse\Api\Model\Transformer\PatientNameTransformer;

class RespondentDossierTemplatesModel extends \Pulse\Model\RespondentDossierTemplatesModel
{
    public function __construct()
    {
        parent::__construct();
        $this->addTransformer(new BooleanTransformer(['has_template']));
        $this->addTransformer(new IntTransformer(['gr2t_id_respondent_track', 'gdt_id_diagnosis', 'gtrt_id_treatment']));

        $this->addTable('gems__respondents', ['gr2t_id_user' => 'grs_id_user'], 'grs', false);

    }

    public function afterRegistry()
    {
        $this->addTransformer(new PatientNameTransformer());
        $this->addTransformer(new FlatRespondentTrackFieldTransformer());
    }

    public function applyBrowseSettings()
    {
        parent::applyBrowseSettings();
        $this->set('gdt_id_diagnosis', [
            'label' => $this->_('Diagnosis ID'),
            'apiName' => 'diagnosis',
        ]);

        $this->set('gtrt_id_treatment', [
            'label' => $this->_('Treatment ID'),
            'apiName' => 'treatment',
        ]);

        $this->set('patientFullName', [
            'label' => $this->_('Patient name'),
            'apiName' => 'patientFullName',
        ]);
    }
}
