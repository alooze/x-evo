<?php
namespace Modx\Ext\AutoFilter\AfInputs;
/**
 * Класс, подлючающий обработку конкретного типа поля в форме
 */
use Modx\Ext\Xparser\Xparser as Parser;

abstract class AbstractAfInput 
{
    public $fieldTpl;
    public $fieldData;
    public $fieldValues;

    public function __construct(Array $inputData)
    {
        $this->fieldData = $inputData;
        $this->fieldValues = array();
        $this->getFieldTpl();
    }

    /**
     * Возвращает шаблон текущего поля ввода
     * В текущей версии можно делать это прямым присваиванием строки
     * $this->fieldTpl = TPL
     */
    abstract public function getFieldTpl();

    /**
     * Функция для обработки параметров HTTP запроса, которые относятся к текущему полю формы
     */
    public function grabRequest()
    {
        if ($this->fieldData['fieldName'] != '') {
            $key = $this->fieldData['fieldName'];
            if (isset($_REQUEST[$key]) && $_REQUEST[$key] != '') {
                $this->fieldValues = $this->_toArray($_REQUEST[$key]);
                return $this->fieldValues;
            }
        }
        return $this->fieldValues;
    }

    /**
     * Функция для обработки имитаторов HTTP запроса, которые относятся к текущему полю формы
     * наденные имитаторы заменяют значения REQUEST
     */
    public function grabPreFilters($str='')
    {
        if ($str == '') return $this->fieldValues;
        
        // boo:46,47,48 
        // (установит $_REQUEST['boo'][0]=46,$_REQUEST['boo'][1]=47,$_REQUEST['boo'][2]=48) 
        // optpagetitle_select:Архив 
        // (установит $_REQUEST['optpagetitle_select']="Архив")
        $tmpAr = explode('|', $str);
        foreach ($tmpAr as $preFilterStr) {
            list($varName, $valStr) = explode(':', $preFilterStr);
            if ($varName == $this->fieldData['fieldName']) {
                $this->fieldValues = explode(',', $valStr);
                return $this->fieldValues;
            }
        }
        return $this->fieldValues;
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
    abstract public function filter();

    /**
     * Функция заполнения шаблона значениями
     * @param array $allValues - данные по значениям поля
     */
    public function parseTpl(Array $allValues)
    {
        /**
         * Разбиваем шаблон вывода с учетом возможного вложенного цикла
         */
        $chAr = preg_split('~(\[LOOP\]|\[/LOOP\])~s', $this->fieldTpl);

        if (count($chAr) < 3) {
            $wrapper = '[+inner+]';
            $body = $chAr[0];
        } else {
            $wrapper = $chAr[0].'[+inner+]'.$chAr[2];
            $body = $chAr[1];
        }        

        natsort($allValues); 
        $bodyStr = new Parser();
        $bodyStr->strToTpl('@CODE '.$body); 

        $wrapperStr = new Parser();
        $wrapperStr->strToTpl('@CODE '.$wrapper); 

        $phw['name'] = $this->fieldData['fieldName'];        
        $phw['inner'] = '';

        $iter = 0;

        foreach ($allValues as $key=>$value) {
            $ph['value'] = $value;
            $ph['key'] = $key;
            $iter++;
            $ph['iteration'] = $iter;

            if (in_array($value, $this->fieldValues)) {
                $ph['selected'] = ' selected="selected"';
                $ph['checked'] = ' checked="checked"';
            } else {
                $ph['selected'] = '';
                $ph['checked'] = '';
            }
            $phw['inner'].= $bodyStr->setPh($ph)->parse()->get();
            unset($ph);
        }

        $retCode = $wrapperStr->setPh($phw)->parse()->get();
        return $retCode;
    }

    /**
     * Очистка данных в REQUEST
     */
    public function cleanRequestData()
    {
        $this->fieldValues = array();        
    }

    /**
     * Принудительно приводим значение к массиву
     */
    protected function _toArray($value)
    {
        if (is_array($value))
            return $value;
        else return array($value);
    }

}