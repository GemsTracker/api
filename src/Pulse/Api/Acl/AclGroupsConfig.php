<?php

return [
    'intramed-dashboard' => [
        'api.extreme-values' => ['GET'],
        'api.organizations' => ['GET'],
        'api.outcome-variables' => ['GET'],
        'api.respondents' => ['GET', 'PATCH'],
        'api.respondent-tracks' => ['GET', 'PATCH', 'POST'],
        'api.surveys' => ['GET'],
        'api.tokens' => ['GET'],
        'api.tracks' => ['GET'],

        'chartdata' => ['GET'],
        'insert-track-token' => ['GET'],
        'respondent-track-fields' => ['PATCH'],
        'survey-questions' => ['GET'],
        'token-answers' => ['GET'],
        'track-fields' => ['GET'],
        'treatment-episodes' => ['GET'],
    ]
];