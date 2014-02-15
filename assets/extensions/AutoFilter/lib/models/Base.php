<?php
/**
 * Класс-модель для работы автофильтра с полями ресурса и значениями TV
 * Требуется описать что является "товарами", а что - "опциями"
 * + реализовать методы из интерфейса
 * @todo перевод на AR отложен на версию 1.1
 * @todo убрать лишние преобразования в массивы и обратно
 */
namespace Modx\Ext\AutoFilter\AfModels;

use Modx\Ext\AutoFilter\AfModels\IAbstractAfModel;
// use ActiveRecord\Model;

// class Base extends Model implements IAbstractAfModel
class Base implements IAbstractAfModel
{
    
    /**
     * Название таблицы для получения атрибутов
     */
    // static $table_name='ob_site_content';

    public $rawList;
    public $preparedList;
    public $filteredList;

    /**
     * Получение полного списка первичных ключей, до всех выборок и сортировок
     * на основании параметров вызова
     *  @param array $params Данные из вызова сниппета для получения списка
     *  @return array
     */
    public function rawItemList($params='') 
    {
        global $modx;
        
        if (!is_array($params)) {
            if ($this->rawList != '') {
                return explode(',', $this->rawList);
            } else {
                return array();
            }
        }                

        /**
         * Тут надо использовать $modx->getChildIds() для более быстрой разработки
         * Но для сторонних таблиц это будет неприменимо, в других моделях 
         * строим дерево своими силами
         */

        if (isset($params['ids'])) {
            $ids = $params['ids'];
            $itemIdsAr = explode(',', $ids);
        } else {
            $itemIdsAr = array();
        }

        if (isset($params['parents'])) {
            $parents = $params['parents'];
            $scanLevel = isset($params['scanLevel']) ? $params['scanLevel'] : '10'; //глубина сканирования каталога
            $parAr = explode(',', $parents);

            foreach ($parAr as $parId) {
                if (intval($parId) >= 0) {
                    $chAr = $modx->getChildIds($parId, $scanLevel);
                    if (is_array($chAr)) {
                        $itemIdsAr = array_merge($itemIdsAr, $chAr);
                    }
                }
            }
        }
        $this->rawList = implode(',', $itemIdsAr);
        return $itemIdsAr;
    }

    /**
     * Получение списка id ресурсов, на основании заданных условий
     * @todo перевести на использование AR
     * @param string $optId Строка, идентифицирующая поле(опцию) для выборки
     * @param array $condAr Массив вида 'sign'=>условие, 'val'=>значение
     * @return array
     */
    public function getIdsByCond($optId, $condAr)
    {
        global $modx;

        if ($optId == '') return array();
        if (!is_array($condAr)) return array();

        $wr = '"~"';

        switch ($condAr['sign']) {
            case 'lt': $sign = '<'; break;
            case 'le': $sign = '<='; break;
            case 'gt': $sign = '>'; break;
            case 'ge': $sign = '>='; break;
            case 'eq': $sign = '='; break;
            case 'ne': $sign = '<>'; break;
            case 'like': 
                $sign = 'LIKE'; 
                $wr = '"%~%"';
                break;
            case 'nlike': 
                $sign = 'NOT LIKE'; 
                $wr = '"%~%"';
                break;
            default: $sign = false; break;
        }

        if (!$sign) return array();

        $val = $modx->db->escape($condAr['val']);
        $val = str_replace('~', $val, $wr);
        
        if (intval($optId) != 0) {
            //делаем предварительную выборку из TV
            $res = $modx->db->select('contentid', $modx->getFullTableName('site_tmplvar_contentvalues'), 'contentid IN ('.$this->rawList.') AND tmplvarid = '.$optId.' AND value '.$sign.' '.$val);

        } else {
            //делаем предварительную выборку из content
            $res = $modx->db->select('id', $modx->getFullTableName('site_content'), 'id IN ('.$this->rawList.') AND '.$optId.' '.$sign.' '.$val);
        }

         $cnt = $modx->db->getRecordCount($res);
        if (!$cnt) return array();
        while ($row = $modx->db->getRow($res)) {
            $retAr[] = isset($row['id']) ? $row['id'] :$row['contentid'];
        }

        $retAr = array_unique($retAr);
        return $retAr;
    }

