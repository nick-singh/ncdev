<?php

	/**
	* This class is responsible for all database logic 
	* it handles the storing/updating/deleting/retreiving of data to and from the database
	*/

	class DbHandler
	{		
		private $db;
		private $dph;
		private $fh;
		private $resolve;

		protected static $instance = null;		

		protected  function __construct(){

			$this->db = DbConnect::getInstance();    
			$this->dph = DuplicateHandler::getInstance();
			$this->fh = FileHandler::getInstance();
			$this->resolve = Resolve::getInstance();
			
		}

		public static function getInstance(){
			
	        if (!isset(static::$instance)) {
	            static::$instance = new static;
	        }
	        return static::$instance;
	    }


	    /**
	    *			Mobile Authentication
	    * 	mobile device sends user's imei number 	    
	    * 	and the user's information is 
	    * 	returned
	    */
	    public function getUser($req){

	    	$sql = "select u.id,u.fname,u.lname,u.type 
	    			from user u , phonedetails pd 
	    			where pd.userid = u.id and pd.imei =  :imei";
			$imei = $req->post('imei');
	    	try {

		    	$con = $this->db->getConnection();
		    	$stmt = $con->prepare($sql);
		        $stmt->bindParam("imei", $imei);        		        
		        $stmt->execute();
        		$user = $stmt->fetchAll(PDO::FETCH_OBJ);
		        $con = null;
		        
		        echo json_encode($user);

		    } catch(PDOException $e) {
		        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		    }

	    }

	    /**
	    *			Web Application Authentication
	    * 	user's log in using username and password
	    * 	relivent user's information is returned
	    */
	    public function logIn($req){

			$username = $req->post('username'); 
			$password = sha1($req->post('password'));

			$sql = "SELECT username,password, type,id FROM user WHERE username = :username and password = :password";

			try {
		        $con = $this->db->getConnection();
		        $stmt = $con->prepare($sql);
		        $stmt->bindParam("username", $username);        
		        $stmt->bindParam("password", $password);        
		        $stmt->execute();
		        $user = $stmt->fetchObject();
		        $con = null;
		        
		        echo json_encode($user);
		        
		    } catch(PDOException $e) {
		      
		        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		    }
		}


		/**
		* Given and array of objects containing a deviceTimestamp 
		* sortByTimestamp will return the array in ascending order
		*/
		private function sortByTimestamp($tracks){
			$array = array();
			
			// echo json_encode($tracks);
			foreach ($tracks as $key => $v) {
				$array[$key] = strtotime($v->deviceTimestamp);							
			}
			array_multisort($array, SORT_ASC, $tracks);
			return $tracks;
		}		


		/**
		*	getStartTrackid returns a the last start track id that was stored in the database for any given user
		*/
		private function getStartTrackid($userid){
			$sql = "SELECT t.starttrackid from trackpoints t inner join 
					(select max(t1.deviceTimestamp) as m,t1.starttrackid, t1.userid 
					from trackpoints t1 where t1.userid = :userid and t1.starttrackid is not null) as tem on 
					t.userid = tem.userid and tem.m = t.deviceTimestamp";

			try {
		        $con = $this->db->getConnection();
		        $stmt = $con->prepare($sql);
		        $stmt->bindParam("userid", $userid);        		              
		        $stmt->execute();
		        $user = $stmt->fetchObject();
		        $con = null;
		        
		        // echo json_encode($user);
		        return $user->starttrackid; 
		        
		    } catch(PDOException $e) {
		      
		        // echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		        return null;
		    }
		}


		/**
		*	insetTrack accepts a track object to be inserted into the database
		*	if succseeful, returns a starttackid associated with the inserted track
		*	else returns null	
		*/
		private function insertTrack($data){
			
			$userid = $data->userid;
			$rssi = $data->rssi;
			$accuracy = $data->accuracy;
			$altitude = $data->altitude;
			$bearing = $data->bearing;
			$latitude = $data->latitude;
			$longitude = $data->longitude;
			$speed = $data->speed;
			$mockprovider = $data->mockprovider;
		    $deviceTimestamp = date('Y-m-d H:i:s', strtotime($data->deviceTimestamp));
		    $type = $data->type;		

		    if($type === 'start'){
		    	// if the type is start then the starttrackid must be null
		    	$starttrackid = null;
		    }else{
		    	// else send the start track id to the user
		    	$starttrackid = $this->getStartTrackid($userid);
		    	// $starttrackid = $data->starttrackid;
		    }    
		    // echo ($starttrackid);
	    	$sql = "INSERT INTO trackpoints(`userid`,`rssi`,`accuracy`,`altitude`,`bearing`,`latitude`,`longitude`,`speed`,`mockprovider`, `deviceTimestamp`,`type`, `starttrackid`) values 
			(:userid,:rssi,:accuracy,:altitude,:bearing,:latitude,:longitude,:speed,:mockprovider,:deviceTimestamp, :type, :starttrackid)";

			try {

		    	$con = $this->db->getConnection();
		    	$stmt = $con->prepare($sql);		        
		        $stmt->bindParam("userid", $userid);
		        $stmt->bindParam("rssi", $rssi);
		        $stmt->bindParam("accuracy", $accuracy);
		        $stmt->bindParam("altitude", $altitude);
		        $stmt->bindParam("bearing", $bearing);        
		        $stmt->bindParam("latitude", $latitude);        
		        $stmt->bindParam("longitude", $longitude);        
		        $stmt->bindParam("speed", $speed);        
		        $stmt->bindParam("mockprovider", $mockprovider);      
		        $stmt->bindParam("deviceTimestamp", $deviceTimestamp); 
		        $stmt->bindParam("type", $type); 
				$stmt->bindParam("starttrackid", $starttrackid); 		             		        
		        $stmt->execute();
		        // if the type is start, you want to get the 
		        // last id that was inserted into the database
		        // i.e. the starttrackid
		        if($type === 'start'){
			    	$starttrackid = $con->lastInsertId();
			    	$sql = "UPDATE trackpoints set starttrackid = :id where id = :id";

					try {
				        $con = $this->db->getConnection();
				        $stmt = $con->prepare($sql);
				        $stmt->bindParam("id", $starttrackid);        				              
				        $stmt->execute();				       
				        $con = null;				        				       
				        
				    } catch(PDOException $e) {
				      
				        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
				    }
			    }
			    else{
			    	// $starttrackid = $data->starttrackid;
			    	$starttrackid = $this->getStartTrackid($userid);
			    }
			    if($type === 'sos'){
		        	$sosEmail = SosEmail::getInstance();
    				$sosEmail->sendEmail($con->lastInsertId(), $userid);
		        }

        		// $last = array('trackId' => $starttrackid);
		        $con = null;
		        
		        // echo json_encode($last);
		        return $starttrackid;

		    } catch(PDOException $e) {
		        // echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		        // echo '{"status":"unsuccessful"}'; 
		        return null;
		    }
		}

		/**
		*			Mobile User's Tracks
		*	User sends track, only takes one track at a time
		*	The first track is the starttrack, when entered into the 
		* 	database, that trackid is tetrieved and send back to the user
		* 	as the start trackid. This is to facilitate keeping track of all 
		*	the tracks relating to the frist track.
		*	
		*	If the type of track is sos an email is sent out to the 
		*	Relivant authorties
		*	
		*/
	    public function postTracks($req){
	    
	    	$tracks = json_decode($req->post('tracks'));
	    	$tracks = $this->sortByTimestamp($tracks);
	    	$starttrackid = null;	    	
	    	$suc = true;	    	
	    	$duplicates = array();
	    	
	    	$id = $tracks[0]->userid;
	    		    

			foreach ($tracks as $key => $value) {
				
				$params = array("latitude" =>$value->latitude, "longitude"=>$value->longitude, "deviceTimestamp" => date('Y-m-d H:i:s', strtotime( $value->deviceTimestamp)), "userid" =>$value->userid);	

				if(null!== $this->insertTrack($value)){					
				}else{						
					array_push($duplicates, $params);
				}														
			}

			$file = $this->fh->createFile($id);
			// if the file does not exists
			if(!$this->fh->findFile($file)){				
				// if there were no duplicates
				if(count($duplicates)>0){
					// if something went wrong in dumping data
					if(!$this->fh->addData($file,$duplicates)) $suc = false;
				}
			}else{				
				// if there were no duplicates
				if(count($duplicates)>0){
					// if something went wrong in dumping data
					if(!$this->fh->appendData($file,$duplicates))$suc = false;
				}
			}
			if($suc){
	    		$last = array('trackId' => $id);
	    		echo json_encode($last);
	    	}else{
	    		echo '{"status":"unsuccessful"}'; 
	    	}						
	    }



	    /**
	    *			Track Dates
	    *	Gets all the unique dates from the tracks table.
	    * 	This relates all the dates where there was activity at sea
	    */
	    public function getDates(){

		   $sql = "SELECT distinct DATE_FORMAT(deviceTimestamp, '%Y-%m-%d') AS 'start' from trackpoints";
		   
		    try {

		    	$con = $this->db->getConnection();
		        $stmt = $con->query($sql);  
		        $dates = $stmt->fetchAll(PDO::FETCH_OBJ);
		        $con = null;
		        
		        echo json_encode($dates);

		    } catch(PDOException $e) {
		        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		    }
		}

		/**
		*			Sos Markers
		*	Retrives all tracks of type sos regurdless of 
		*	date, this is to indicate that this sos has
		*	not been resolved yet
		*/
		private function getSosMarkers(){
			$sql = "SELECT distinct t1.id, t1.userid, t1.latitude, t1.longitude, DAY( t1.deviceTimestamp ) AS day, t1.starttrackid,
					MONTH( t1.deviceTimestamp ) AS month, 
					t1.type, t1.bearing, u.fname, u.lname, pd.phonenum, t1.speed, t1.deviceTimestamp as time
					FROM trackpoints t1, user u, phonedetails pd
					WHERE u.id = t1.userid
					AND u.id = pd.userid
					AND t1.type = 'sos'";					

			try {
		        $con = $this->db->getConnection();
		        $stmt = $con->prepare($sql);		              
		        $stmt->execute();
		        $points = $stmt->fetchAll(PDO::FETCH_OBJ);
		        $con = null;
		        
		        return $points;
		        
		    } catch(PDOException $e) {
		      
		        // echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		        return null;
		    }
		}

		/**
		*		Getting Track By Day
		*	For each day in a month on a calendar, this retrives all markers
		*	for any given day. For each user, get the last marker/track they made
		*	in that day. The start track id is included for querying users tracks
		*/
		public function getMarkersByDay($day, $month){							

			$sql = "SELECT distinct t1.userid, t1.latitude, t1.longitude, DAY( t1.deviceTimestamp ) AS day, 
					t1.starttrackid, MONTH( t1.deviceTimestamp ) AS month, 
					t1.type, t1.bearing, u.fname, u.lname, pd.phonenum, t1.speed, t1.deviceTimestamp as time
					FROM trackpoints t1
					inner JOIN user u 
					ON u.id = t1.userid
					inner JOIN phonedetails pd 
					ON pd.userid = u.id
					inner JOIN (SELECT max(t2.deviceTimestamp) max_time, t2.starttrackid
					      FROM trackpoints t2      
					      where day(t2.deviceTimeStamp) = :day
						  and month(t2.deviceTimeStamp) = :month
					      group by t2.starttrackid) AS tr 
					ON tr.starttrackid = t1.starttrackid
					AND tr.max_time = t1.deviceTimeStamp";

			try {
		        $con = $this->db->getConnection();
		        $stmt = $con->prepare($sql);
		        $stmt->bindParam("day", $day);        
		        $stmt->bindParam("month", $month);        
		        $stmt->execute();
		        $points = $stmt->fetchAll(PDO::FETCH_OBJ);
		        $sos = $this->getSosMarkers();

		        // Include sos markers in this query
		        foreach ($sos as $key => $value) {
		        	array_push($points, $value);
		        }
		        $con = null;
		        
		        echo json_encode($points);
		        
		    } catch(PDOException $e) {
		      
		        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		    }
		}

		/**
		*		User Tracks
		* 	for any given day, for any userid, given the start track id
		* 	get all the tracks associated with this track
		*	2014-07-16 14:27:33		
		*/
		public function getUserTracks($day, $month, $userid, $starttrackid){
			$sql = "SELECT t1.id,t1.userid, t1.latitude, t1.longitude, DAY( t1.deviceTimestamp ) AS day, t1.starttrackid,
					MONTH( t1.deviceTimestamp ) AS month, 
					t1.type, t1.bearing, u.fname, u.lname, pd.phonenum, t1.speed, t1.deviceTimestamp as time
					FROM trackpoints t1, user u, phonedetails pd
					WHERE u.id = t1.userid
					AND u.id = pd.userid										
					AND t1.userid = :userid
					AND DAY( t1.deviceTimestamp ) <= :day
					AND MONTH(t1.deviceTimestamp) <= :month
					AND (t1.starttrackid = :starttrackid
					OR t1.id = :starttrackid)
					order by time asc";

			try {
		        $con = $this->db->getConnection();
		        $stmt = $con->prepare($sql);
		        $stmt->bindParam("userid", $userid);
		        $stmt->bindParam("day", $day);        
		        $stmt->bindParam("month", $month);
		        $stmt->bindParam("starttrackid", $starttrackid);        
		        $stmt->execute();
		        $points = $stmt->fetchAll(PDO::FETCH_OBJ);
		        $con = null;
		        
		        echo json_encode($points);
		        
		    } catch(PDOException $e) {
		      
		        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		    }
		}



		/**
		*	User's Current Trips
		*	for the purpose of the coastguard interface, given the start track id an userid
		*	getting the deviceTimestamp of that track, find any trips that fit within that deviceTimestamp
		*	if a trip is found, that is used as the current trip relatiing to the track
		*/
		public function getUserCurrentTrip($starttrackid, $userid, $start){

			$sql = "SELECT u.fname, u.lname, pd.phonenum as phoneNum, t.userid, t.latitude, t.longitude, 
					DATE_FORMAT( t.deviceTimestamp, '%W %D %M %Y %r') as start FROM `tripheaders` t, user u, phonedetails pd 
					where t.userid = :userid and t.userid = u.id and t.userid = pd.userid and 
					date(t.deviceTimestamp) = date(:start)
					and t.starttrackid = :starttrackid order by t.deviceTimestamp desc";				

			try {
		        $con = $this->db->getConnection();
		        $stmt = $con->prepare($sql);
		        $stmt->bindParam("userid", $userid);		        
		        $stmt->bindParam("start", $start);    
		        $stmt->bindParam("starttrackid", $starttrackid);               		        
		        $stmt->execute();
		        $points = $stmt->fetchAll(PDO::FETCH_OBJ);
		        $con = null;
		        
		        echo json_encode($points);
		        
		    } catch(PDOException $e) {
		      
		        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		    }			
		
		}


		/**
		*	setTripHeader accepts and array of trip header objects to be insterted into the database
		*	the array is sorted in ascending order according to deviceTimestamp
		*/

		public function setTripHeader($req){
			$header = json_decode($req->post('tripheader'));
			$header = $this->sortByTimestamp($header);
			foreach ($header as $key => $value) {
				
				$value->starttrackid = $this->getStartTrackid($value->userid);				
				$sql = "INSERT into tripheaders (`userid`,`latitude`,`longitude`,`name`,`deviceTimestamp`,`starttrackid`) 
						values (:userid,:latitude,:longitude,:name,:deviceTimestamp, :starttrackid)";

				$stmt = $this->dph->exeQuery($sql,$value);
				try {

					$stmt->execute();
					echo json_encode($this->db->getStatus(200));
					
				} catch (PDOException $e) {

					if($e->getCode() !== '23000'){					
						echo json_encode($this->db->getStatus(500));
						echo $e;
					}else{
						echo json_encode($this->db->getStatus(409));
					}				
				}
			}								
		}

		/**
		*	updateSosStatus updates the status of a SOS track from SOS to resolved given the id of the track
		*	this signifies that the sos is resolved
		*/

		private function updateSosStatus($id){
			$sql = "Update trackpoints set type = 'resolved' where id = :id";				

			try {
		        $con = $this->db->getConnection();
		        $stmt = $con->prepare($sql);
		        $stmt->bindParam("id", $id);        				              
		        if($stmt->execute()){
		        	$con = null;	
		        	return true;
		        }				     		        			        				      
		        
		    } catch(PDOException $e) {
		      
		        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		        return false;
		    }
		}

		/**
		*	resoloveSOs accepts track information to be resolved and stored into a table
		*	the track information is stored in a table recoding the circumstances to why there was a SOS
		*	and how it was resolved along with the coastguard who resolved it
		*	
		*	An email is also sent out to notifiy that the sos has been resolved
		*/

		public function resolveSos($req){

			$sql = "INSERT into resolvedsos(`trackid`,`badgeno`,`caserefno`,`details`) values
					(:trackid,:badgeno,:caserefno,:details)";
			if($this->updateSosStatus($req->id)){
				try {

			        $con = $this->db->getConnection();
			        $stmt = $con->prepare($sql);
			        $stmt->bindParam("trackid", $req->id);		        
			        $stmt->bindParam("badgeno", $req->badgeno);    
			        $stmt->bindParam("caserefno", $req->caserefno);    
			        $stmt->bindParam("details", $req->details);               		        
			        if($stmt->execute()){
			        	$con = null;
			        	$this->resolve->sendEmail($req);
			        	echo json_encode($this->db->getStatus(200));	
			        }			        			        
			    } catch(PDOException $e) {
			      
			        echo '{"error":{"text":'. $e->getMessage() .'}}';
			        // echo json_encode($this->db->getStatus(409)); 
			    }		
			}			
		}


 
	}

?>