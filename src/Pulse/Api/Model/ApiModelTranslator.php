<?php


namespace Pulse\Api\Model;


class ApiModelTranslator
{
    /**
     * @var array Api translations
     */
    public $translations = [];

    public function __construct($translations=null)
    {
        if ($translations) {
            $this->setTranslations($translations);
        }
    }

    /**
     * Flip multidimensional translation array
     * @param $array
     * @return array
     */
    protected function flipArray($array)
    {
        $flippedArray = [];
        foreach($array as $key=>$value) {
            if (is_array($value)) {
                $flippedArray[$key] = $this->flipArray($value);
                continue;
            }
            $flippedArray[$value] = $key;
        }
        return $flippedArray;
    }

    protected function translateList($row, $translations)
    {
        $translatedRow = [];
        foreach($row as $colName=>$value) {

            if (is_array($value) && isset($translations[$colName]) && is_array($translations[$colName])) {
                foreach($value as $key=>$subrow) {
                    $translatedRow[$colName] = $this->translateList($value, $translations[$colName]);
                }
                continue;
            }

            if (isset($translations[$colName])) {
                $translatedRow[$translations[$colName]] = $value;
            } else {
                $translatedRow[$colName] = $value;
            }
        }

        return $translatedRow;
    }

    /**
     * Translate a row with api values
     *
     * @param $row
     * @param
     * @return array
     */
    public function translateRow($row, $reversed=false)
    {
        $translations = $this->translations;
        if ($reversed) {
            $translations = $this->flipArray($translations);
        }
        $row = $this->translateList($row, $translations);

        return $row;
    }

    /**
     * Set translations array
     *
     * @param $translations array
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;
    }
}