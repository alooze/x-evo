<?php
namespace Modx\Ext\Core;

/**
* Родительский класс для работы расширений
*/
abstract class ModxExtensionCore
{
    protected $_extensionName;
    protected $_extensionVersion;
    protected $_baseDir;
    protected $_lang;

    public function getExtVersion()
    {
        return $this->_extensionVersion;
    }

    public function getExtName()
    {
        return $this->_extensionName;
    }

    abstract protected function _extInit();

    public function __construct()
    {
        $this->_extInit();
    }

    public function lang($str)
    {
        return isset($this->_lang[$str]) ? $this->_lang[$str] : '%'.$str.'%';
    }

    public function setExtLang($langKey)
    {
        if (file_exists($this->_baseDir.'lang/'.$langKey.'.inc.php')) {
            $this->_lang = include $this->_baseDir.'lang/'.$langKey.'.inc.php';
            return true;
        } else {
            $this->_lang = array();
            return false;
        }
    }
}