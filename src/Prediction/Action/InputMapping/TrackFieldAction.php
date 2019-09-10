<?php


namespace Prediction\Action\InputMapping;


use Gems\Tracker\Field\FieldAbstract;
use Gems\Tracker\Field\MultiselectField;
use Gems\Tracker\Field\SelectField;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class TrackFieldAction implements MiddlewareInterface
{
    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(\Gems_Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return \Psr\Http\Message\ResponseInterface|JsonResponse
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $trackId = $id = $request->getAttribute('trackId');

        $engine = $this->tracker->getTrackEngine($trackId);
        $fields = $engine->getFieldsDefinition();
        $model = $fields->getMaintenanceModel();

        $fieldData = $model->load();

        $filteredData = [];
        foreach($fieldData as $field) {
            $code = $field['gtf_field_code'];
            if ($code) {
                $filteredData[$code] = [
                    'name' => $field['gtf_field_name'],
                    'type' => $field['gtf_field_type'],
                ];

                if ($field['gtf_field_values'] !== null) {
                    $options = explode(FieldAbstract::FIELD_SEP, $field['gtf_field_values']);
                    $filteredOptions = [];
                    foreach($options as $option) {
                        $filteredOptions[$option] = $option;
                    }
                    $filteredData[$code]['options'] = $filteredOptions;
                }
            }
        }

        return new JsonResponse($filteredData, 200);
    }
}