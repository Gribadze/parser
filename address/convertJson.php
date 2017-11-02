<?php
/**
 * Created by PhpStorm.
 * User: dmitry
 * Date: 27.10.17
 * Time: 14:31
 */

$json_content = file_get_contents('../cian_metro.json');

$json = json_decode($json_content, true);

$newobj = array();

foreach ($json as $key=>$item) {
	$newobj[$item['name']] = $key;
}

$newF = fopen('../files/cian_metro.json', 'w');
	fwrite($newF, json_encode($newobj, JSON_UNESCAPED_UNICODE));
fclose($newF);
// json_encode($newobj, JSON_UNESCAPED_UNICODE);