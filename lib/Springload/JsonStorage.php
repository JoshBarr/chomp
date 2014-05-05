<?php
namespace Springload;


class JsonStorage {
	
	public function __construct() {

	}

	public function store($file, $contents) {
		try {
			return file_put_contents($file, 
				json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
			);
		} catch(Exception $e) {
			return $e;
		}
	}
}
