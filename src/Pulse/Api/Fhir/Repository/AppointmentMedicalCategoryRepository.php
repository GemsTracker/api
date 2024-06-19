<?php

namespace Pulse\Api\Fhir\Repository;

use Gems\Rest\Cache\Psr6CacheHelpers;
use Gems\Rest\Db\ResultFetcher;
use Psr\Cache\CacheItemPoolInterface;

class AppointmentMedicalCategoryRepository
{
    use Psr6CacheHelpers;

    protected $activityPaternToMedicalCategoryId = [
        '%OK %' => 52,
        '%Acne%' => 57,
        '%Botox%' => 61,
        'Comedonen%'  => 57,
        '%PC%' => 52,
        '%HV%' => 57,
        '%peel%' => 57,
        '%Microneedling%' => 57,
        'Coagulatie%' => 57,
        '%borstver%' => 52,
        '%CO2%' => 61,
        '%couperose%' => 57,
        '%Skinbooster%' => 57,
        '%ontharen%' => 57,
        '%armlift%' => 52,
        '%borstlift%' => 52,
        '%filler%' => 61,
        '%oog%' => 52,
        '%correctie%' => 52,
        '%implantaten%' => 52,
        '%huid%' => 57,
        '%vaatjes%' => 57,
        '%aliaxin%' => 61,
        '%borstvorm%' => 52,
        '%prothese%' => 52,
        'Consult facelift%' => 52,
        '%hals/face%' => 52,
        '%lipofilling%' => 52,
        '%liposuctie%' => 52,
        '%melasma%' => 57,
        '%mommy%' => 52,
        'consult n.a.v. facelift' => 52,
        '%voorhoofdslift%' => 52,
        '%wenkbrauwlift%' => 52,
        '%cosmelan%' => 57,
        '%inspuitingen%' => 61,
        '%VPK%' => 52,
        '%dermamelan%' => 57,
        '%dual plane%' => 52,
        '%dysport%' => 61,
        '%easy phen%' => 57,
        '%glycolzuur%' => 57,
        '%emla%' => 57,
        '%gips%' => 52,
        '%harmonyca%' => 61,
        '%hechtingen%' => 52,
        '%juvederm%' => 61,
        '%lymfe%' => 57,
        '%laser%' => 57,
        '%tampons%' => 52,
        '%phenol%' => 57,
        '%profh%' => 61,
        '%radiesse%' => 61,
        '%rosacea%' => 57,
        'wondcontrole%' => 52,
        'VZ %' => 52,
        'Verwijsconsult Derma' => 61,
        '%teosyal%' => 61,
        '%MRI%' => 52,
        '2e Consult' => 52,
        'Telefonisch consult arts%' => 52,
        'Vervolg Consult' => 52,
        'Tussentijdse controle injectables' => 61,
        'Telefonische afspraak injectables' => 61,
        'Extra controle injectables' => 61,
    ];

    protected $activity2MedicalCategoryKey = 'activity2medicalCategory';

    protected $activitiesCacheTags = ['activities'];

    protected $agenda;

    protected $cache;

    protected $medicalCategory2ActivtyKey = 'medicalCategory2activity';
    private $resultFetcher;

    public function __construct(
        \Gems_Agenda $agenda,
        CacheItemPoolInterface $cache,
        ResultFetcher $resultFetcher
    ) {
        $this->agenda = $agenda;
        $this->cache = $cache;
        $this->resultFetcher = $resultFetcher;
    }

    public function getActivityIdsPerMedicalCategory($medicalCategoryId, $organizationId)
    {
        $medicalCategory2Activity = $this->getMedicalCategory2Activity($organizationId);
        if (isset($medicalCategory2Activity[$medicalCategoryId])) {
            return $medicalCategory2Activity[$medicalCategoryId];
        }
        return null;
    }

    protected function getMedicalCategories()
    {
        $cacheKey = 'medicalCategoryPairs';
        if ($medicalCategories = $this->getCacheItem($cacheKey)) {
            return $medicalCategories;
        }

        $query = 'SELECT gmdc_id_medical_category, gmdc_name FROM gems__medical_categories ORDER BY gmdc_name';
        $medicalCategories = $this->resultFetcher->fetchPairs($query);

        $this->setCacheItem($cacheKey, $medicalCategories, ['medicalCategories']);

        return $medicalCategories;
    }

    public function getMedicalCategoryName($medicalCategoryId)
    {
        $medicalCategories = $this->getMedicalCategories();
        if (isset($medicalCategories[$medicalCategoryId])) {
            return $medicalCategories[$medicalCategoryId];
        }
        return null;
    }

    protected function getMedicalCategory2Activity($organizationId)
    {
        $cacheKey = $this->medicalCategory2ActivtyKey . '_' . $organizationId;
        if ($medicalCategory2Activity = $this->getCacheItem($cacheKey)) {
            return $medicalCategory2Activity;
        }

        $activity2MedicalCategory = $this->getActivity2MedicalCategory($organizationId);
        $medicalCategory2Activity = [];
        foreach($activity2MedicalCategory as $activityId => $medicalCategoryId) {
            if (!isset($medicalCategory2Activity[$medicalCategoryId])) {
                $medicalCategory2Activity[$medicalCategoryId] = [];
            }
            $medicalCategory2Activity[$medicalCategoryId][] = $activityId;
        }
        $this->setCacheItem($cacheKey, $medicalCategory2Activity, $this->activitiesCacheTags);

        return $medicalCategory2Activity;
    }

    public function getMedicalCategoryFromAppointmentActivityId($activityId, $organizationId)
    {
        $activity2MedicalCategory = $this->getActivity2MedicalCategory($organizationId);
        if (isset($activity2MedicalCategory[$activityId])) {
            return $activity2MedicalCategory[$activityId];
        }
        return null;
    }

    protected function getActivity2MedicalCategory($organizationId)
    {
        $cacheKey = $this->activity2MedicalCategoryKey . '_' . $organizationId;
        if ($activity2MedicalCategory = $this->getCacheItem($cacheKey)) {
            return $activity2MedicalCategory;
        }

        $activities = $this->getAllAgendaActivities($organizationId);
        $regexPatterns = $this->getRegexPaterns();

        $activity2MedicalCategory = [];
        foreach($regexPatterns as $regex => $medicalCategoryId) {
            $matches = preg_grep('/^' . $regex . '/i', $activities);
            foreach($matches as $activityId => $activityName) {
                $activity2MedicalCategory[$activityId] = $medicalCategoryId;
            }
        }

        $this->setCacheItem($cacheKey, $activity2MedicalCategory, $this->activitiesCacheTags);

        return $activity2MedicalCategory;
    }

    protected function getAllAgendaActivities($organizationId)
    {
        return $this->agenda->getActivities($organizationId);
    }

    protected function getRegexPaterns()
    {
        $regexPaterns = [];
        foreach($this->activityPaternToMedicalCategoryId as $searchString => $medicalCategory) {
            $pattern = str_replace('%', '.*', preg_quote($searchString, '/'));
            $regexPaterns[$pattern] = $medicalCategory;
        }
        return $regexPaterns;
    }


}