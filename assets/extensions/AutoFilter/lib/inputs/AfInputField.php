<?php
namespace Modx\Ext\AutoFilter\AfInputs;
/**
 * Класс, подлючающий обработку конкретного типа поля в форме
 */

class AfInputField
{
    public static function getIF($type, Array $inputData)
    {
        $className = 'Modx\\Ext\\AutoFilter\\AfInputs\\Af'.ucfirst(strtolower($type));
        return new $className($inputData);
    }

}