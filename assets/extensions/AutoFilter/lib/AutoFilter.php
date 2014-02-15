<?php
namespace Modx\Ext\AutoFilter;

use Modx\Ext\Core\ModxExtensionCore as ModxExtensionCore;
use ActiveRecord\Config as ARC;
use Modx\Ext\Xparser\Xparser as Parser;
use Modx\Ext\AutoFilter\AfInputs\AfInputField as AfInputField;


/**
* Класс для управления автоматической фильтрацией в MODX Evolution
*/
class AutoFilter extends ModxExtensionCore
{
    public $config;

    /**
     * Модель для работы с ресурсами
     */
    protected $_model;

    /**
     * Массив данных по полям формы фильтрации и типам опций
     */
    protected $_taskAr;

    /**
     * Вывод формы фильтрации
     */
    protected $_formOutput;

    /**
     * Вывод результатов фильтрации
     */
    protected $_resultOutput;

    /**
     * Список ресурсов, которые нужно исключить из рассмотрения
     */
    protected $_excludeConditionList;

    /**
     * Массив сохраненных плейсхолдеров фильтрации
     */
    protected $_loadedPh;

    /**
     * Флаг необходимости использовать предфильтры, вместо REQUEST
     */
    protected $_usePreFilter;

    /**
     * Массив для хранения объектов типа "поле-ключ фильтра"
     */
    protected $_filterInputObsAr;

    public function __construct($params)
    {
        $this->config = $params;
        if (!isset($this->config['id'])) {
            $this->config['id'] = 'af';
        }
        if (!isset($this->config['resetStateKey'])) {
            $this->config['resetStateKey'] = 'Reset';
        }
        if (!isset($this->config['saveStateKey'])) {
            $this->config['saveStateKey'] = 'start';
        } 
        $this->_loadedPh = false;
        $this->_usePreFilter = false;
        parent::__construct();
    }

    /**
     * Задаем начальные значения для расширения
     */
    protected function _extInit()
    {
        $this->_extensionName = 'AutoFilter';
        $this->_extensionVersion = '1.0.0';

        /**
         * @Todo: пересмотреть механизм для большей гибкости
         *      используется для подключения языков и конфигов
         */
        $this->_baseDir = dirname(dirname(__FILE__)).'/';

        /**
         * @todo: Пока принудительно ставим русский язык, нужно будет заменить на механизм 
         *          определения языка
         */        
        $this->setExtLang('ru');

        /**
         * пытаемся подключить AR библиотеку
         */
        // $this->_loadArLib();

        /**
         * Подключаем нужную модель данных
         */
        $this->_loadArModel();
    }

    /**
     * Подключение библиотеки Active Records
     */
    protected function _loadArLib()
    {
        global $modx;
        if (!defined('PHP_ACTIVERECORD_AUTOLOAD_PREPEND')) {
            //подключаем собственный автозагрузчик AR
            require_once MODX_BASE_PATH.'assets/extensions/ActiveRecord/ActiveRecord.php';
        }

        $u = $modx->db->config['user'];
        $p = $modx->db->config['pass'];
        $h = $modx->db->config['host'];
        $d = trim($modx->db->config['dbase'], '`');
        // $c = $modx->db->config['charset'];

        // echo "mysql://$u:$p@$h/$d?charset=$c";

        $connections = array(
            // 'modx' => "mysql://$u:$p@$h/$d?charset=$c",
            'modx' => "mysql://$u:$p@$h/$d",
        );

        /**
         * @todo Почистить код
         */

        // initialize ActiveRecord
        // change the connection settings to whatever is appropriate for your mysql server 
        ARC::initialize(function($cfg) use ($connections)
        {
            $cfg->set_model_directory(MODX_BASE_PATH.'assets/extensions/AutoFilter/lib/models/');
            $cfg->set_connections($connections);
            $cfg->set_default_connection('modx');
            
        });
    }

    /**
     * Подключение класса для управления моделью данных
     */
    protected function _loadArModel()
    {
        // как временное решение
        if (!isset($this->config['mode']) || $this->config['mode'] == '') {
            $model = 'Modx\\Ext\\AutoFilter\\AfModels\\Base';
        } else {
            $mode = ucfirst(strtolower($this->config['mode']));
            $model = 'Modx\\Ext\\AutoFilter\\AfModels\\'.$mode;
        }
        $this->_model = new $model();
    }

    /**
     * Получаем необработанный полный список id для фильтрации
     */
    public function rawItemList()
    {
        return $this->_model->rawItemList($this->config);
    }

    /**
     * Получаем полный список id для фильтрации с учетом excludeIf 
     */
    public function preparedItemList($condStr='')
    {
        if ($condStr == '') {
            $condStr = $this->getExludedConditions();
        }        
        return $this->_model->preparedItemList($condStr);
    }

