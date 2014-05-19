<?php 
namespace Springload;
use Springload\DirectoryList;

class ClientList extends DirectoryList
{
	public function getData() {
		return $this->ls(null, array(
			"order"=>"modified_desc"
			)
		);
	}
}