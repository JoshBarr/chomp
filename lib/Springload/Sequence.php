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

class Sequence extends JsonModel {

    public $images;

    protected $jsonFile = "sequence.json";

    protected $defaults = array(
        "name" => "A title for this set of images",
        "description" => "A description for this set of images"
    );

    public function getImages() {
        $url = $this->data["name"];
        $imagePath = $this->path;
        $images = new ImageList($imagePath);
        $this->images = $images->ls();
        return $this->images;
    }

    public function toArray() {

        $array = $this->data;
        $array["images"] = $this->images;
        $array["url"] = $this->data['name'];
        $array["relpath"] = $this->data['relpath'];
        $array["type"] = get_class($this);

        $jason = $this->getJson();

        if ($jason) {
            $array["name"] = $jason["name"];
            $array["description"] = $jason["description"];
        }

        return $array;
    }

    public function fromArray($data) {
        return $data;
    }
}