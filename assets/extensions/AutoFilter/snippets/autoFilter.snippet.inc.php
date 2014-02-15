<?php
/**
 * autoFilter.snippet.inc.php
 * 
 * Файл для include. Является частью расширения autoFilter для MODX Evolution
 * @version 1.0.0
 * @author alooze (a.looze@gmail.com)
 */
use Modx\Ext\AutoFilter\AutoFilter;

$af = new AutoFilter($params);

/**
 * Проверяем наличие премета фильтрации
 */
$rawList = $af->rawItemList();
if (!is_array($rawList)) {
    return $af->lang('No items for filter');
} 

// echo '<pre>';
// print_r($rawList);
// echo '<br>';

/**
 * Проверяем задачи фильтрации
 */
if (!is_array($tasks = $af->getTask())) {
    return $af->lang('No tasks for filter');
}
// print_r($tasks);
// echo '<br>';

/**
 * Уточняем список ресурсов, с учетом excludeIf
 */
$preparedList = $af->preparedItemList();
if (!is_array($preparedList)) {
    return $af->lang('Empty list');
}
// print_r($preparedList);
// echo '<br>';

/**
 * Обрабатываем значения из HTTP запроса, сессии и предфильтров
 * 
 */
if ($af->fromForm() && !$af->resetRequested()) {
    // получены данные из формы, сброс не запрошен
    // echo 'form';
    $af->makeFiltering();
} else if ($af->resetRequested()) {
    // получены данные из формы, запрошен сброс
    // echo 'reset';
    $af->makeReset();
} else if ($af->hasSavedState() && $af->needLoadState()) {
    // форма не приходила, но данные в сессии для восстановления фильтров есть
    // echo 'session';
    $af->loadState();
} else if ($af->hasPreFilter()) {
    // нет данных в сессии и в запросе, но есть установки предфильтров
    // echo 'prefilter';
    $af->imitateRequest();
    $af->makeFiltering();
} else {
    // первая загрузка страницы, форма в дефолтном состоянии
    // echo 'default';    
}

/**
 * Заполняем плейсходеры
 */
$ph = $af->preparePlaceholders();
$modx->toPlaceholders($ph);

// echo '</pre>';
return;
?>