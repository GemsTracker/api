<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model;

use Pulse\Api\Emma\Fhir\Model\Transformer\ConditionCodeAndNameTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ConditionEpisodeOfCareTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ExistingConditionTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientReferenceTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ResourceTypeTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\SourceIdTransformer;
use Pulse\Api\Emma\Fhir\Repository\ConditionRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\EpisodeOfCareRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportEscrowLinkRepository;
use Pulse\Api\Repository\RespondentRepository;
use Pulse\Model\ModelUpdateDiffs;

class ConditionModel extends \Gems_Model_JoinModel
{
    use ModelUpdateDiffs;

    public function __construct(
        RespondentRepository $respondentRepository,
        EpdRepository $epdRepository,
        EpisodeOfCareRepository $episodeOfCareRepository,
        ConditionRepository $conditionRepository,
        ImportEscrowLinkRepository $importEscrowLinkRepository)
    {
        parent::__construct('conditionModel', 'gems__medical_conditions', 'gmco', true);

        \Gems_Model::setChangeFieldsByPrefix($this, 'gmco', 1);

        $this->addTransformer(new ResourceTypeTransformer('Condition'));
        $this->addTransformer(new SourceIdTransformer('gmco_id_source'));
        $this->addTransformer(new ExistingConditionTransformer($conditionRepository, $epdRepository));

        $this->addTransformer(new PatientReferenceTransformer($respondentRepository, $epdRepository, 'subject', 'gmco_id_user'));
        $this->addTransformer(new ConditionEpisodeOfCareTransformer($episodeOfCareRepository, $importEscrowLinkRepository));
        $this->addTransformer(new ConditionCodeAndNameTransformer());
    }

    public function afterRegistry()
    {
        $this->set('gmco_status', [
            'label' => 'Status',
            'apiName' => 'clinicalStatus',
        ]);

        $this->set('gmco_onset_date', [
            'label' => 'Onset date',
            'apiName' => 'onsetDateTime',
        ]);

        $this->set('gmco_abatement_date', [
            'label' => 'Abatement date',
            'apiName' => 'abatementDateTime',
        ]);
    }
}
