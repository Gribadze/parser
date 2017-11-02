<?php
/**
 * Created by PhpStorm.
 * User: dmitry
 * Date: 19.10.17
 * Time: 12:29
 */
require_once '../vendors/simple_html_dom.php';

class CPrice {
	public function __construct($rooms, $types, $square, $metro) {
		$this->avito_url = 'https://www.avito.ru/moskva/kvartiry/sdam/na_dlitelnyy_srok/';
		$this->cian_url = 'https://www.cian.ru/cat.php?deal_type=rent&engine_version=2'.
		                  '&type=4&offer_type=flat&maxarea='.
		                  ((round($square/10)+1)*10).'&minarea='.
		                  ((round($square/10)-1)*10).'&room'.$rooms.'=1';

		$cian_metro = file_get_contents('../files/cian_metro.json');
		$cian_json = json_decode($cian_metro, true);

		if ($cian_json[$metro] !== null) {
			$this->cian_url .= '&metro='.$cian_json[$metro];
		}

		$this->context = stream_context_create([
			'http' => [
				"method" => "GET",
				"header" => "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36"
			]
		]);
		$this->avito_rooms_url = array(
			1 => '1-komnatnye',
			'2-komnatnye',
			'3-komnatnye',
			'4-komnatnye',
			'5-komnatnye',
			'6-komnatnye',
			'7-komnatnye',
			'8-komnatnye',
			'9-komnatnye',
			'mnogokomnatnye'
		);
		$this->avito_types_url = array(
			'Кирпичный' => 'kirpichnyy_dom',
			'Панельный' => 'panelnyy_dom',
			'Блочный' => 'blochnyy_dom',
			'Монолитный' => 'monolitnyy_dom',
			'Монолит' => 'monolitnyy_dom',
			'Деревянный' => 'derevyannyy_dom'
		);
		$this->avito_square_param = '568_'; // 0,14007 - 14027
		$squares = [ 10, 15, 20, 25, 30, 40, 50, 60, 70, 80, 90, 100,
			110, 120, 130, 140, 150, 160, 170, 180, 190, 200 ];
//		$this->avito_floor_param = '501_'; // 0,5151 - 5180

		$this->rooms = $rooms;
		$this->types = $types;
		$square = 1 * explode(' ',$square)[0];
		foreach ( $squares as $index=>$s ) {
			if ($s == $square) {
				$square = [$index - 1, $index + 1];
				break;
			} elseif ($s < $square) {
				continue;
			} else {
				$square = [$index - 2, $index];
				break;
			}
		}
		$this->square = '568_'.(14007+$square[0]).'b'.(14007+$square[1]);
//		$this->floor = '501_'.(5150+$floor).'b'.(5150+$floor);
		$main_page = file_get_html($this->avito_url,false, $this->context);
//		var_dump($main_page);
		$metro_options = $main_page->find('option');
		foreach ($metro_options as $option) {
			if ($option->plaintext === $metro) {
				$this->metro = 'metro='.$option->value;
			}
		}
//		var_dump($metro_names);
	}

	private function getSquares($url) {
		echo 'get url '.$url.'<br>';
		$query = $this->getContent('https://www.avito.ru'.$url, true);
		$item = str_get_html($query);
		if (!$item) return [0, 0];
		$params = $item->find('.item-params-list-item');
		$kitchen = 0;
		$living = 0;
		foreach ($params as $param) {
			if (substr_count($param->plaintext, 'Площадь кухни')>0) {
				$kitchen = trim(str_replace('м²', '',
					trim(explode(':', trim($param->plaintext))[1])));
			}
			if (substr_count($param->plaintext, 'Жилая площадь')>0) {
				$living = trim(str_replace('м²', '',
					trim(explode(':', trim($param->plaintext))[1])));
			}
		}
		return [$kitchen, $living];
	}

	private function getContent($url, $withProxy=false) {
		$curl_handle=curl_init();
		curl_setopt($curl_handle, CURLOPT_URL,$url);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('User-Agent: Mozilla/5.0'));
		if ($withProxy) {
			srand(time());
			$proxy = '162.210.198.8:'.(1200+rand(0,50));
//			echo $proxy;
			curl_setopt($curl_handle, CURLOPT_PROXY, $proxy);
		}
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE);
		$query = curl_exec($curl_handle);
		curl_close($curl_handle);
		return $query;
	}

	public function getAvitoLink() {
		$url = $this->avito_url.$this->avito_rooms_url[$this->rooms].'/'.
		       $this->avito_types_url[$this->types].'?s=101&f='.$this->square;
		if (isset($this->metro)) {
			$url .= '&'.$this->metro;
		}

		return $url;
	}

	public function getCianLink() {
		return $this->cian_url;
	}

	public function getAveragePrice() {
		$url = $this->getAvitoLink();

//		$status = get_headers($url.'&f='.$this->square.'.'.$this->floor);
/*		if (!strpos($status[0], '404 Not found')) {
			$url = $url.'&f='.$this->square.'.'.$this->floor;
		} else {*/
//			$status = get_headers($url.'&f='.$this->square);
//			if (!strpos($status[0], '404 Not found')) {
//				$url = $url.'&f='.$this->square;
//			}
//			var_dump($status);
/*		}*/
		$query = $this->getContent($url, true);
		echo '<br/>price from '.$url.'<br/>';
//		$page = file_get_html($url, false, $this->context);
		$page = str_get_html($query);
//		if (!$page) echo $query;
		$items_url = array_map(function($item) {
			return $item->href;
		}, $page->find('.item-description-title-link'));
		$result_squares = [0, 0];
		foreach ($items_url as $item) {
			$result_squares = $this->getSquares($item);
			if ($result_squares[0] !== 0 && $result_squares[1] !== 0) {
				break;
			}
			sleep(5);
		}
//		foreach ($inner_squares as $inner_square) {
//			if ($inner_square[0] !== 0 && $inner_square[1] !== 0) {
//				$result_squares = [$inner_square[0], $inner_square[1]];
//				break;
//			}
//		}
		$items = array_map(function ($item) {
			$about = explode('в месяц',$item->find('.about', 0)->plaintext)[0];
			$res = 0;
			foreach (str_split($about) as $ch) {
				if (is_numeric($ch)) {
					$res = $res*10 + $ch;
				}
				if ($ch === 'р') {
					break;
				}
			}
			return $res;
		}, $page->find('.description'));
		$avg = 0;
		foreach ($items as $item) {
			$avg += $item;
		}
		if (count($items) > 0) {
			return array(
				'price' => round($avg / count($items) / 1000) * 1000,
				'kitchen' => 1*$result_squares[0],
				'living' => 1*$result_squares[1]
			);
		} else {
			return array(
				'price' => 0,
				'kitchen' => 1*$result_squares[0],
				'living' => 1*$result_squares[1]
			);
		}
	}
}

