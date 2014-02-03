<?php
namespace Modx\Ext\Xparser;

use Modx\Ext\Core\ModxExtensionCore as ModxExtensionCore;

class Xparser extends ModxExtensionCore
{
    public $output;
    public $errorsAr;

    public function __construct()
    {
        parent::__construct();
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
        $this->setOutput();

        /**
         * Обнуляем при запуске список ошибок (заглушка)
         */
        $this->setError();
    }
  
    /**
     * Задает значение для вывода
     */
    public function setOutput($str='') 
    {
        $this->output = $str;
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
        return $str;
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

  // function printOutput() {
  //   //echo $this->output;
  //   echo $this->stripTags($this->output);
  // }
  
  // function stripTags($html) {
  //   $t= preg_replace('~\[\*(.*?)\*\]~', "", $html); //tv
  //   $t= preg_replace('~\[\[(.*?)\]\]~', "", $t); //snippet
  //   $t= preg_replace('~\[\!(.*?)\!\]~', "", $t); //snippet
  //   $t= preg_replace('~\[\((.*?)\)\]~', "", $t); //settings
  //   $t= preg_replace('~\[\+(.*?)\+\]~', "", $t); //placeholders
  //   $t= preg_replace('~{{(.*?)}}~', "", $t); //chunks
  //   return $t;
  // }
}