    /**
     * Получаем из формы фильтрации список опций и необходимых фильтров 
     */
    public function getTask()
    {
        // return $this->_model->rawItemList($this->config);
        /**
         * Подключаем парсер для шаблона результатов
         */
        $this->_resultOutput = new Parser();
        $this->_resultOutput->strToTpl($this->config['parseTpl']);

        /**
         * Подключаем парсер и получаем форму с плейсхолдерами
         */
        $this->_formOutput = new Parser();
        $tpl = $this->_formOutput->strToTpl($this->config['formTpl'])->getTpl();

        /**
         * Вырезаем плейсхолдеры и заполняем массив задач фильтрации
         */
        $re = '~\[\+([^\[\]]*\.)(([^\[\]]*)_([^:\[\]]*)(:[^\[\]]*)?)\+\]~sU';
        preg_match_all($re, $tpl, $resAr,2);
        // print_r($resAr);
        // print_r($re);
        // die();

        foreach ($resAr as $tmpAr) {
            /**
             * Заполняем массив заданий фильтрации
             */
            $fieldName = isset($tmpAr[5]) ? substr($tmpAr[5], 1) : $tmpAr[2];
            $rawFieldName = $tmpAr[3].'_'.$tmpAr[4];
            $this->_taskAr[$fieldName] = array('rawFieldName'=>$rawFieldName, 'fieldName'=>$fieldName, 'opt'=>$tmpAr[3], 'key'=>$tmpAr[4]);

            /**
             * Заполняем массив объектов формы фильтрации
             */
            $this->_filterInputObsAr[$fieldName] = AfInputField::getIF($tmpAr[4], $this->_taskAr[$fieldName]);
        }
        // print_r($this->_filterInputObsAr);
        // die();
        return $this->_taskAr;
    }

    /**
     * Проверка того, что получены данные из формы фильтрации
     */
    public function fromForm()
    {
        if (isset($_REQUEST['afid']) && $_REQUEST['afid'] == $this->config['id'])
            return true;
        else 
            return false;
    }
    

    /**
     * Проверка того, что в запросе пришел ключ сброса состояния
     */
    public function resetRequested()
    {
        if (isset($_REQUEST[$this->config['resetStateKey']]))
            return true;
        else 
            return false;
    }

