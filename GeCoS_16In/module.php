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
 	    	$this->RegisterPropertyInteger("DeviceAddress", 32);
		$this->RegisterPropertyInteger("DeviceBus", 4);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		for ($i = 32; $i <= 238; $i = $i + 2) {
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
			$this->RegisterVariableBoolean("Input_X".$i, "Eingang X".$i, "", ($i + 1) * 10);
			$this->DisableAction("Input_X".$i);	
		}
		
		$this->RegisterVariableInteger("InputBank0", "Input Bank 0", "", 170);
          	$this->DisableAction("InputBank0");
		IPS_SetHidden($this->GetIDForIdent("InputBank0"), false);
		
		$this->RegisterVariableInteger("InputBank1", "Input Bank 1", "", 180);
          	$this->DisableAction("InputBank1");
		IPS_SetHidden($this->GetIDForIdent("InputBank1"), false);
		
		If (IPS_GetKernelRunlevel() == 10103) {
			// Logging setzen
			
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*|.*"Function":"interrupt".*))';
			$this->SetReceiveDataFilter($Filter);
		
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				
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
			   	If ($data->HardwareRev <= 3) {
				   	If (($data->Pin == 0) OR ($data->Pin == 1)) {
				   		$this->SetStatus($data->Status);		
				   	}
			   	}
				else if ($data->HardwareRev > 3) {
					If (($data->Pin == 2) OR ($data->Pin == 3)) {
				   		$this->SetStatus($data->Status);
				   	}
				}
			   	break;
			case "interrupt":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->GetInput();
				}
				break;				
			case "set_i2c_byte_block":
			   	If ($data->DeviceIdent == $this->GetBuffer("DeviceIdent")) {
			   		$ByteArray = array();
					$ByteArray = unserialize($data->ByteArray);
					SetValueInteger($this->GetIDForIdent("InputBank0"), $ByteArray[1]);
					SetValueInteger($this->GetIDForIdent("InputBank1"), $ByteArray[2]);
					//IPS_LogMessage("GeCoS_16Out", "Bank 0: ".$ByteArray[1]);
					//IPS_LogMessage("GeCoS_16Out", "Bank 1: ".$ByteArray[2]);
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
	
	public function RequestAction($Ident, $Value) 
	{
  		//SetValueBoolean($this->GetIDForIdent($Ident), $Value);
		switch($Ident) {
		
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	    
	// Beginn der Funktionen
	private function GetInput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $this->ReadPropertyInteger("DeviceAddress"), "Count" => 2)));
		}
	}
	        
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$ByteArray = array();
			$ByteArray[0] = hexdec("06");
			$ByteArray[1] = hexdec("FF");
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "ByteArray" => serialize($ByteArray) )));
			$ByteArray = array();
			$ByteArray[0] = hexdec("07");
			$ByteArray[1] = hexdec("FF");
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "ByteArray" => serialize($ByteArray) )));
		}
	}
}
?>
