<?php
/**
 * Created by PhpStorm.
 * User: dmitry
 * Date: 16.10.17
 * Time: 22:39
 */
require_once 'CAddressDB.php';
require_once 'CAdvert.php';
require_once 'CPrice.php';

$data = new CAddressDB();
$data = $data->getRandomData();

$adv = new CAdvert();
$adv = $adv->getRandomAdvert();

$c = new CPrice($adv['rooms'],$data['type'], $adv['square'], $data['metro']);
$avito_url = $c->getAvitoLink();
$cian_url = $c->getCianLink();
$extdata = $c->getAveragePrice();
echo '<br><b>Avito</b>: <a target="_blank" href="'.$avito_url.'">'.$avito_url.'</a>';
echo '<br><b>Cian</b>: <a target="_blank" href="'.$cian_url.'">'.$cian_url.'</a>';
//
?>
<!DOCTYPE html>
<html>
<head>
    <title>Объявления</title>
    <style>
        body {
            font-size: 10pt;
            font-family: "Helvetica", "Arial", sans-serif;
        }
    </style>
</head>
<body>
<form action="csv.php" method="post">
    <input type="submit" value="Получить CSV"><br/>
	<?php foreach(explode(',',$adv['photo']) as $image) {
		echo '<span><img height="100px" src="/files/'.$adv['id'].'/'.$image.'" /></span>&nbsp;';
	}
	echo '<br/>'?>
    <label>
        Заголовок:
        <input type="text" name="header" value="<?php echo $adv['header']; ?>">
    </label><br/>
    <label>
        Описание:
        <textarea rows="5" style="width: 100%" name="description"><?php echo $adv['description']; ?></textarea>
    </label><br/>
    <label>
        Цена:
        <input type="text" name="price" value="<?php echo $extdata['price']; ?>">
    </label><br/>
    <label>
        Телефон:
        <input type="text" name="phone" value="">
    </label><br/>
    <label>
        Категория:
        <input type="text" name="category" value="Квартиры">
    </label><br/>
    <label>
        Регион:
        <input type="text" name="region" value="">
    </label><br/>
    <label>
        Город:
        <input type="text" name="city" value="Москва">
    </label><br/>
    <label>
        Район:
        <input type="text" name="city_region" value="">
    </label><br/>
    <label>
        Метро:
        <input type="text" name="metro" value="<?php echo $data['metro']; ?>">
    </label><br/>
    <label>
        Фото:
        <input type="text" name="photo" value="<?php echo $adv['photo']; ?>">
    </label><br/>
    <label>
        Тип объявления:
        <input type="text" name="adv_type" value="Сдам">
    </label><br/>
    <label>
        Кол-во комнат:
        <input type="text" name="rooms" value="<?php echo $adv['rooms']; ?>">
    </label><br/>
    <label>
        Тип дома:
        <input type="text" name="house_type" value="<?php echo $data['type']; ?>">
    </label><br/>
    <label>
        Этаж:
        <input type="text" name="floor" value="<?php echo rand(1, $data['floors']); ?>">
    </label><br/>
    <label>
        Этажей в доме:
        <input type="text" name="floors" value="<?php echo $data['floors']; ?>">
    </label><br/>
    <label>
        Срок аренды:
        <input type="text" name="term" value="На длительный срок">
    </label><br/>
    <label>
        Собственник/посредник:
        <input type="text" name="whois" value="Посредник">
    </label><br/>
    <label>
        % комиссии:
        <input type="text" name="tax" value="50">
    </label><br/>
    <label>
        Залог:
        <input type="text" name="pledge" value="1 месяц">
    </label><br/>
    <label>
        Общая площадь:
        <input type="text" name="square" value="<?php echo $adv['square']; ?>">
    </label><br/>
    <label>
        Площадь кухни:
        <input type="text" name="kitchen_square" value="<?php echo $extdata['kitchen']; ?>">
    </label><br/>
    <label>
        Жилая площадь:
        <input type="text" name="living_square" value="<?php echo $extdata['living']; ?>">
    </label><br/>
    <label>
        Адрес:
        <input type="text" name="address" value="<?php echo $data['address']; ?>">
    </label><br/>
    <label>
        Кадастровый номер:
        <input type="text" name="num" value="">
    </label><br/>
    <label>
        Видео:
        <input type="text" name="video" value="">
    </label><br/>
    <input type="hidden" name="houseId" value="<?php echo $data['id']; ?>">
    <input type="hidden" name="advertId" value="<?php echo $adv['id']; ?>">
</form>
</body>
</html>