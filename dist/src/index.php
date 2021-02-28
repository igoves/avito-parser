#!/usr/bin/php
<?php
set_time_limit(0);

function getPage($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1');
	curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

echo 'Парсер телефонов Авито [ https://xfor.top/ ]' . PHP_EOL;
echo 'Для выхода из программы нажмите Ctrl+C' . PHP_EOL;
echo "Начните вводить город и нажмите Enter" . PHP_EOL;
echo "Город: ";

$handle = fopen ("php://stdin","r");
$line = trim(fgets($handle));

if( $line == '' ){
	echo 'Ошибка! Вы не ввели город' . PHP_EOL;
	die();
} else {
	
	$url = "https://www.avito.ru/web/1/slocations?locationId=653240&limit=10&q=".urlencode($line);
	$data = json_decode(getPage($url), true);
	
	foreach ( $data['result']['locations'] as $key => $value ) {
		echo $value['id'] . ' - ' . $value['names']['1'] . PHP_EOL;
	}
}

echo 'Введите ID города:';

$line2 = trim(fgets($handle)); 

if ( empty($line2) || strlen($line2) !== 6 ) {
	echo 'Ошибка! Не верно введен ID' . PHP_EOL;
	die();
}

$locationId = (int)$line2;

fclose($handle);

if (is_file(__DIR__ . '/hash.tmp')) {
    $hash = file_get_contents(__DIR__ . '/hash.tmp');
} else {
    $url = 'https://m.avito.ru/ekaterinburg/avtomobili/lada_xray_2020_2075178073';
    preg_match('/\/mstatic\/build\/modern\/main.(.*?).js/', getPage($url), $match);
    $hash = $match[1];
    file_put_contents('hash.tmp', $hash);
}

echo 'ХЭШ: ' . $hash . PHP_EOL;
//die();

if (!isset($hash) || empty($hash)) {
    die('Ошибка! Хэш не найден...');
}


if (is_file(__DIR__ . '/key.tmp')) {
    $key = file_get_contents(__DIR__ . '/key.tmp');
} else {
    $url = 'https://m.avito.ru/mstatic/build/modern/main.' . $hash . '.js';
    preg_match('/o=r\("vDqi"\),a=r.n\(o\),n="(.*?)",s=/', getPage($url), $match);
    $key = $match[1];
    file_put_contents('key.tmp', $key);
}
echo 'КЛЮЧ: ' . $key . PHP_EOL;
//die();

if (!isset($key) || empty($key)) {
    die('Ошибка! Ключ не найден...');
}

echo 'Старт сбора даных ...' . PHP_EOL;
for ($i = 1; $i < 999999; $i++) {

	// https://m.avito.ru/api/9/items?key=af0deccbgcgidddjgnvljitntccdduijhdinfgjgfjir&categoryId=114&locationId=621540&query=изготовление%2Bпамятников&page=1&lastStamp=1590865380&display=list&limit=30

	$url = 'https://m.avito.ru/api/9/items?key=' . $key . '&page=1&display=list&limit=50';
	echo 'Загрузка списка: ' . $i . PHP_EOL;

	$list = json_decode(getPage($url), true);

	// sleep(rand(8, 10));

	foreach ($list['result']['items'] as $k => $value) {

		$id = $value['value']['id'];

		$url_2 = 'https://m.avito.ru/api/1/items/' . $id . '/phone?key=' . $key;

		$res = json_decode(getPage($url_2), true);

		if (isset($res['result']['action']['uri'])) {

			$uri = urldecode(str_replace('ru.avito://1/phone/call?number=', '', $res['result']['action']['uri']));
			$uri = urldecode(str_replace('ru.avito://1/authenticate', 'authenticate', $uri));
			$uri = trim($uri);
			echo $id . ' : ' . $uri . PHP_EOL;
			if ( $uri !== 'authenticate' ) {
				file_put_contents(__DIR__ . '/phones.csv', $id . ',' . $uri . PHP_EOL, FILE_APPEND);
			}
		}

		flush();
		ob_flush();
		sleep(rand(3, 6));

	}
	
	flush();
	ob_flush();
	sleep(rand(8,10));
}
echo 'Конец.' . PHP_EOL;;

die();