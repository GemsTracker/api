<?php

namespace Ichom\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Gems\Tracker\Field\FieldAbstract;
use Gems\Tracker\Field\FieldInterface;
use Ichom\Repository\Diagnosis2TreatmentRepository;
use Ichom\StartStop;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Loader\Translate\TranslateableTrait;

class DiagnosisWizardStructureController extends RestControllerAbstract
{
    use TranslateableTrait;

    /**
     * @var array
     */
    protected $diagnosisTracks;

    /**
     * @var Diagnosis2TreatmentRepository
     */
    protected $diagnosis2TreatmentRepository;

    /**
     * @var \Zend_Translate_Adapter
     */
    protected $translateAdapter;
    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(\Zend_Translate_Adapter $translateAdapter, Diagnosis2TreatmentRepository $diagnosis2TreatmentRepository, \Gems_Tracker $tracker)
    {
        $this->translateAdapter = $translateAdapter;
        $this->diagnosis2TreatmentRepository = $diagnosis2TreatmentRepository;
        $this->tracker = $tracker;
    }

    /**
     * Get one or multiple rows from the model
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return EmptyResponse|JsonResponse
     */
    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $params = $request->getQueryParams();
        if (!isset($params['patientNr'], $params['organizationId'])) {
            return new JsonResponse(['error' => 'missing_data', 'message' => 'Patient number or organization ID missing as query params'], 400);
        }

        $structures = [
            'newDiagnosis' => $this->getNewDiagnosisStructure($params['organizationId']),
            'editDiagnosis' => $this->getEditDiagnosisStructure($params['organizationId']),
            'trackfields' => $this->getTrackfieldsPerTrack($params['patientNr'], $params['organizationId']),
        ];

