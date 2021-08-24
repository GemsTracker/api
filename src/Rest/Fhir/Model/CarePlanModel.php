<?php

namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\CarePlanActityTransformer;
use Gems\Rest\Fhir\Model\Transformer\CareplanAuthorTransformer;
use Gems\Rest\Fhir\Model\Transformer\CarePlanContributorTransformer;
use Gems\Rest\Fhir\Model\Transformer\CarePlanInfoTransformer;
use Gems\Rest\Fhir\Model\Transformer\CarePlanPeriodTransformer;
use Gems\Rest\Fhir\Model\Transformer\IntTransformer;
use Gems\Rest\Fhir\Model\Transformer\PatientReferenceTransformer;

class CarePlanModel extends \Gems_Model_JoinModel
{
    /**
     * @var \Gems_Loader
     */
    protected $loader;

    public function __construct()
    {
        parent::__construct('carePlan', 'gems__respondent2track', 'gr2t', true);
        $this->addTable(
            'gems__respondents',
            [
                'gr2t_id_user' => 'grs_id_user',
            ],
            'grs',
            false
        );
        $this->addTable(
            'gems__respondent2org',
            [
                'gr2t_id_user' => 'gr2o_id_user',
                'gr2t_id_organization' => 'gr2o_id_organization'
            ],
            'gr2o',
            false
        );
        $this->addTable('gems__tracks',
            [
                'gr2t_id_track' => 'gtr_id_track',
            ],
            'gtr',
            false
        );
        $this->addTable('gems__organizations',
            [
                'gr2t_id_organization' => 'gor_id_organization',
            ],
            'gor',
            false
        );
        $this->addTable('gems__reception_codes',
            [
                'gr2t_reception_code' => 'grc_id_reception_code',
            ],
            'grc',
            false
        );

        $this->addColumn(new \Zend_Db_Expr('\'CarePlan\''), 'resourceType');
        $this->addColumn(new \Zend_Db_Expr('\'intent\''), 'order');
        $this->addColumn(new \Zend_Db_Expr('
CASE 
    WHEN grc_success = \'OK\' THEN \'active\' 
    WHEN gr2t_reception_code = \'retract\' THEN \'revoked\' 
    ELSE \'unknown\' 
END'), 'status');


        $this->set('resourceType', ['label' => 'resourceType']);
        $this->set('gr2t_id_respondent_track', ['label' => 'id', 'apiName' => 'id']);
        $this->set('intent', ['label' => 'intent']);
        $this->set('status', ['label' => 'status']);
        $this->set('period', ['label' => 'period']);
        $this->set('gtr_track_name', ['label' => 'title', 'apiName' => 'title']);
        $this->set('gtr_code', ['label' => 'code', 'apiName' => 'code']);
        $this->set('gr2t_created', ['label' => 'created', 'apiName' => 'created']);
        $this->set('contributor', ['label' => 'contributor']);
        $this->set('supportingInfo', ['label' => 'supportingInfo']);
        $this->set('activity', ['label' => 'activity']);

        $this->set('gr2t_start_date', ['label' => 'start', 'apiName' => 'start']);
        $this->set('gr2t_end_date', ['label' => 'end', 'apiName' => 'end']);

        //$this->set('gtr_track_name', ['label' => 'trackName', 'apiName' => 'track_name']);
        //$this->set('gtr_code', ['label' => 'trackCode', 'apiName' => 'track_code']);
        $this->set('gtr_id_track', ['label' => 'trackId', 'apiName' => 'track_id']);

        $this->set('patient', 'label', 'patient');
        $this->set('patient.email', 'label', 'patient.email');
    }

    public function afterRegistry()
    {

        parent::afterRegistry();
        $this->addTransformers();
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
