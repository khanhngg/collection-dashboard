#!/usr/bin/php

<?php
ini_set('memory_limit', '1024M');

include 'config.php';

class SfmomaCollectionBase {

	public $api_key = SFMOMA_API_KEY;

	function __construct() {
		
	}

	function query_collection($url, $query_data=[]) {

		$ch = curl_init();
		$timeout = 5;
		$request_headers[] = 'Authorization: Token '.$this->api_key;
		$query = http_build_query($query_data, '', '&amp;');

		$url = $url.'?'.$query;

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data_json = curl_exec($ch);
		$data = json_decode($data_json);
		curl_close($ch);

		return $data;

	}
}

?>