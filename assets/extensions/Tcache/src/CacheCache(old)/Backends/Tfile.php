<?php
namespace CacheCache\Backends;

class Tfile extends File
{
    /**
     * Constructor
     *
     * Options:
     *  - dir: the directory where to store files (default: /tmp)
     *  - sub_dirs: whether to use sub directories for namespaces (default: false)
     *  - id_as_filename: whether to use the id as the filename (default: false)
     *  - file_extension: a file extension to be added to the filename, with the leading dot (default: none)
     *
     * @param array $options
     */
    public function __construct(array $options=array())
    {
        global $modx;

        $dir = isset($options['dir']) ? $options['dir'] : MODX_BASE_PATH.'assets/cache/tcache';
        $this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $this->subDirs = isset($options['sub_dirs']) && $options['sub_dirs'] or false;
        $this->idAsFilename = isset($options['id_as_filename']) && $options['id_as_filename'] or true;
        $this->fileExtension = isset($options['file_extension']) ? $options['file_extension'] : '.cache';
    }

    /**
     * Устанавливаем кеш без сериализации и TTL
     */
    public function setRaw($id, $value)
    {
        $filename = $this->filename($id);
        $dirname = dirname($filename);
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }
        file_put_contents($filename, $value);
    }

    /**
     * Читаем кеш без преобразования
     */
    public function getRaw($id)
    {
        if ($this->exists($id)) {
            $filename = $this->filename($id);
            $value = file_get_contents($filename);
            
            return $value;
        }
        return null;
    }

    /**
     * Удаляем файл кеша без преобразования имени
     */
    public function deleteRaw($id)
    {
        if (file_exists($id)) {
            unlink($id);
        }
        return true;
    }

    /**
     * Получаем список всех файлов по заданному условию
     */
    public function getAll($expression)
    {
        $ret = glob($this->dir.DIRECTORY_SEPARATOR.$expression);
        return $ret;
    }
}