    /**
     * Проверка того, что в сессии есть сохраненное состояние фильтра
     */
    public function hasSavedState()
    {
        $key = $this->config['id'].'Data';
        if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) 
            return true;
        else 
            return false;
    }

    /**
     * Проверка того, что были заданы предфильтры
     */
    public function hasPreFilter()
    {
        /**
         * @todo Сделать сразу в этом месте разбор предфильтра и сохранить в атрибуте
         */
        if (isset($this->config['preFilter']) && trim($this->config['preFilter']) != '')
            return true;
        else
            return false;
    }

    /**
     * Проверка того, что в запросе получена одна из переменных, 
     * указанных для сохранения состояния,
     * либо в параметрах сниппета есть указание на сохранение
     */
    public function needLoadState()
    {
        if ($this->config['saveState'] == 1) {
            return true;
        } else if (isset($this->config['saveStateKey'])) {
            $tmpAr = explode(',', $this->config['saveStateKey']);
            foreach ($tmpAr as $key) {
                if ($key != '' && isset($_REQUEST[$key])) {
                    return true;                    
                }
            }
        }
        return false;
    }

    /**
     * Загружаем сессию
     */
    public function loadState()
    {
        $key = $this->config['id'].'Data';
        $this->_loadedPh = $_SESSION[$key];
    }

    /**
     * Метод заполнения рабочих плейсхолдеров заранее подготовленными данными
     */
    public function preparePlaceholders()
    {
        /**
         * Если требуется восстановление сессии
         */
        if ($this->_loadedPh && is_array($this->_loadedPh)) {
            return $this->_loadedPh;
        }

        /**
         * Заполняем общие плейсхолдеры
         */
        $ph[$this->config['id'].'.id'] = $this->config['id'];

        /**
         * В этом месте у нас есть все данные, чтобы сформировать поля ввода в форме
         */
        if (!is_array($this->_taskAr)) {
            die('Where is my form data');
        } else {
            $phf = array();
            foreach ($this->_taskAr as $key => $dataAr) {
                // $phf[$this->config['id'].'.'.$key] = $this->prepareFormElement($dataAr);
                $inputAr = $this->prepareFormElement($key);
                $phf = array_merge($phf, $inputAr);
            }
        }
        
        $ph[$this->config['id'].'.form'] = $this->_formOutput->setPh($phf)->parse()->get();

        /**
         * Готовим данные для вывода результатов
         */

        // получаем строку для исключения из выборки
        $condStr = $this->getExludedConditions();

        // массив подготовленных к фильтрации id
        $fullList = $this->preparedItemList($condStr);

        // массив отфильтрованных id
        $filteredList = $this->filteredItemList();

        $phr[$this->config['id'].'.items'] = implode(',', $filteredList);
        $phr[$this->config['id'].'.items_count'] = count($fullList);
        $phr[$this->config['id'].'.items_show_count'] = count($filteredList);
        
        $ph[$this->config['id'].'.result'] = $this->_resultOutput->setPh($phr)->parse()->get();
        /**
         * Сохраняем результаты в сессию
         */
        $key = $this->config['id'].'Data';
        $_SESSION[$key] = $ph;

        return $ph;
    }

    /**
     * Получаем список отфильтрованных id 
     */
    public function filteredItemList()
    {
        return $this->_model->filteredItemList();
    }

    /**
     * Метод для сборки одного элемента формы
     *  @return array [PH_name] => PH_value
     */
    public function prepareFormElement($key)
    {
        $dataAr = $this->_taskAr[$key];
        if ($dataAr['fieldName'] == $dataAr['rawFieldName']) {
            $phKey = $this->config['id'].'.'.$dataAr['fieldName'];
        } else {
            $phKey = $this->config['id'].'.'.$dataAr['rawFieldName'].':'.$dataAr['fieldName'];
        }

        // Для текущего ключа получаем ранее сохраненный объект 
        // типа AfInputField и парсим список значений его методом
        $_AIF = $this->_filterInputObsAr[$key];

        // устанавливаем предфильтры
        // $_AIF->grabPreFilters($this->config['preFilter']);

        // проверяем наличие в REQUEST данной опции
        // $_AIF->grabRequest();

        // парсим полученный инпут
        $phVal = $_AIF->parseTpl($this->getOptionList($key));

        return array($phKey => $phVal);        
    }

    /**
     * Получаем текущий набор значений в форме фильтрации по данной опции
     */
    public function getOptionList($optKey)
    {
        /**
         * пока тупо выбираем все данные из БД
         * @todo пересмотреть подход
         */
        global $modx;

        //получаем список всех значений опции
        $idsAr = $this->_model->rawItemList();
        $idsStr = implode(',', $idsAr);

        // вырезаем префикс "opt" из названия опции        
        $optId = str_replace('opt', '', $this->_taskAr[$optKey]['opt']);

        // получаем строку для исключения из выборки
        $condStr = $this->getExludedConditions();
        
        $optValuesAr = $this->_model->getOptionList($optId, $condStr);
        return $optValuesAr;
    }

    /**
     * Функция для исключения указанных в параметрах значений из выборки
     */
    public function getExludedConditions()
    {
        if (!isset($this->config['excludeIf'])) return false;

        /**
         * Если еще не считывались условия исключения, заполняем атрибут
         */
        if (!isset($this->_excludeConditionList)) {
            // парсим список исключений
            $condAr = explode('|', $this->config['excludeIf']);
            foreach ($condAr as $condStr) {
                list($opt, $val, $sign) = explode(':', $condStr);
                $tmpAr[$opt][] = array('val'=>$val, 'sign'=>$sign);
            }
        } else {
            return $this->_excludeConditionList;
        }

        $resAr = array();
        
        foreach ($tmpAr as $optName=>$condArAr) {
            //если название опции не содержит opt, то это псевдоним, делаем замену
            if (strpos($optName, 'opt') !== false) {
                $optId = str_replace('opt', '', $optName);
            } else {
                $optId = str_replace('opt', '', $this->_taskAr[$optName]['opt']);
            }

            // получаем из модели строку ids            
            foreach ($condArAr as $condAr) {
                $idsAr = $this->_model->getIdsByCond($optId, $condAr);
                
                if (!$idsAr) continue;
                $resAr = array_merge($resAr, $idsAr);
            }
            
        }
        $resAr = array_unique($resAr);
        $this->_excludeConditionList = implode(',', $resAr);
        return $this->_excludeConditionList;
    }

    /**
     * Непосредственно фильтрация
     */
    public function makeFiltering()
    {
        // получаем из каждого инпута условия фильтрации
        if (!is_array($this->_taskAr)) return;

        foreach ($this->_taskAr as $key => $dataAr) {
            // получаем ранее сохраненный объект типа AfInputField 
            $_AIF = $this->_filterInputObsAr[$key];

            if ($this->_usePreFilter) {
                // устанавливаем предфильтры
                $_AIF->grabPreFilters($this->config['preFilter']);
            } else {
                // проверяем наличие в REQUEST данной опции
                $_AIF->grabRequest();
            }
            

            if (!is_array($_AIF->fieldValues) || count($_AIF->fieldValues) < 1) {
                // по этой опции фильтрование не требуется
                continue;
            }

            // в модель для фильтрации требуется передать опцию, значения и принцип отбора
            // опция и значения получены из атрибутов $_AIF
            // принцип отбора берем из метода filter
            $this->_model->makeFiltering($_AIF);
        }
    }

    /**
     * Сброс состояния фильтров и результатов
     */
    public function makeReset()
    {
        $this->cleanSession();
        $this->cleanForm();
    }

    /**
     * Очистка сессии
     */
    public function cleanSession()
    {
        unset($_SESSION[$this->config['id'].'Data']);
    }

    /**
     * Очистка состояния форм
     */
    public function cleanForm()
    {
        foreach ($this->_taskAr as $key => $dataAr) {
            // получаем ранее сохраненный объект типа AfInputField 
            $_AIF = $this->_filterInputObsAr[$key];
            $_AIF->cleanRequestData();
        }
    }

    /**
     * Имитируем запрос по данным предфильтра
     */
    public function imitateRequest()
    {
        $this->_usePreFilter = true;
    }
}