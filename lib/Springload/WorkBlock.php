<?php
/**
 * Created by PhpStorm.
 * User: josh
 * Date: 19/05/14
 * Time: 4:17 PM
 */

namespace Springload;

use Springload\Common;
use Springload\JsonModel;
use Springload\DirectoryList;


class WorkBlock extends JsonModel {

    protected $jsonFile = "work-block.json";

    protected $defaults = array(
        "date" => "",
        "name" => ""
    );

    public $children;

    public $base_dir = false;

    public function getChildren() {
        $lister = new DirectoryList($this->path);

        $children = $lister->ls(null, array("base_dir" => $this->base_dir));
        $newChildren = array();

        foreach ($children as $child) {

            $childObject = new Sequence($child);

            $images = $childObject->getImages();

            if (sizeof($images) == 0) {
                continue;
            }

            $newChildren[] = $childObject->toArray();
        }

        $this->children = $newChildren;
        return $newChildren;
    }

    public function toArray() {
        date_default_timezone_set("Pacific/Auckland");
        $array = $this->data;
        $array["children"] = $this->getChildren();
        $array["url"] = $this->data['name'];
        $array["base_dir"] = $this->data['base_dir'];
        $array["data"] = $this->getJson();
        $array["type"] = get_class($this);

        $jason = $this->getJson();

        if ($jason) {
            $array["name"] = $jason["name"];
            $array["date"] = strtotime($jason["date"]);
        }

        return $array;
    }

    public function fromArray($data) {
        unset($data["children"]);
        return $data;
    }
}