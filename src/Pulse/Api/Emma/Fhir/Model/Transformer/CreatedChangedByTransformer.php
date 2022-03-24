<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;

/**
 * Add created and changed by values from currentuser
 */
class CreatedChangedByTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var CurrentUserRepository
     */
    protected $currentUser;

    public function __construct(CurrentUserRepository $currentUser)
    {
        $this->currentUser = $currentUser;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        $saveTransformers = $model->getCol($model::SAVE_TRANSFORMER);

        foreach($saveTransformers as $columnName=>$value) {
            if (substr_compare($columnName, '_by', -3) === 0 && $value != $this->currentUser->getUserId()) {
                $model->set($columnName, $model::SAVE_TRANSFORMER, $this->currentUser->getUserId());
            }
        }
        /*if ($this->prefix) {
            \Gems_Model::setChangeFieldsByPrefix($model, $this->prefix, $this->currentUser->getUserId());
        }*/

        return $row;
    }

}
