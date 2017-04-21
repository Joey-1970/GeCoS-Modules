<?
    // Klassendefinition
    class GeCoS_RegVar extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterPropertyInteger("SelectScript", 0);
		$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
 	    	
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "SelectScript", "type" => "SelectScript",  "caption" => "Ziel"); 
		
			
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}           
	  
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
            	//Connect to available splitter or create a new one
	    	$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
	    	
		
		If (IPS_GetKernelRunlevel() == 10103) {
			// Logging setzen
			
			//ReceiveData-Filter setzen
			//$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"InstanceID":'.$this->InstanceID.'.*)|(.*"Function":"status".*|.*"Function":"interrupt".*))';
			$this->SetReceiveDataFilter($Filter);
			
			/*
			If ($this->ReadPropertyBoolean("Open") == true) {				
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}
			*/
		}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "status":
			   	If ($data->InstanceID == $this->InstanceID) {
				   	$this->SetStatus($data->Status);
			   	}
			   	break;
						
			case "set_i2c_byte_block":
			   	
			  	break;
	 	}
 	}
	    
 
}
?>
