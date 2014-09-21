<?php
	include 'dbConnect.php';
	include 'duplicateHandler.php';
	include 'fileHandler.php';


	class Cron_job 
	{

		private $duplicateHandler;
		private $fileHandler;
		private $fileDir;
		
		function __construct()
		{
			$this->duplicateHandler = DuplicateHandler::getInstance();
			$this->fileHandler = FileHandler::getInstance();

			$this->fileDir = $this->fileHandler->readFiles();
		}

		private function updateDB($data){
			foreach ($data as $key => $value) {
				// echo json_encode($value);
				$this->duplicateHandler->updateDupRow($value);				
			}
			
		}

		public function exe(){
			
			if($this->fileDir[0] !== ".."){
				$content = array();
				$msc=microtime(true);
				for ($i=0; $i < count($this->fileDir)-2; $i++) { 
					
					$content = $this->fileHandler->openFile($this->fileDir[$i]);					
					$this->updateDB($content);
					$this->fileHandler->removeFile($this->fileDir[$i]);
				}
				$msc=microtime(true)-$msc;
		    	echo $msc.' seconds';
			}			
		}
	}	


	$cron = new Cron_job();

	$cron->exe();

?>