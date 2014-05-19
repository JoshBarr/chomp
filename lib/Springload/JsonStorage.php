<?php
namespace Springload;


class JsonStorage {
	
	public function __construct() {

	}

	public function store($file, $contents) {
        if (defined("JSON_UNESCAPED_SLASHES")) {
            $json = json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $json = json_encode($contents);
        }

        return file_put_contents($file, $json);
	}
}
