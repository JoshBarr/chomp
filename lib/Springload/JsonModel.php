<?php
/**
 * Created by PhpStorm.
 * User: josh
 * Date: 19/05/14
 * Time: 4:21 PM
 */
namespace Springload;

use Springload\Common;
use Springload\JsonStorage;

class JsonModel {

    protected $jsonFile = "data.json";
    public $base_dir;

    public function __construct($data) {
        $data = json_decode(json_encode($data), true);
        $this->data = $data;
        $this->path = $data['path'];

        if (array_key_exists("base_dir", $data)) {
            $this->base_dir = $data["base_dir"];
        }
    }

    public function ensureFileExists($file) {
//        echo $file;
//
//        if (!Common::startsWith($file, "/")) {
//            $file =
//        }
        $path = $this->base_dir . "/" . $file;
//        echo $path . "\n<br><br>\n";
        return $path;
    }

    public function fromArray($data) {
        return $data;
    }

    public function save() {
        $data = $this->fromArray($this->data);
        $storage = new JsonStorage();
        $storage->store($this->ensureFileExists($this->getJsonFile()), $data);
    }

    public function getJsonFile() {
        return $this->path . "/" . $this->jsonFile;
    }

    public function toArray() {
        $array = $this->data;
        $array["url"] = $this->data['name'];
        return $array;
    }

    public function getJson() {

        if (is_dir($this->path)) {
            $filename = $this->getJsonFile();

            if (file_exists($filename)) {
//                $this->createJsonDefault($filename);
                return $this->readFileAsJson($filename);
            }
        }
        return false;
    }

    public function readFileAsJson($filename) {
        if (file_exists($filename)) {
            $string = file_get_contents($filename);
            return json_decode($string, true);
        }
        return array();
    }
}