<?php


	/**
	*	FileHandler provides services and functions to easily preform CRUD operations using php
	*
	*/

class FileHandler{

	private $path;
	protected static $instance = null;		

	protected  function __construct(){		
		// path to admin/
		$this_dir = dirname(__FILE__);

		// admin's parent dir path can be represented by admin/..
		$parent_dir = realpath($this_dir . '/../..');

		// concatenate the target path from the parent dir path
		$this->path = $parent_dir . '/projects/';  		 
	}

	public static function getInstance(){
		
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }


    public function readFiles(){
    	$results_array = array();

		if (is_dir($this->path))
		{
			try {
				if ($handle = opendir($this->path))
		        {
	                //Notice the parentheses I added:
	                while(($file = readdir($handle)) !== FALSE)
	                {
	                    $results_array[] = $file;
	                }
	                closedir($handle);
	                return $results_array;
		        }
			} catch (Exception $e) {
				echo $e;
			}	        
		}else{
			return null;
		}	

    }


    public function removeFile($file){
    	unlink($this->path.$file);
    }
}