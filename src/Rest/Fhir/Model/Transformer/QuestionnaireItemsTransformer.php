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

    protected function getItems(array $questionInformation, $lsSurveyId)
    {
        $groups = [];

        $questionGroupIndexes = [];

        foreach($questionInformation as $questionCode=>$question) {

            if (!isset($groups[$question['group']])) {
                $groups[$question['group']] = [
                    'linkId' => $lsSurveyId . 'X' . $question['group'],
                    'type' => 'group',
                    'text' => $question['groupName'],
                    'item' => [],
                ];
            }

            $isSub = false;
            if (isset($question['class']) && $question['class'] == 'question_sub') {
                $isSub = true;

            }

            $item = [
                'linkId' => $questionCode,
                'text' => $question['question'],
                'type' => $this->getQuestionType($question['type'], $isSub),
            ];

            if ($item['type'] == 'group') {
                $index = count($groups[$question['group']]['item']);
                $questionGroupIndexes[$questionCode] = $index;
                $item['item'] = [];
            }

            if ($item['type'] != 'group' && isset($question['answers']) && is_array($question['answers'])) {
                $item['answerOption'] = [];
                foreach($question['answers'] as $answerCode=>$answerLabel) {
                    $item['answerOption'][] = [
                        'code' => $answerCode,
                        'display' => $answerLabel,
                    ];
                }
            }

            if ($isSub) {
                $questioCodeBlocks = explode('_', $questionCode);
                $parentName = reset($questioCodeBlocks);
                if (isset($questionGroupIndexes[$parentName])) {
                    if (isset($groups[$question['group']]['item'][$questionGroupIndexes[$parentName]], $groups[$question['group']]['item'][$questionGroupIndexes[$parentName]]['item'])) {
                        $groups[$question['group']]['item'][$questionGroupIndexes[$parentName]]['item'][] = $item;
                    }
                }
                continue;
            }

            $groups[$question['group']]['item'][] = $item;
        }

        return array_values($groups);
    }

    protected function getQuestionType($type, $isSub)
    {
        switch ($type) {
            case '5': // 5 point choice
                return 'choice';
            case '!': //List - dropdown
            case 'L': //LIST drop-down/radio-button list
                return 'choice';
            case 'O': // List with comment
                return 'group';
            case 'F': // Array
                if ($isSub) {
                    return 'choice';
                }
                return 'group';
            case 'B': // Array 10 point
                if ($isSub) {
                    return 'choice';
                }
                return 'group';
            case 'A': // Array 5 point
                if ($isSub) {
                    return 'choice';
                }
                return 'group';
            case 'E': // Array increase decrease same
                if ($isSub) {
                    return 'choice';
                }
                return 'group';
            case ':': // Array number
                if ($isSub) {
                    return 'decimal';
                }
                return 'group';
            case ';': // Array text
                if ($isSub) {
                    return 'string';
                }
                return 'group';
            case 'C': // Array Yes No Uncertain
                if ($isSub) {
                    return 'choice';
                }
                return 'group';
            case 'H': // Array by column
                if ($isSub) {
                    return 'choice';
                }
                return 'group';
            case '1': // Array dual scale
                return null;
            case 'D': // Date + time
                return 'dateTime';
            case '*': // Equation
                return 'string';
            case '|': // File upload
                return 'attachment';
            case 'G': // Gender
                return 'choice';
            case 'I': // Language switch
                return 'choice';
            case 'K': // Multiple numerical
                if ($isSub) {
                    return 'decimal';
                }
                return 'group';
            case 'N': // Numerical
                return 'decimal';
            case 'R':  // Ranking
                return null;
            case 'X': // Text display
                /* No validty control ; but always reset the value to null ? */
                return 'display'; // Can not be set : set it to null
            case 'Y': // Yes / No
                return 'choice';
            case 'U': // Huge text
            case 'T': // Long text
            case 'Q': // Multiple text
            case 'S': // Short text
                return 'string';
            case 'M':
                if ($isSub) {
                    return 'choice';
                }
                return 'group';
            case 'P': // Multiple choice with comments
                if ($isSub) {
                    return 'choice';
                }
                return 'group';
            default:
                return null;
        }
    }

    /**
     * @param $sourceId
     * @return \Gems_Tracker_Source_SourceInterface
     * @throws \Gems_Exception_Coding
     */
    protected function getSource($sourceId)
    {
        $source = $this->tracker->getSource($sourceId);

        return $source;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            $source = $this->getSource($row['gsu_id_source']);
            $questionInformation = $source->getQuestionInformation($this->language, $row['gsu_id_survey'], $row['gsu_surveyor_id']);
            if ($questionInformation) {
                $item = $this->getItems($questionInformation, $row['gsu_surveyor_id']);
                $data[$key]['item'] = $item;
            }
        }

        return $data;
    }
}
