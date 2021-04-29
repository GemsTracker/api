<?php

namespace Gems\Rest\Fhir\Model\Transformer;


class QuestionnaireItemsTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var string Language
     */
    protected $language;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(\Gems_Tracker $tracker, $language)
    {
        $this->tracker = $tracker;
        $this->language = $language;
    }

    protected function getItemsFromAnswers(array $tokenAnswers, array $surveyInformation)
    {
        $items = [];

        foreach($tokenAnswers as $answers) {
            $tokenItems = [];
            foreach($answers as $key=>$answer) {
                if(!isset($surveyInformation[$key])) {
                    continue;
                }
                $answerItem = [
                    'linkId' => $key,
                    'text' => $surveyInformation[$key]['question'],
                ];

                if (isset($surveyInformation[$key]['answers']) && is_array($surveyInformation[$key]['answers']) &&
                    isset($surveyInformation[$key]['answers'][$answer])) {

                    $display = $surveyInformation[$key]['answers'][$answer];
                    if (is_numeric($display)) {
                        if (is_int($display)) {
                            $display = (int)$display;
                        } else {
                            $display = (float)$display;
                        }
                    }

                    $answerItem['answer']['valueCoding'] = [
                        'code' => $answer,
                        'display' => $display,
                        'system' => null, // Should reference a place to view the answer options
                    ];
                } else {

                    switch ($surveyInformation[$key]['type']) {
                        case 'N':
                            if (is_int($answer)) {
                                $answerItem['answer']['valueDecimal'] = (int)$answer;
                            } else {
                                $answerItem['answer']['valueInteger'] = (float)$answer;
                            }
                            break;
                        default:
                            $answerItem['answer']['valueString'] = str_replace('\n', "\n", $answer);
                    }
                }

                $tokenItems[] = $answerItem;
            }

            $items[$answers['token']] = $tokenItems;
        }

        return $items;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        $tokensBySource = [];
        $sourceSurveyIds = [];
        foreach($data as $row) {
            $tokensBySource[$row['gsu_id_source']][$row['gto_id_survey']][] = $row['gto_id_token'];
            $sourceSurveyIds[$row['gto_id_survey']] = $row['gsu_surveyor_id'];
        }

        $tokenItems = [];

        foreach ($tokensBySource as $sourceId => $tokensBySurvey) {
            $source = $this->tracker->getSource($sourceId);
            foreach ($tokensBySurvey as $surveyId => $tokenIds) {
                $answers = $source->getRawTokenAnswerRows(['token' => $tokenIds], $surveyId, $sourceSurveyIds[$surveyId]);
                $surveyInformation = $source->getQuestionInformation($this->language, $surveyId, $sourceSurveyIds[$surveyId]);
                $tokenItems = array_merge($tokenItems, $this->getItemsFromAnswers($answers, $surveyInformation));
            }
        }

        foreach ($data as $key => $row) {
            $tokenId = str_replace('-', '_', $row['gto_id_token']);
            if (isset($tokenItems[$tokenId])) {
                $data[$key]['item'] = $tokenItems[$tokenId];
            }
        }

        return $data;
    }
}
