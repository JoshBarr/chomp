<?php
namespace Springload;

use DirectoryIterator;
use SplFileInfo;
use CachingIterator;
use ArrayIterator;

function startswith($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}


class ImageList
{
    protected $dir;

    var $valid_extensions = array("mp4", "mov", "gif", "png", "jpg", "svg", "pdf");
    var $allow_types = array('image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/png', 'image/bmp', 'image/bitmap');

    public function __construct($dir)
    {
        $this->dir = $dir;        
    }
    
    public function ls()
    {
        $images = array();

        foreach (new DirectoryIterator($this->dir) as $fileInfo) {
            if (!in_array($fileInfo->getExtension(), $this->valid_extensions)) continue;
            // if ($fileInfo->isExecutable()) continue;

            if ($fileInfo->isDot()) continue;
            if ($fileInfo->isDir()) continue;
            if (startswith($fileInfo->getFilename(), ".")) continue; 
//            if (!preg_match("/^[a-zA-Z0-9_\.\-]*$/", $name )) continue;

            $images[] = array(
                "name" => $fileInfo->getFilename(),
                "mtime" => $fileInfo->getMTime(),
                "ctime" => $fileInfo->getCTime(),
                "path" => $fileInfo->getPathname(),
                "next" => false,
                "index" => sizeof($images) + 1

            );
        }

        $collection = new CachingIterator(
                  		new ArrayIterator(
                  			$images));
		$imgs = array();

		foreach($collection as $image) {
		    if ($collection->hasNext()) {
		        $image['next'] =  $collection->getInnerIterator()->current();
		    }
		    $imgs[] = $image;
		}

        $this->images = $imgs;
        return $imgs;
    }

    public function image_for($index = 1) {

    	if (!isset($this->images)) {
    		$this->ls();
    	}

        if (sizeof($this->images) > 0) {
            $image = $this->images[$index];
            return $image;
        }

    	return false;
    }
}