<?php
/**
 * Created by PhpStorm.
 * User: dmitry
 * Date: 17.10.17
 * Time: 10:38
 */
require_once '../vendors/simple_html_dom.php';
require_once 'CDB.php';

class CAddressDB {
	public function __construct() {
		$this->main_url = 'https://www.moscowmap.ru/adress_all.asp?dom=';
		$this->context = stream_context_create([
			'http' => [
				"method" => "GET",
				"header" => "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36"/*,
				"proxy" => "tcp://162.210.198.8:1200",
				"request_fulluri" => true*/
			],
			"ssl"=>array(
				"verify_peer" => false,
				"verify_peer_name" => false,
			)
		]);
	}

	public function getRandomData() {
//		$letters_page = file_get_html($this->main_url.'/streets/');
//
//		$letters_urls = array_map(function ($element) {
//			return $element->href;
//		}, $letters_page->find('a[href^="/streets/"]'));
//
//		srand(time());
//
//		$letter_num = rand(0, count($letters_urls));
//
//		$letter_url = $letters_urls[$letter_num];
		echo 'getting random address... <br/>';
		$data = NULL;
		$isFound = false;
		$db = new CDB();
		$results = $db->query('
SELECT houses.id 
FROM houses 
WHERE NOT EXISTS(SELECT adverts.idHouse FROM adverts, houses AS h WHERE adverts.idHouse=h.id) 
ORDER BY RANDOM()');
		$rows = array();
		while ($row = $results->fetchArray()) {
			$rows[count($rows)] = $row[0];
		};
		$db->close();
		unset($db);
		foreach ($rows as $row) {
			$data = $this->parseHouseUrl($this->main_url./*'28515'*/$row);
			if ($data !== NULL) {
				$isFound = true;
				break;
			}
		}

		if (!$isFound) {
			echo 'Database is full...<br/> Copy backup and cleaning adverts...<br/>';
			copy('../files/adverts.db', '../files/adverts'.date('dmY').'.bak');
			$db = new CDB();
			$db->exec('DELETE FROM adverts');
			$db->close();
			unset($db);
			$this->getRandomData();
		}

		return $data;
	}

	private function parseHouseUrl($house_url) {
//		$ch = curl_init();
//		curl_setopt($ch, CURLOPT_URL, $house_url);
//		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36');
//		curl_setopt($ch, CURLOPT_PROXY, '162.210.198.8:1234');
//		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
//		curl_setopt($ch, CURLOPT_HEADER, 0);
//
//		$house_resp = curl_exec($ch);
//		var_dump($house_resp);
//		var_dump(curl_error($ch));
//		curl_close($ch);
//
		$house_page = file_get_html( $house_url, false, $this->context );
		$house_id = explode('dom=', $house_url)[1];

		$db = new CDB();
		$results = $db->query('SELECT idHouse FROM adverts WHERE idHouse='.$house_id);
		if ($row = $results->fetchArray(SQLITE3_ASSOC)) {
			echo $house_id.' already used. Try to get another address...<br/>';
			$db->close();
			unset($db);
			return NULL;
		}
		$db->close();
		unset($db);

		$data = array();
		$data['id'] = $house_id;
		$data['address'] = $house_page->find( 'h1', 0 )->plaintext;
		$metro = $house_page->find('.distroutes a[href^="/metro/"]', 0);
		if ($metro) {
			$data['metro'] = explode(' - ', $house_page->find('.distroutes a.red', 0)->plaintext)[0];
		} else {
			$data['metro'] = '';
		}
		$extinfo = array_map( function ( $element ) {
			return $element->plaintext;
		}, $house_page->find( '.extinfo p' ) );

		foreach ($extinfo as $info) {
			$param = explode(':', $info);
			switch($param[0]) {
				case 'Назначение': $data['appointment'] = trim($param[1]); break;
				case 'Тип дома': $data['type'] = trim($param[1]); break;
				case 'Этажей': $data['floors'] = trim($param[1]); break;
				case 'Квартир в доме': $data['apartments'] = trim($param[1]); break;
			}
		}
		if (!isset($data['appointment']) || $data['appointment'] !== 'Жилой') {
			$db = new CDB();
			$db->exec('DELETE FROM houses WHERE id="'.$house_id.'"');
			$db->close();
			unset($db);
			return NULL;
		}
		if (!isset($data['type']) || $data['type'] === 'Деревянный' || $data['type'] === '') {
			$db = new CDB();
			$db->exec('DELETE FROM houses WHERE id="'.$house_id.'"');
			$db->close();
			unset($db);
			return NULL;
		}
		echo 'info from '.$house_url.'<br/>';
		return $data;
	}

	private function parseStreetUrl($street_url) {
		$street_page = file_get_html( $street_url, false, $this->context );

		$houses = $street_page->find( 'a[href^="/adress_all.asp"]' );

		$houses_urls = array_map( function ( $element ) {
			return $element->href;
		}, $houses );
		if (count($houses_urls) === 0) {
			return NULL;
		}

		$tries = 0;
		do {
			$tries += 1;
			$data = $this->parseHouseUrl( $this->main_url.$houses_urls[ rand( 0, count( $houses_urls )-1 ) ] );
			if ($tries > count( $houses_urls )) {
				return NULL;
			}
		} while ($data === NULL);

		return $data;
	}

	private function parseLetterUrl($letter_url) {
		$letter_page = file_get_html( $letter_url, false, $this->context );

		$streets_urls = array_map( function ( $element ) {
			return $element->href;
		}, $letter_page->find( 'a[href^="/street.asp"]' ) );

		$tries = 0;
		do {
			$tries += 1;
			$data = $this->parseStreetUrl( $this->main_url.$streets_urls[ rand( 0, count( $streets_urls )-1 ) ] );
			if ($tries > count( $streets_urls )) {
				return NULL;
			}
		} while ($data === NULL);

		return $data;
	}

}