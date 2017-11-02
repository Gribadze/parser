<?php
/**
 * Created by PhpStorm.
 * User: dmitry
 * Date: 23.10.17
 * Time: 11:27
 */

require_once 'CDB.php';

$db = new CDB();
$results = $db->query('
SELECT houses.id 
FROM houses 
WHERE NOT EXISTS(SELECT adverts.idHouse FROM adverts, houses AS h WHERE adverts.idHouse=h.id) 
ORDER BY RANDOM()');
while ($row = $results->fetchArray()) {
	echo $row[0].PHP_EOL;
}
$db->close();
unset($db);
