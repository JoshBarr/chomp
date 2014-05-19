<?php 
namespace Springload;
use Springload\DirectoryList;
use Springload\JsonStorage;


class ClientProjectList extends DirectoryList
{	

	protected $jsonName = "client.json";

	protected $jsonDefault = array(
			"name" =>  "Client's name",
			"logo" => "/assets/images/client-logo.png",
			"url" => "http://some-client.co.nz"
		);

	public function __construct($base, $client) {
        $this->dir = $base . $client;
        $this->folderName = $client;        
    }

	public function getData() {
		$data = $this->getJson();
		$data["projects"] = $this->ls(null, array(
			"order"=>"modified_desc"
			)
		);

		$new = array();
		foreach ($data["projects"] as $project) {
			$project["data"] = array();
			$new[] = $project;
		}

		$data["projects"] = $new;
		return $data;
	}

	public function getDir() {
		return $this->dir;
	}

	public function getFolderName() {
		return $this->folderName;
	}

	public function update($data) {
		$existing = $this->getJson();
		$arr = array_replace($existing, $data);
		$this->saveData($arr);
	}
}