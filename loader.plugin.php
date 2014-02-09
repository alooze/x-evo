<?php
$e = $modx->event;

$debug = 1;

switch ($e->name) {
    // case 'OnManagerPageInit':
    case 'OnWebPageInit':
        //подключаем логирование запросов
        if ($debug == 1) {
            $modx->dumpSQL = true;
        }

        //проверяем наличие класса автозагрузки
        if (!class_exists('ModxExtAutoLoader')) {
            /*$mainConfig = MODX_BASE_PATH.'assets/extensions/Core/config';
            if (is_file($mainConfig)) {

            }*/
            $loaderFile = MODX_BASE_PATH.'assets/extensions/Core/lib/Loader.php';
            include $loaderFile;

            //регистрируем пути к расширениям
            $loader = new \Modx\Ext\Core\ModxExtAutoLoader;
            $loader->register();

            $loader->addNamespace('Modx\Ext\Core', MODX_BASE_PATH.'assets/extensions/Core/lib');
            
            $loader->addNamespace('Modx\Ext\Xparser', MODX_BASE_PATH.'assets/extensions/Xparser/lib');

            $loader->addNamespace('Modx\Ext\Rutils', MODX_BASE_PATH.'assets/extensions/Rutils/lib');
            $loader->addNamespace('Modx\Ext\Rutils\Struct', MODX_BASE_PATH.'assets/extensions/Rutils/lib/Struct');

            $loader->addNamespace('Modx\Ext\Store', MODX_BASE_PATH.'assets/extensions/Store/lib');
            $loader->addNamespace('Modx\Ext\Xadmin', MODX_BASE_PATH.'assets/extensions/Xadmin/lib');
            $loader->addNamespace('Modx\Ext\Xadmin\Models', MODX_BASE_PATH.'assets/extensions/ActiveRecord/models');

            $loader->addNamespace('ActiveRecord', MODX_BASE_PATH.'assets/extensions/ActiveRecord/lib');

            $loader->addNamespace('CacheCache', MODX_BASE_PATH.'assets/extensions/Tcache/src/CacheCache');
        }
    break;
}