    /**
     * Метод для получения всего списка значений для данной опции
     * @todo перевести на использование AR
     * @param string $optId Строка для идентификации опции
     * @param string $condStr Список id для исключения из выборки
     * @return array
     */
    public function getOptionList($optId, $condStr='')
    {
        global $modx;
        if (intval($optId) != 0) {
            //работаем с таблицей TV
            $q = "SELECT DISTINCT value ";
            $q.= " FROm ".$modx->getFullTableName('site_tmplvar_contentvalues');
            $q.= " WHERE tmplvarid = ".$optId;
            $q.= " AND contentid IN (".$this->rawList.")";
            // предварительные условия для добавления в выборку
            if ($condStr != '') {
                $q.= " AND contentid NOT IN (".$condStr.")";
            }
        } else {
            //работаем с таблицей контента
            $q = "SELECT DISTINCT ".$optId;
            $q.= " FROm ".$modx->getFullTableName('site_content');
            $q.= " WHERE id IN (".$this->rawList.")";
            // предварительные условия для добавления в выборку
            if ($condStr != '') {
                $q.= " AND id NOT IN (".$condStr.")";
            }
        }

        $res = $modx->db->query($q);
        $cnt = $modx->db->getRecordCount($res);
        if (!$cnt) {
            return array();
        }
        while ($row = $modx->db->getRow($res)) {
            $retAr[] = isset($row['value']) ? $row['value'] : $row[$optId];
        }
        $retAr = array_unique($retAr);        
        sort($retAr);
        return $retAr;
    }

    /**
     * Метод для получения полного списка ресурсов, за вычетом exludedIf
     *  @param string $condStr Список ресурсов, которые нужно исключить
     *  @return array
     */
    public function preparedItemList($condStr='')
    {
        $rawAr = $this->rawItemList();
        
        if (is_array($this->preparedList)) {
            return $this->preparedList;
        }

        if ($condStr == '') return $this->rawItemList();

        $condAr = explode(',', $condStr);
        $rawAr = $this->rawItemList();

        $this->preparedList = array_diff($rawAr, $condAr);

        return $this->preparedList; 
    }

    /**
     * Получение результатов фильтрации, сама фильтрация не проводится
     * @return array Массив значений опции
     */
    public function filteredItemList()
    {
        if (!is_array($this->filteredList)) {
            $this->filteredList = $this->preparedItemList();
        }        
        return $this->filteredList;
    }

    /**
     * Непосредственно процесс фильтрации
     * @param AfInputField $_AIF Объект с данными по полю фильтрации
     * @return array Отфильтрованные значения
     */
    public function makeFiltering($_AIF)
    {
        global $modx;
        $filterStr = $_AIF->filter();
        $idsAr = $this->filteredItemList();

        // если массив уже пустой, выходим
        if (count($idsAr) < 1) return $idsAr;

        // если нет задачи по фильтрованию, выходим
        if ($filterStr == '') return $idsAr;

        // проверяем о какой опции идет речь
        $optName = $_AIF->fieldData['opt'];
        $optId = str_replace('opt', '', $optName);

        $idsList = implode(',', $idsAr);

        if (count($_AIF->fieldValues) >= 1) {
            array_map(array($modx->db, 'escape'), $_AIF->fieldValues);
            $values = implode(',', $_AIF->fieldValues);
        }

        if (intval($optId) != 0) {
            //работаем с таблицей TV
            $q = "SELECT DISTINCT contentid ";
            $q.= " FROM ".$modx->getFullTableName('site_tmplvar_contentvalues');
            $q.= " WHERE tmplvarid = ".$optId;
            $q.= " AND contentid IN (".$idsList.")";
            // условия для добавления в выборку
            $srch = array('{opt}', '{val}');
            $repl = array('value', $values);
            $q.= " AND ".str_replace($srch, $repl, $filterStr);
            
        } else {
            //работаем с таблицей контента
            $q = "SELECT DISTINCT id ";
            $q.= " FROM ".$modx->getFullTableName('site_content');
            $q.= " WHERE id IN (".$idsList.")";
            // условия для добавления в выборку
            $srch = array('{opt}', '{val}');
            $repl = array($optId, $values);
            $q.= " AND ".str_replace($srch, $repl, $filterStr);
        }

        $res = $modx->db->query($q);
        $cnt = $modx->db->getRecordCount($res);
        if (!$cnt) {
            $retAr = array();
        } else {
            while ($row = $modx->db->getRow($res)) {
                $retAr[] = isset($row['id']) ? $row['id'] : $row['contentid'];
            }
        }
        $this->filteredList = $retAr;
    }
}