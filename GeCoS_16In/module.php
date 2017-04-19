<?
    // Klassendefinition
    class GeCoS_16In extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 16);
		$this->RegisterPropertyInteger("DeviceBus", 4);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		for ($i = 16; $i <= 23; $i++) {
		    	$arrayOptions[] = array("label" => $i." dez. / 0x".strtoupper(dechex($i))."h", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "GeCoS I²C-Bus");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 0", "value" => 4);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 1", "value" => 5);
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );
				
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}           
	  
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
            	//Connect to available splitter or create a new one
	    	$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
	    	
	    	// Profil anlegen
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 15; $i++) {
			$this->RegisterVariableBoolean("Input_X".$i, "Eingang X".$i, "~Switch", ($i + 1) * 10);
			$this->DisableAction("Input_X".$i);	
		}
		
		$this->RegisterVariableInteger("InputBank0", "Input Bank 0", "", 170);
          	$this->DisableAction("InputBank0");
		IPS_SetHidden($this->GetIDForIdent("InputBank0"), false);
		
		$this->RegisterVariableInteger("InputBank1", "Input Bank 1", "", 180);
          	$this->DisableAction("InputBank1");
		IPS_SetHidden($this->GetIDForIdent("InputBank1"), false);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// Logging setzen
			
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"InstanceID":'.$this->InstanceID.'.*)|(.*"Function":"status".*|.*"Function":"interrupt".*))';
			$this->SetReceiveDataFilter($Filter);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));

			If ($this->ReadPropertyBoolean("Open") == true) {				
				// Setup
				$this->Setup();
				$this->GetInput();
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}	
		}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "get_used_i2c":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->ApplyChanges();
				}
				break;
			case "status":
			   	If ($data->InstanceID == $this->InstanceID) {
				   	$this->SetStatus($data->Status);
			   	}
			   	break;
			case "interrupt":
				If ($this->ReadPropertyBoolean("Open") == true) {
					If ($this->ReadPropertyInteger("DeviceBus") == $data->DeviceBus) {
						$this->GetInput();
					}
				}
				break;				
			case "set_i2c_byte_block":
			   	If ($data->InstanceID == $this->InstanceID) {
			   		$ByteArray = array();
					$ByteArray = unserialize($data->ByteArray);
					SetValueInteger($this->GetIDForIdent("InputBank0"), $ByteArray[1]);
					SetValueInteger($this->GetIDForIdent("InputBank1"), $ByteArray[2]);
					for ($i = 0; $i <= 7; $i++) {
						$Bitvalue = boolval($ByteArray[1]&(1<<$i));					
					    	If (GetValueBoolean($this->GetIDForIdent("Input_X".$i)) <> $Bitvalue) {
							SetValueBoolean($this->GetIDForIdent("Input_X".$i), $Bitvalue);
						}
					}
					for ($i = 8; $i <= 15; $i++) {
						$Bitvalue = boolval($ByteArray[2]&(1<<($i - 8)));					
					    	If (GetValueBoolean($this->GetIDForIdent("Input_X".$i)) <> $Bitvalue) {
							SetValueBoolean($this->GetIDForIdent("Input_X".$i), $Bitvalue);
						}
					}
			   	}
			  	break;
	 	}
 	}
	    
	// Beginn der Funktionen
	private function GetInput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_bytes", "InstanceID" => $this->InstanceID, "Register" => $this->ReadPropertyInteger("DeviceAddress"), "Count" => 2)));
		}
	}
	        
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$ByteArray = array();
			$ByteArray[0] = hexdec("06");
			$ByteArray[1] = hexdec("FF");
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "InstanceID" => $this->InstanceID, "ByteArray" => serialize($ByteArray) )));
			$ByteArray = array();
			$ByteArray[0] = hexdec("07");
			$ByteArray[1] = hexdec("FF");
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "InstanceID" => $this->InstanceID, "ByteArray" => serialize($ByteArray) )));
		}
	}
	    
	/**
	* Check if a parent is active
	* @param $id integer InstanceID
	* @return bool
     	*/
    	protected function HasActiveParent($id = 0)
    	{
        	if ($id == 0) $id = $this->InstanceID;
        	$parent = $this->GetParent($id);
        	if ($parent > 0) {
            		$status = $this->GetInstanceStatus($parent);
            		if ($status == 102) {
                		return true;
            		} else {
                		//IPS_SetInstanceStatus($id, self::ST_NOPARENT);
                		$this->debug(__FUNCTION__, "Parent not active for Instance #" . $id);
                		return false;
			}
        	}
        	$this->debug(__FUNCTION__, "No Parent for Instance #" . $id);
        return false;
    	}
 	//------------------------------------------------------------------------------
    	/**
     	* Check if a parent for Instance $id exists
     	* @param $id integer InstanceID
     	* @return integer
     	*/
    	protected function GetParent($id = 0)
    	{
        	$parent = 0;
        	if ($id == 0) $id = $this->InstanceID;
        	if (IPS_InstanceExists($id)) {
            		$instance = IPS_GetInstance($id);
            		$parent = $instance['ConnectionID'];
        	} else {
            		$this->debug(__FUNCTION__, "Instance #$id doesn't exists");
        	}
        return $parent;
    	}
	//------------------------------------------------------------------------------
    	/**
     	* Retrieve instance status
     	* @param int $id
     	* @return mixed
     	*/
    	protected function GetInstanceStatus($id = 0)
    	{
        	if ($id == 0) $id = $this->InstanceID;
        	$inst = IPS_GetInstance($id);
        	return $inst['InstanceStatus'];
    	}	  
}
?>
