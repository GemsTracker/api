<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model;

use Pulse\Api\Emma\Fhir\Model\Transformer\EpisodeOfCareOrganizationTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\EpisodeOfCarePeriodTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\EpisodeOfCareStatusTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ExistingEpisodeOfCareTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientReferenceTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ResourceTypeTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\SourceIdTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\SourceTransformer;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\EpisodeOfCareRepository;
use Pulse\Api\Emma\Fhir\Repository\OrganizationRepository;
use Pulse\Api\Repository\RespondentRepository;
use Pulse\Model\ModelUpdateDiffs;

class EpisodeOfCareModel extends \Gems_Model_JoinModel
{
    use ModelUpdateDiffs;

    public function __construct(OrganizationRepository $organizationRepository, RespondentRepository $respondentRepository, EpdRepository $epdRepository, EpisodeOfCareRepository $episodeOfCareRepository)
    {
        parent::__construct('episodeOfCareModel', 'gems__episodes_of_care', 'gec', true);

        \Gems_Model::setChangeFieldsByPrefix($this, 'gec', 1);

        $this->addTransformer(new ResourceTypeTransformer('EpisodeOfCare'));
        $this->addTransformer(new SourceIdTransformer('gec_id_in_source'));
        $this->addTransformer(new SourceTransformer($epdRepository, 'gec_source'));

        $this->addTransformer(new ExistingEpisodeOfCareTransformer($episodeOfCareRepository, $epdRepository));

        $this->addTransformer(new EpisodeOfCareOrganizationTransformer($organizationRepository));
        $this->addTransformer(new PatientReferenceTransformer($respondentRepository, $epdRepository, 'patient', 'gec_id_user'));
        $this->addTransformer(new EpisodeOfCarePeriodTransformer());
        $this->addTransformer(new EpisodeOfCareStatusTransformer());
    }
}
