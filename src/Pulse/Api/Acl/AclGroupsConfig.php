<?php

return [
    'intramed-dashboard' => [
        'description' => 'Pulse Intramed dashboard',
        'permissions' => [
            'api.extreme-values' => ['GET'],
            'api.organizations' => ['GET'],
            'api.outcome-variables' => ['GET'],
            'api.patient-numbers' => ['GET'],
            'api.respondents' => ['GET', 'PATCH'],
            'api.respondent-tracks' => ['GET', 'PATCH', 'POST', 'DELETE'],
            'api.surveys' => ['GET'],
            'api.tokens' => ['GET', 'PATCH'],
            'api.tracks' => ['GET'],

            'chartdata' => ['GET'],
            'correct-token' => ['PATCH'],
            'insert-track-token' => ['POST'],
            'respondent-track-fields' => ['GET', 'PATCH'],
            'survey-questions' => ['GET'],
            'token-answers' => ['GET'],
            'track-fields' => ['GET'],
            'treatment-episodes' => ['GET'],
            'treatments-with-norms' => ['GET'],
        ]
    ],
    'emma-transfer' => [
        'description' => 'EMMA EPD up and downstream endpoints',
        'permissions' => [
            'ping' => ['GET'],
            'api.emma/respondents' => ['POST', 'OPTIONS'],
            'api.emma/tokens' => ['GET', 'OPTIONS'],
            'emma/survey-questions' => ['GET', 'OPTIONS'],
            'emma/token-answers' => ['GET', 'OPTIONS'],
        ]
    ],
    'r-dashboard' => [
        'description' => 'R Dashboard',
        'permissions' => [
            'api.fhir/patient' => ['GET'],
            'api.fhir/questionnaire-task' => ['GET', 'PATCH'],
            'api.fhir/questionnaire-response' => ['GET'],
            'api.fhir/care-plan' => ['GET'],
            'api.data-set/data' => ['GET'],
            'api.ichom/diagnosis' => ['GET'],
            'api.ichom/treatment' => ['GET'],
            'api.ichom/medical-category' => ['GET'],
            'api.reference-data' => ['GET'],
            'other-patient-numbers' => ['GET'],
        ],
    ],
];
