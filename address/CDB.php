<?php
/**
 * Created by PhpStorm.
 * User: dmitry
 * Date: 18.10.17
 * Time: 16:41
 */

class CDB extends SQLite3 {
	public function __construct() {
		$this->open('../files/adverts.db');
		$this->busyTimeout(5000);
		$this->exec('CREATE TABLE IF NOT EXISTS adverts (idHouse PRIMARY KEY, idAdvert, header, description, price,'.
		            ' phone, category, region, city, city_region, metro, photo, adv_type, rooms,'.
		            ' house_type, floor, floors, term, whois, tax, pledge, square, kitchen_square,'.
		            ' living_square, address, num, video)');
	}
}