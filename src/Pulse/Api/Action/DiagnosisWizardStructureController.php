<?php

namespace Pulse\Api\Action;

class DiagnosisWizardStructureController extends \Ichom\Action\DiagnosisWizardStructureController
{
    protected function getBaseStructure($organizationId)
    {
        $structure = parent::getBaseStructure($organizationId);
        $structure['dossierNotes'] = [
            'elementClass' => 'hidden',
            'name' => 'dossierNotes',
        ];

        return $structure;
    }
}