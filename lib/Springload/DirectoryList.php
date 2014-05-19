<?php
namespace Springload;

use DirectoryIterator;

class DirectoryList
{
    protected $dir;

    public function __construct($dir)
    {
        $this->dir = $dir;        
    }
    
    public function ls($directory = false, $options = array())
    {
        if ($directory == false) {
            $directory = $this->dir;
        }

        $defaults  = array(
            "ignore_empty" => false,
            "base_dir" => false
        );

        $options = array_merge($defaults, $options);

        $directories = array();

        if (!is_dir($directory)) {
            return $directories;
        }

        foreach (new DirectoryIterator($directory) as $fileInfo) {
            $dir = $fileInfo->getPathname();
            $name = $fileInfo->getFilename();
            $project_data = array();

            if ($fileInfo->isDot()) continue;
            if (!$fileInfo->isDir()) continue;

            if ($options["ignore_empty"]) {
                if (!Common::startsWith($name,"block-")) {
                    if (count(glob("$dir/*.{jpg,png,gif,mp4,json}", GLOB_BRACE)) === 0) {
                        continue;
                    }
                }
            }

            if (!preg_match("/^[a-zA-Z0-9_\.\-]*$/", $name )) {
                continue;
            }

            $info = array(
                "name" => $name,
                "mtime" => $fileInfo->getMTime(),
                "ctime" => $fileInfo->getCTime(),
                "path" => $dir
            );

            if ($options["base_dir"]) {
                $info["relpath"] = str_replace($options["base_dir"]."/", "", $dir);
            }

            $directories[] = $info;
        }
        
        return $directories;
    }

    public function getJsonFilename() {
        return $this->dir . "/" . $this->jsonName;
    }

    public function getJson() {

        if (is_dir($this->dir)) {
            $filename = $this->getJsonFilename();
            
            if (!file_exists($filename)) {
                $this->createJsonDefault($filename);
            }

            return $this->readFileAsJson($filename);
        }
    }

    public function readFileAsJson($filename) {
        if (file_exists($filename)) {
            $string = file_get_contents($filename);
            return json_decode($string, true);
        }
        return array();
    }

    public function createJsonDefault($filename) {
        $this->save($filename, $this->jsonDefault);
    }

    public function save($filename, $data) {
        $storage = new JsonStorage();
        $storage->store($filename, $data);
    }

    public function saveData($data) {
        $filename = $this->getJsonFilename();
        $this->save($filename, $data);
    }
}