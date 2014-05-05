<?php 
namespace Springload;
use Springload\DirectoryList;

class ClientProject extends DirectoryList
{
	protected $jsonName = "project.json";

	public $jsonDefault = array(
		"name" => "Project name",
		"job_code" => "SL000",
		"preview_url" => "http://some-client.co.nz",
		"basecamp_url" => "http://basecamp.com/XXX",
		"description" => "This is the project description",
		"groups" => array()
	);

	protected $groupDefault = array(
		"id" => "0",
		"date" => "",
		"title" => "",
		"order" => "0",
		"anchor" => ""
	);

	protected $blockDefault = array(
		"name" => "A title for this set of images",
		"description" => "A description for this set of images",
		// "date" => "today",
		"group" => "0"
		);

	public function __construct($clientObject, $project) {
        $this->dir = $clientObject->getDir() . "/" . $project; 
        $this->client = $clientObject;

        $this->jsonDefault["groups"][] = $this->groupDefault;   
    }

    public function getData() {
    	$data = $this->getJson();
    	$data["blocks"] = $this->getBlocksByGroups($data["groups"]);

    	return $data;
    }

    public function getBlocksByGroups($groups) {
    	$blocks = $this->getBlocks();
    	$ordered = array();

    	foreach ($groups as $group) {
    		$group["data"] = array();

    		foreach ($blocks as $block) {
    			
    			if ($block['data']['group'] == $group['id']) {
                    $block['data']['id'] = sha1($block['path']);
                    $block['data']['path'] = $block['name'];
                    $block['data']['mtime'] = $block['mtime'];
    				$group['data'][] = $block["data"];
    			}
    		}
    		$ordered[] = $group;
    	}

    	usort($ordered, function($a, $b) {
		    return $a['order'] - $b['order'];
		});

    	return $ordered;
    }

    public function getBlocks() {
    	$blocks = $this->ls();
    	$storage = new JsonStorage();

    	foreach ($blocks as $block) {
    		$block_json = $block["path"] . "/block.json";

    		if (!file_exists($block_json)) {
    			$storage->store($block_json, $this->blockDefault);
    		}

    		$block['data'] = $this->readFileAsJson($block_json);
    	}

    	return $blocks;
    }


    public function saveData($data) {

        $stripped_groups = array();
        $blocks = array();

        foreach ($data["groups"] as $group) {
            $stripped_groups[] = array(
                "name" => $group->name,
                "id" => $group->id,
                "order" => $group->order 
            );
            $blocks[] = $group->blocks;
        }

        $data["groups"] = $stripped_groups;
        
        // Save all the blocks separately...
        unset($data["blocks"]);


        foreach ($blocks as $blockArray) {
            foreach ($blockArray as $block) {
                $actual_block = $block;
                $path = $this->dir . "/" . $actual_block->path . "/block.json";
                $this->save($path, $actual_block);
            }
        }


        $filename = $this->getJsonFilename();
        $this->save($filename, $data);
    }
}