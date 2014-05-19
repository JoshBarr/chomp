<?php 
namespace Springload;
use Springload\DirectoryList;
use Springload\Common;
use Springload\Sequence;
use Springload\WorkBlock;


class ClientProject extends DirectoryList
{
	protected $jsonName = "project.json";

    public $dir = false;

	public $jsonDefault = array(
		"name" => "Project name",
		"job_code" => "SL000",
		"preview_url" => "http://some-client.co.nz",
		"basecamp_url" => "http://basecamp.com/XXX",
		"description" => "This is the project description",
		"groups" => array()
	);

	public function __construct($clientObject, $project) {
        $this->dir = $clientObject->getDir() . "/" . $project; 
        $this->client = $clientObject;
    }

    public function getData() {
    	$data = $this->getJson();
        $data["blocks"] = $this->getBlocks();
    	return $data;
    }


    public function isBlock($path) {
        return Common::startsWith($path, "block-");
    }


    public function getBlocks() {

        $newBlocks = array();
        $blocks = $this->ls(false, array(
            "ignore_empty" => true,
            "base_dir" => $this->dir,
            "order" => "modified_desc"
        ));

        foreach ($blocks as $block) {
            if ($this->isBlock($block["relpath"])) {
                $blockObject = new WorkBlock(
                    array_merge($block,
                        array(
                            "base_dir" => $this->dir
                        )
                    )
                );
            } else {
                $blockObject = new Sequence(
                    array_merge($block,
                        array(
                            "base_dir" => $this->dir
                        )
                    )
                );

                if (sizeof($blockObject->getImages()) == 0) {
                    continue;
                }
            }

            $newBlocks[] = $blockObject->toArray();
        }

        return $this->sortBlocks($newBlocks);
    }

    public function sortBlocks($blocks) {
        // Sort by blocks first
        usort($blocks, function($a, $b) {

            if (array_key_exists("children", $a) && !array_key_exists("children", $b)) {
                return -1;
            }

            if (array_key_exists("children", $b) && !array_key_exists("children", $a)) {
                return 1;
            }

            // Sort blocks by mtime
            if (array_key_exists("children", $b) && array_key_exists("children", $a)) {
                return $a['mtime'] - $b['mtime'];
            }


            if (!array_key_exists("children", $b) && !array_key_exists("children", $a)) {

                // Sort items by order if it's set
                if (array_key_exists("order", $a) && array_key_exists("order", $b)) {
                    return $a['order'] - $b['order'];

                    // Otherwise sort by mtime
                } else {
                    return $a['ctime'] - $b['ctime'];
                }
            }
        });

        return $blocks;
    }

     public function saveData($data) {
         $stripped_groups = array();
         $blocks = array();



         foreach ($data["groups"] as $block) {
             if ($this->isBlock($block['path'])) {
                 $blockObject = new WorkBlock(array_merge($block, array(
                     "base_dir" => $this->dir
                 )));

                 $blockObject->save();
                 $this->saveTheChildren($block["children"]);

             // Deal with defaults...
             } else if ($block["path"] == "") {
                 $blockObject = new WorkBlock(array_merge($block, array(
                     "base_dir" => $this->dir
                 )));

                 $this->saveTheChildren($block["children"]);
             } else {

                 $sequenceObject = new Sequence(array_merge($block, array(
                     "base_dir" => $this->dir
                 )));
                 $sequenceObject->save();
             }
         }



         $filename = $this->getJsonFilename();
         $this->save($filename, $data);

     }

    public function saveTheChildren($children) {
        foreach($children as $child) {
            $sequenceObject = new Sequence(array_merge($child, array(
                "base_dir" => $this->dir
            )));
            $sequenceObject->save();
        }
    }
}