        return new JsonResponse($structures);
    }

    protected function getDiagnosisTracks($organizationId)
    {
        if (!$this->diagnosisTracks) {
            $this->diagnosisTracks = $this->diagnosis2TreatmentRepository->getDiagnosisTracks($organizationId);
        }
        return $this->diagnosisTracks;
    }

    protected function pairsToKeyValue($pairs)
    {
        $keyValueList = [];
        foreach($pairs as $key=>$value) {
            $keyValueList[] = [
                'key' => $key,
                'value' => $value
            ];
        }
        return $keyValueList;
    }

    protected function getEditDiagnosisStructure($organizationId)
    {
        $structure = [
            'action' => [
                'label' => $this->_('Action'),
                'required' => true,
                'elementClass' => 'Radio',
                'type' => 'string',
                'multiOptions' => [
                    'show' => $this->_('Show'),
                    'edit' => $this->_('Edit'),
                    'delete' => $this->_('Delete - diagnosis / treatment'),
                ],
                'name' => 'action',
                'default' => 'show',
            ],
        ];

        $structure = array_merge($structure, $this->getBaseStructure($organizationId));
        $structure['removeDiagnosis'] = [
            'label' => $this->_('Remove diagnosis reason'),
            'elementClass' => 'Checkbox',
            'description' => $this->_('Was this a misdiagnosis at the time?'),
            'name' => 'removeDiagnosis',
            'hidden' => [
                'otherField' => [
                    'action',
                    '!=',
                    'delete',
                ],
            ],
        ];
        $structure['diagnosisChangeReason'] = [
            'label' => $this->_('Change diagnosis reason'),
            'elementClass' => 'Checkbox',
            'name' => 'diagnosisChangeReason',
            'description' => $this->_('Was the previous diagnosis a misdiagnosis?'),
        ];
        $structure['treatmentChangeReason'] = [
            'label' => $this->_('Change treatment reason'),
            'elementClass' => 'Checkbox',
            'name' => 'treatmentChangeReason',
            'description' => $this->_('Was the previous treatment wrong?'),
        ];

        unset($structure['track']['label']);
        $structure['track']['elementClass'] = 'Hidden';



        return $structure;
    }

    protected function getNewDiagnosisStructure($organizationId)
    {
        $structure = $this->getBaseStructure($organizationId);
        $structure['diagnosis']['label'] = $this->_('New diagnosis');
        $structure['treatment']['label'] = $this->_('New treatment');

        return $structure;
    }

    protected function getBaseStructure($organizationId)
    {
        $structure = [
            'track' => [
                'label' => $this->_('Add new track'),
                'required' => true,
                'elementClass' => 'Radio',
                'type' => 'string',
                'multiOptions' => $this->pairsToKeyValue($this->getDiagnosisTracks($organizationId)),
                'name' => 'track',
                'onChange' => [
                    'otherFieldValue' => [
                        'diagnosis' => null,
                        'treatment' => null,
                    ],
                ],
            ],
            'diagnosis' => [
                'label' => $this->_('Diagnosis'),
                'elementClass' => 'Select',
                'required' => true,
                'description' => 'Kies een diagnose waarvoor een meettraject gestart moet worden',
                'multiOptionSettings' => [
                    'reference' => 'ichom/diagnosis',
                    'key' => 'id',
                    'value' => 'name',
                    'onChange' => [
                        'treatment' => [
                            'multiOptionSettings' => [
                                'referenceData' => 'treatments',
                            ],
                        ],
                    ],
                    'filter' => [
                        'otherFieldValueJoin' => [
                            'track' => [
                                'trackId',
                            ],
                        ],
                    ],
                    'order' => [
                        'displayOrder',
                        'name',
                    ],
                ],
                'name' => 'diagnosis',
                'onChange' => [
                    'otherFieldValue' => [
                        'treatment' => null,
                    ],
                ],
            ],
            'treatment' => [
                'label' => $this->_('Treatment'),
                'required' => true,
                'elementClass' => 'Select',
                'multiOptionSettings' => [
                    'key' => 'id',
                    'value' => 'name',
                ],
                'order' => [
                    'name',
                ],
                'name' => 'treatment',
            ],
            'trackStartDate' => [
                'label' => $this->_('Start date'),
                'elementClass' => 'Html',
                'default' => 'Today',
                'name' => 'trackStartDate',
            ],
            'dossierTemplate' => [
                'elementClass' => 'hidden',
                'name' => 'dossierTemplate',
            ],
        ];

        return $structure;
    }

    protected function getTrackfieldsPerTrack($patientNr, $organizationId)
    {
        $trackFieldStructure = [];
        $tracks = $this->getDiagnosisTracks($organizationId);

        $skipFields = ['diagnosis', 'treatment'];

        $respondentModel = new \MUtil_Model_TableModel('gems__respondent2org');
        $respondent = $respondentModel->loadFirst([
            'gr2o_patient_nr' => $patientNr,
            'gr2o_id_organization' => $organizationId,
        ]);

        if ($respondent) {

            $respondentTrackContext = [
                'gr2t_id_user' => $respondent['gr2o_id_user'],
                'gr2t_id_organization' => $respondent['gr2o_id_organization'],
                'gr2t_id_respondent_track' => null,
            ];

            foreach ($tracks as $trackId => $trackName) {
                $trackFieldStructure[$trackId] = [];
                $engine = $this->tracker->getTrackEngine($trackId);
                $fields = $engine->getFieldsDefinition()->getFields();
                foreach ($fields as $field) {
                    if ($field instanceof FieldInterface) {
                        if ($field->isReadOnly()) {
                            continue;
                        }
                        $fieldId = $field->getFieldKey();
                        if ($field->getCode()) {
                            $fieldId = $field->getCode();
                        }
                        if (in_array($fieldId, $skipFields)) {
                            continue;
                        }
                        $settings = $field->getDataModelSettings();
                        $settings['name'] = $fieldId;
                        if (isset($settings['required'])) {
                            $settings['required'] = (bool)$settings['required'];
                        }
                        $dependencyChanges = $field->getDataModelDependyChanges($respondentTrackContext, true);
                        if ($dependencyChanges) {
                            $settings = array_merge($settings, $dependencyChanges);
                        }

                        if ($field->getCode() === StartStop::$treatmentDateFieldCode) {
                            $settings['disabled'] = 'disabled';
                            $settings['readonly'] = 'readonly';
                            $settings['onchange'] = null;
                        }

                        $trackFieldStructure[$trackId][$fieldId] = $settings;
                    }
                }
            }
        }
        return $trackFieldStructure;
    }
}
