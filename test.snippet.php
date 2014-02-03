<?php
use Modx\Ext\Rutils\RUtils;
use Modx\Ext\Rutils\TypoRules;

/**
 * test.snippet.php
 *  Проверка работы механизма расширений на примере библиотеки RUtils
 */

$variants = array(
    'гвоздь', //1
    'гвоздя', //2
    'гвоздей' //5
);
$amount = 3;
echo $amount, ' ', RUtils::numeral()->choosePlural($amount, $variants),'<br><br>';


echo RUtils::numeral()->sumString(1234, RUtils::MALE, $variants),'<br><br>';

$numeral = RUtils::numeral();
echo $numeral->getInWordsInt(100),'<br><br>';

//Result: сто

echo $numeral->getInWordsFloat(100.025),'<br><br>';

//Result: сто целых двадцать пять тысячных

echo $numeral->getInWords(100.0),'<br><br>';

//Result: сто


$params = array(
    'date' => '09-05-1945',
    'format' => 'l d F Y была одержана победа над немецко-фашистскими захватчиками',
    'monthInflected' => true,
    'preposition' => true,
);
echo RUtils::dt()->ruStrFTime($params),'<br><br>';

$toTime = new \DateTime('05-06-1945');
echo RUtils::dt()->distanceOfTimeInWords($toTime),'<br><br>';


$toTime = strtotime('05-06-1945');
$fromTime = null; //now
$accuracy = 3; //дни, часы, минуты
echo RUtils::dt()->distanceOfTimeInWords($toTime, $fromTime, $accuracy),'<br><br>';
//Result: 24 976 дней, 11 часов, 21 минуту назад

$fromTime = '1988-01-01 11:40';
$toTime = '2088-01-01 12:35';
$accuracy = 3; //дни, часы, минуты
echo RUtils::dt()->distanceOfTimeInWords($toTime, $fromTime, $accuracy),'<br><br>';
//Result: через 36 525 дней, 0 часов, 55 минут

//Транслитерация
echo RUtils::translit()->translify('Муха — это маленькая птичка'),'<br><br>';
//Result: Muha - eto malen'kaya ptichka

//Обратное преобразование
echo RUtils::translit()->detranslify("SCHuka"),'<br><br>';
//Result: Щука

//Подготовка для использования в URL'ях или путях
echo RUtils::translit()->slugify('Муха — это маленькая птичка'),'<br><br>';
//Result: muha---eto-malenkaya-ptichka


$text = <<<TEXT
...Когда В. И. Пупкин увидел в газете ( это была "Сермяжная правда" № 45) рубрику Weather Forecast (r),
он не поверил своим глазам - температуру обещали +-451C.
TEXT;

//Стандартные правила
echo RUtils::typo()->typography($text),'<br><br>';
/**
 * Result:
 * ...Когда В. И. Пупкин увидел в газете (это была «Сермяжная правда» №45) рубрику Weather Forecast®,
 * он не поверил своим глазам — температуру обещали ±451°F.
 */


//Правила из набора "extended"
echo RUtils::typo()->typography($text, TypoRules::$EXTENDED_RULES),'<br><br>';
/**
 * Result:
 * …Когда В. И. Пупкин увидел в газете (это была «Сермяжная правда» №45) рубрику Weather Forecast®,
 * он не поверил своим глазам — температуру обещали ±451 °F.
 */

//Пользовательские правила
echo RUtils::typo()->typography($text, array(TypoRules::DASHES, TypoRules::CLEAN_SPACES)),'<br><br>';
/**
 * Result:
 * ...Когда В. И. Пупкин увидел в газете (это была "Сермяжная правда" № 45) рубрику Weather Forecast (r),
 * он не поверил своим глазам — температуру обещали +-451F.
 */
?>