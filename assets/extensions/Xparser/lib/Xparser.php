<?php
namespace Modx\Ext\Xparser;

use Modx\Ext\Core\ModxExtensionCore as ModxExtensionCore;

/**
 * Реализует собственный парсинг строк
 * Предусмотрены конструкции вида:
 *    [+pagetitle@parent:toUpper+]
 *    [+pagetitle@NN:snippet=`options`:modifier+]
 *    где
 *    pagetitle - наименование атрибута в модели (поле в БД)
 *    NN,parent - "адрес" ресурса, ID или псевдопеременные parent
 *    modifier (toUpper, snippet) - модификаторы вывода
 *
 *    Использование:
 *  $xp = new Modx\Ext\Xparser\Xparser();
 *  $xp->strToTpl('@FILE '.MODX_BASE_PATH.'assets/path/to/template/file.tpl')
 *      ->parse()
 *      ->show();
 *  или
 *  $xp->strToTpl('@FILE '.MODX_BASE_PATH.'assets/path/to/template/file.tpl')
 *      ->parse()
 *      ->get();
 *  
 *  Аналог $modx->getChunk('tpl')
 *      $xp->strToTpl('tpl')->get();
 *
 *  Простая подстановка значений и вывод
 *      $ph = array('name'=>'Sam', 'email'=>'sam@mail.com');
 *      $xp->strToTpl('@CODE <div class="name">[+pre.name+]</div> [+pre.email+]')
 *          ->setPh($ph, 'pre')
 *          ->parse()
 *          ->show();
 *  
 */

class Xparser extends ModxExtensionCore
{
    /**
     * 
     */
    public $output;
    public $template;
    public $errorsAr;
    public $placeholdersAr;

    /**
     * Ограничение количества проходов
     */
    protected $_maxPasses;

    /**
     * Массив для хранения заданий (псевдо-транзакции)
     */
    protected $_taskAr;

    /**
     * Внутренний счетчик проходов
     */
    protected $_iteration;

    public function __construct($maxpass=10)
    {
        parent::__construct();
        $this->_maxPasses = (intval($maxpass) > 0 && intval($maxpass) < 10) ? intval($maxpass) : 10;
        $this->_iteration = 0;
    }

    /**
     * Задаем начальные значения для расширения
     */
    protected function _extInit()
    {
        $this->_extensionName = 'Xparser';
        $this->_extensionVersion = '0.1a';

        /**
         * @Todo: пересмотреть механизм для большей гибкости
         *      используется для подключения языков и конфигов
         */
        $this->_baseDir = dirname(dirname(__FILE__)).'/';

        /**
         * @Todo: Пока принудительно ставим русский язык, нужно будет заменить на механизм 
         *          определения языка
         */        
        $this->setExtLang('ru');

        /**
         * Обнуляем при запуске вывод (пока не нужно, заглушка)
         */
        // $this->setOutput();

        /**
         * Обнуляем при запуске список ошибок (заглушка)
         */
        // $this->setError();
    }
  
    /**
     * Задает значение для вывода
     */
    public function setOutput($str='') 
    {
        $this->output = $str;
    }

    /**
     * Приращивает значение для вывода в конце
     */
    public function append($str='') 
    {
        $this->output.= $str;
    }

    /**
     * Приращивает значение для вывода вначале
     */
    public function prepend($str='') 
    {
        $this->output = $str.$this->output;
    }

    /**
     * Вывод текущего состояния строки
     */
    public function show() 
    {
        echo $this->output;
        return $this;
    }

    /**
     * Возврат текущего состояния строки
     */
    public function get() 
    {
        return $this->output;
    }

    /**
     * Возврат текущего шаблона
     */
    public function getTpl() 
    {
        return $this->template;
    }
  
    /**
     * Добавляет ошибку в массив всех ошибок
     */
    public function setError($str='') 
    {
        $this->errorsAr[] = $str;
    }

    /**
     * Получаем шаблон из строки вызова
     */
    public function strToTpl($str) 
    {
        global $modx;
    
        $pre = substr($str, 0, 5);

        switch ($pre) {
            case '@CODE':
                $str = str_replace(array('@eq', '@amp'), 
                                    array('=', '&'), 
                                    trim(substr($str, 6))
                                    );
            break;

            case '@FILE':
                $fName = trim(substr($str, 6));
                if (file_exists($fName)) {
                    $str = file_get_contents($fName);
                } else {
                    $str = 'File '.$fName.' not found';
                }
            break;

            default:
                $str = $modx->getChunk($str);
            break;
        }   
        $this->template = $str; 
        // if ($toOutput) $this->setOutput($str);
        // return $str;
        return $this;
    }

