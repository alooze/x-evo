<?php
/**
 * Интерфейс, обеспечивающий работу автофильтра
 */
namespace Modx\Ext\AutoFilter\AfModels;

interface IAbstractAfModel
{
    /**
     * Получение полного списка первичных ключей, до всех выборок и сортировок
     * на основании параметров вызова
     *  @param array $idsOrParents Данные для получения списка
     *  @return array
     */
    public function rawItemList($params);

    /**
     * Получение списка id ресурсов, на основании заданных условий
     * @param string $optId Строка, идентифицирующая поле(опцию) для выборки
     * @param array $condAr Массив вида 'sign'=>условие, 'val'=>значение
     * @return array
     */
    public function getIdsByCond($optId, $condAr);

    /**
     * Метод для получения всего списка значений для данной опции
     * @param string $optId Строка для идентификации опции
     * @param string $condStr Список id для исключения из выборки
     * @return array
     */
    public function getOptionList($optId, $condStr);

    /**
     * Метод для получения полного списка ресурсов, за вычетом exludedIf
     *  @return array
     */
    public function preparedItemList();

    /**
     * Получение результатов фильтрации
     * @return array Массив значений опции
     */
    public function filteredItemList();

    /**
     * Непосредственно процесс фильтрации
     * @param AfInputField $_AIF Объект с данными по полю фильтрации
     * @return array Отфильтрованные значения
     */
    public function makeFiltering($_AIF);
}