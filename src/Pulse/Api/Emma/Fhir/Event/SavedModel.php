<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Event;

class SavedModel extends ModelEvent
{
    use EventDuration;

    /**
     * @var array New data after save
     */
    protected $newData;

    protected $oldData;

    public function getNewData()
    {
        return $this->newData;
    }

    public function getOldData()
    {
        return $this->oldData;
    }

    public function getUpdateDiffs()
    {
        if (!count($this->oldData)) {
            return array_keys($this->newData);
        }
        $diffValues = [];
        foreach($this->newData as $key=>$newValue) {
            if ($newValue instanceof \MUtil_Date) {
                $storageFormat = 'yyyy-MM-dd HH:mm:ss';
                if ($this->model->has($key, 'storageFormat')) {
                    $storageFormat = $this->model->get($key, 'storageFormat');
                }
                $newValue = $newValue->toString($storageFormat);
            }
            if (array_key_exists($key, $this->oldData) && $newValue === $this->oldData[$key]) {
                continue;
            }
            $diffValues[$key] = $newValue;
        }
        return $diffValues;
    }

    public function setNewData(array $newData)
    {
        $this->newData = $newData;
    }

    public function setOldData(array $oldData)
    {
        $this->oldData = $oldData;
    }
}