    /**
     * Добавляем в массив плесхолдеров указанные плейсхолдеры
     */
    public function setPh($phAr, $prefix='')
    {
        if ($prefix != '') {
            $prefix.= '.';
        }
        if (!is_array($phAr)) {
            $this->setError($this->lang('Bad placeholders'));
            die($this->lang('Bad placeholders'));
        } else {
            foreach ($phAr as $key => $value) {
                $this->placeholdersAr[$prefix.$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Подстановка плейсхолдеров
     */
    public function parse($pre='[+', $suf='+]')
    {
        if ($this->template == '') {
            return $this;
        }
        $this->output = $this->template;

        if (!is_array($this->placeholdersAr)) {
          return $this;
        }
        
        //пока просто подстановка
        foreach ($this->placeholdersAr as $key => $val) {
            $this->output = str_replace($pre.$key.$suf, $val, $this->output);
        } 

        return $this;

        // if ($this->_iteration >= $this->_maxPasses) {
        //     return $this;
        // } else {
        //     $this->_iteration++;
        // }

        // /**
        //  * Хеш строки для сравнения до и после прохода парсера
        //  */
        // $startHash = md5($this->output);

        // /**
        //  * Разбираем строку, получаем плейсхолдеры и модификаторы
        //  */
        // // preg_match_all('~\[(\+|\*|\()([^:\+\[\]]+)([^\[\]]*?)(\1|\))\]~s', $template, $matches);
        // $pre = $this->_escape($pre);
        // $suf = $this->_escape($suf);
        // $re = '~'.$pre.'~s';
    }

    /**
     * Экранирование служебных символов в регулярках
     */
    protected function _escape($str)
    {
        $from = array('[','+','*','.','-','{','}',']','?','^');
        $to =  array('\[','\+','\*','\.','\-','\{','\}','\]','\?','\^');
        return strtr($str, $from, $to);
    }

  
  // function setPh($phs, $values, $pre = '[+', $suf = '+]') {
  //   if (!is_array($phs) && !is_array($values)) {
  //     $this->output = str_replace($pre.$phs.$suf, $values, $this->output);
  //     return;
  //   }
  //   if (is_array($phs) && is_array($values)) {
  //     foreach ($phs as $ind=>$ph) {
  //       $this->output = str_replace($pre.$ph.$suf, $values[$ind], $this->output);
  //     }
  //     return;
  //   }
  //   $this->setError('Невозможно установить значения плейсхолдеров');
  // }

  // function parseTpl($tpl, $phs, $values, $pre = '[+', $suf = '+]') {
  //  if (!is_array($phs) && !is_array($values)) {
  //     $tpl = str_replace($pre.$phs.$suf, $values, $tpl);
  //     return $tpl;
  //   }
  //   if (is_array($phs) && is_array($values)) {
  //     foreach ($phs as $ind=>$ph) {
  //       $tpl = str_replace($pre.$ph.$suf, $values[$ind], $tpl);
  //     }
  //     return $tpl;
  //   }
  //   $this->setError('Невозможно обработать шаблон');
  //   return ('Невозможно обработать шаблон');
  // }

  // function quickParseTpl($tpl, array $phs) {
  //   return $this->parseTpl($tpl, array_keys($phs), array_values($phs));
  // }
  
    public function stripTags($html) {
        // $html = preg_replace('~\[\*(.*?)\*\]~', "", $html); //tv
        // $html = preg_replace('~\[\[(.*?)\]\]~', "", $html); //snippet
        // $html = preg_replace('~\[\!(.*?)\!\]~', "", $html); //snippet
        // $html = preg_replace('~\[\((.*?)\)\]~', "", $html); //settings
        $html = preg_replace('~\[\+(.*?)\+\]~', "", $html); //placeholders
        // $html = preg_replace('~{{(.*?)}}~', "", $html); //chunks
        $html = preg_replace('~\[#(.*?)#\]~', "", $html); // internal placeholders
        return $html;
    }
}