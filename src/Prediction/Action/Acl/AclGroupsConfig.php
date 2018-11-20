<?php
return [
    'prediction-model-mapping' => [
        'api.prediction/tracks' => ['GET'],
        'api.prediction/rounds' => ['GET'],
        'api.prediction/prediction-models' => ['GET'],
        'api.prediction/prediction-model-mappings' => ['GET'],
        'api.prediction/prediction-model-with-mappings' => ['GET', 'POST'],
        'api.prediction/survey-questions' => ['GET'],
        'api.prediction/track-fields' => ['GET'],
        'api.prediction/respondents' => ['GET'],
    ],
    'prediction-charts' => [
        'api.prediction/chart-definitions' => ['GET'],
        'api.prediction/charts' => ['GET'],
    ],
];