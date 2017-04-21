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
			$Filter = '((.*"Function":"set_serial_data".*|.*"InstanceID":'.$this->InstanceID.'.*)|(.*"Function":"status".*))';
			$this->SetReceiveDataFilter($Filter);
			
			$this->SetStatus(102);
		}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "status":
			   	/*
				If ($data->InstanceID == $this->InstanceID) {
				   	$this->SetStatus($data->Status);
			   	}
				*/
			   	break;
						
			case "set_serial_data":
			   	If ($this->ReadPropertyInteger("SelectScript") > 0) { 
					$Message = utf8_decode($data->Buffer);
					IPS_RunScriptEx($this->ReadPropertyInteger("SelectScript"), Array("RegVar_GetBuffer" => $Message));
				}
			  	break;
	 	}
 	}
	    
 	public function SendText(String $Message)
	{
		$Message = utf8_encode($Message);
		$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "serial_write", "Message" => $Message)));
	}
}
?>
