<?php
namespace Modx\Ext\AutoFilter\AfInputs;
/**
 * Класс, подлючающий обработку поля типа select в форме
 */

use Modx\Ext\Xparser\Xparser as Parser;

class AfSelect extends AbstractAfInput
{
    
    /**
     * Конструктор берем из родителя
     */
    // public function __construct(Array $inputData)
    // {
    //     parent::__construct($inputData);
    // }
    
    /**
     * Возвращает шаблон текущего поля ввода
     * В текущей версии можно делать это прямым присваиванием строки
     * $this->fieldTpl = TPL
     */
    public function getFieldTpl()
    {
        $this->fieldTpl = <<<TPL
        <select name="[+name+]" id="[+name+]">
            <option value="">Выберите</option>
            [LOOP]
            <option value="[+value+]" [+selected+][+disabled+]>[+value+]</option>
            [/LOOP]
        </select>
TPL;
        return $this->fieldTpl;
    }

    /**
     * Функция для задания правил фильтрации по значению поля
     *  пока есть такие варианты выборки
     *  1. одно из значений массива
     *  if (in_array($optVal, $filterVal)) {
     *  2. точное соответствие
     *  if ($optVal == $filterVal) {
     *  3. непустое значение
     *  if ($optVal != '') {
     *  4. больше-меньше
     *  if ($optVal < $filterVal[0] || $optVal > $filterVal[1]) {
     * @return string
     */
    public function filter()
    {
        // Для select нужно точное соответствие
        return '{opt} = "{val}"';
    }

}