<?php
/**
 * Created by PhpStorm.
 * User: dmitry
 * Date: 17.10.17
 * Time: 15:10
 */

require_once 'CDB.php';

$db = new CDB();

$str_header = 'Заголовок;Описание;Цена;Телефон;Категория;Регион;Город;Район;Метро;Фото;Тип объявления;'.
              'Количество комнат;Тип дома;Этаж;Этажей в доме;Срок аренды;Право собственности;Размер комиссии;Залог;'.
              'Общая площадь;Площадь кухни;Жилая площадь;Адрес;Кадастровый номер;Видео';

$data = [
	$_POST['header'],
	$_POST['description'],
	$_POST['price'],
	$_POST['phone'],
	$_POST['category'],
	$_POST['region'],
	$_POST['city'],
	$_POST['city_region'],
	$_POST['metro'],
	$_POST['photo'],
	$_POST['adv_type'],
	$_POST['rooms'],
	$_POST['house_type'],
	$_POST['floor'],
	$_POST['floors'],
	$_POST['term'],
	$_POST['whois'],
	$_POST['tax'],
	$_POST['pledge'],
	$_POST['square'],
	$_POST['kitchen_square'],
	$_POST['living_square'],
	$_POST['address'],
	$_POST['num'],
	$_POST['video']
];

$fp = fopen('../files/advert.csv','w');
fwrite($fp, iconv('utf-8', 'Windows-1251//TRANSLIT', $str_header));
fwrite($fp, '
');
foreach ($data as $index=>$value) {
	fwrite($fp, iconv('utf-8','Windows-1251//TRANSLIT', $value));
	if ($index < count($data) -1) {
		fwrite($fp, iconv('utf-8', 'Windows-1251',';'));
	}
}
fclose($fp);
$zip = new ZipArchive;
$zip->open('../files/advert.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
$images = scandir('../files/'.$_POST['advertId'].'/');
foreach ($images as $image) {
	if (in_array($image, array('.','..'))) {
		continue;
	}
	$zip->addFile('../files/'.$_POST['advertId'].'/'.$image, $image);
}

$zip->addFile('../files/advert.csv', 'advert.csv');
$zip->close();
$db->exec('INSERT INTO adverts VALUES ("'.$_POST['houseId'].'", "'.$_POST['advertId'].'", '.
          '"'.$_POST['header'].'", "'.$_POST['description'].'", "'.$_POST['price'].'", '.
          '"'.$_POST['phone'].'", "'.$_POST['category'].'", "'.$_POST['region'].'", '.
          '"'.$_POST['city'].'", "'.$_POST['city_region'].'", "'.$_POST['metro'].'", '.
          '"'.$_POST['photo'].'", "'.$_POST['adv_type'].'", "'.$_POST['rooms'].'", '.
          '"'.$_POST['house_type'].'", "'.$_POST['floor'].'", "'.$_POST['floors'].'", "'.
          $_POST['term'].'", "'.$_POST['whois'].'", "'.$_POST['tax'].'", "'.
          $_POST['pledge'].'", "'.$_POST['square'].'", "'.$_POST['kitchen_square'].'", "'.
          $_POST['living_square'].'", "'.$_POST['address'].'", "'.$_POST['num'].'", "'.$_POST['video'].'")');
$db->close();

header('Location: /files/advert.zip');
//header('Content-Type: application/zip');
//header('Content-disposition: attachment; filename='.'../files/advert.zip');
//header('Content-Length: ' . filesize('../files/advert.zip'));
//chdir('../files/advert.zip');
//readfile('advert.zip');