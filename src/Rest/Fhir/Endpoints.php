<?php

namespace Gems\Rest\Fhir;


class Endpoints
{
    const PREFIX = 'fhir';

    const APPOINTMENT = 'fhir/appointment/';

    const CARE_PLAN = 'fhir/care-plan/';

    const EPISODE_OF_CARE = 'fhir/episode-of-care/';

    const LOCATION = 'fhir/location/';

    const ORGANIZATION = 'fhir/organization/';

    const PATIENT = 'fhir/patient/';

    const PRACTITIONER = 'fhir/practitioner/';

    const RELATED_PERSON = 'fhir/related-person/';

    const QUESTIONNAIRE = 'fhir/questionnaire/';

    const QUESTIONNAIRE_TASK = 'fhir/questionnaire-task/';

    CONST TREATMENT = 'fhir/treatment/';

    public static function getEndpointByResourceType($resourceType)
    {
        switch (strtolower($resourceType)) {
            case 'appointment':
                return static::APPOINTMENT;
            case 'careplan':
                return static::CARE_PLAN;
            case 'episodeofcare':
                return static::EPISODE_OF_CARE;
            case 'location':
                return static::LOCATION;
            case 'organization':
                return static::ORGANIZATION;
            case 'patient':
                return static::PATIENT;
            case 'practitioner':
                return static::PRACTITIONER;
            case 'relatedperson':
                return static::RELATED_PERSON;
            case 'questionnaire':
                return static::QUESTIONNAIRE;
            case 'questionnairetask':
                return static::QUESTIONNAIRE_TASK;
            case 'treatment':
                return static::TREATMENT;
        }

        return null;
    }
}
