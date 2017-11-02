<?php
/**
 * Created by PhpStorm.
 * User: dmitry
 * Date: 17.10.17
 * Time: 10:39
 */
require_once '../vendors/simple_html_dom.php';
ini_set('display_errors','1');


class CAdvert {
	public function __construct() {
		$this->main_url = 'https://moskva.smr77.ru';
		$this->context = stream_context_create([
			'http' => [
				"method" => "GET",
				"header" => "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36"/*,
				"proxy" => "tcp://162.210.198.8:1234",
				"request_fulluri" => true*/
			],
			"ssl"=>array(
				"verify_peer" => false,
				"verify_peer_name" => false,
			)
		]);
	}

	private function savePicture($id, $picture_url) {
//		echo 'uploading '.$picture_url.' ...<br/>';
		if (!file_exists('../files/'.$id)) {
			mkdir('../files/'.$id, 0777, true);
		}
		$file_name = explode('/', $picture_url)[count(explode('/', $picture_url))-1];
		$img_path = '../files/'.$id.'/'.$file_name;
		file_put_contents($img_path, file_get_contents($picture_url, false, $this->context));
		return $file_name;
	}

	private function parseAdvert($advert_url) {
//		$advert_url = 'https://moskva.smr77.ru/object/44735211/';
		echo 'advert from '.$advert_url;
		$advert_id = explode('/', $advert_url)[count(explode('/', $advert_url))-2];
		$page = file_get_html( $advert_url, false, $this->context );

		$data = array();
		$data['id'] = $advert_id;
		$data['header'] = str_ireplace('&sup2;', '.кв.', trim($page->find('h1', 0)->plaintext));
		$data['description'] = trim($page->find('p.description', 0)->plaintext);
		//rooms
		$first_table = $page->find('table.fl',0);
//		$row = $first_table->find('tr', 1);
//		$data['rooms'] = trim($row->find('td', 1)->plaintext);
		$rows = $first_table->find('tr');
		foreach($rows as $row) {
			$d = $row->find('td');
			if (substr_count($d[0]->plaintext,'комнат') > 0) {
				$data['rooms'] = trim($d[1]->plaintext);
			}
			if (substr_count($d[0]->plaintext, 'Общая площадь') > 0) {
				$square = trim($d[1]->plaintext);
				$res = 0;
				foreach (str_split($square) as $ch) {
					if (is_numeric($ch)) {
						$res = $res*10 + $ch;
					} else {
						break;
					}
				};
				$square = $res;
				$data['square'] = $square;
			};
		};

		$pictures_urls = array_map(function ($element) {
			return 'https:'.$element->full;
		}, $page->find('.images img'));
		$photos = '';
		foreach ($pictures_urls as $picture_url) {
			if ($photos === '') {
				$photos = $this->savePicture($advert_id,$picture_url);
			} else {
				$photos = $photos.','.$this->savePicture($advert_id,$picture_url);
			}
		}
//		echo $photos.'<br/>';
		$data['photo'] = $photos;

		return $data;
	}

	private function parsePageUrl($page_url) {
		$page = file_get_html( $page_url, false, $this->context );

		$adverts = array_map( function ( $advert ) {
			return 'https:'.$advert->href;
		}, $page->find( 'a[href^="//moskva.smr77.ru/object/"]' ) );

		return $this->parseAdvert($adverts[rand(0, count($adverts)-1)]);
	}

	public function getRandomAdvert() {
		$main_page = file_get_html(''.$this->main_url, false, $this->context);
		srand(time());

		$last_a = $main_page->find('a.last');
		$page_url_template = '';
		$pages_count = '';
		foreach ($last_a as $element) {
			$url = explode('page=', $element->href);
			$pages_count = $url[1];
			$page_url_template = $url[0].'page=';
		}

		return $this->parsePageUrl($page_url_template.rand(1,$pages_count));
	}
}
