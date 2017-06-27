<?
    // Klassendefinition
    class GeCoS_16Out extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 25);
		$this->RegisterPropertyInteger("DeviceBus", 4);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Device konnte nicht gefunden werden");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		for ($i = 25; $i <= 31; $i++) {
		    	$arrayOptions[] = array("label" => $i." dez. / 0x".strtoupper(dechex($i))."h", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 0", "value" => 4);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 1", "value" => 5);
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "GeCoS I²C-Bus", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "label" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/'");
	
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}           
	  
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();

		//Status-Variablen anlegen
		for ($i = 0; $i <= 15; $i++) {
			$this->RegisterVariableBoolean("Output_X".$i, "Ausgang X".$i, "~Switch", ($i + 1) * 10);
			$this->EnableAction("Output_X".$i);	
		}
		
		$this->RegisterVariableInteger("OutputBank0", "Output Bank 0", "", 170);
          	$this->DisableAction("OutputBank0");
		IPS_SetHidden($this->GetIDForIdent("OutputBank0"), false);
		
		$this->RegisterVariableInteger("OutputBank1", "Output Bank 1", "", 180);
          	$this->DisableAction("OutputBank1");
		IPS_SetHidden($this->GetIDForIdent("OutputBank1"), false);
		
		If ((IPS_GetKernelRunlevel() == 10001) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				//ReceiveData-Filter setzen
				$Filter = '((.*"Function":"get_used_i2c".*|.*"InstanceID":'.$this->InstanceID.'.*)|.*"Function":"status".*)';
				$this->SetReceiveDataFilter($Filter);
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
			
				// Setup
				$this->Setup();
				$this->GetOutput();
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
				   	If ($this->ReadPropertyBoolean("Open") == true) {				
						$this->SetStatus($data->Status);
					}
					else {
						$this->SetStatus(104);
					}	
			   	}
			   	break;
			  case "set_i2c_byte_block":
			   	If ($data->InstanceID == $this->InstanceID) {
			   		$ByteArray = array();
					$ByteArray = unserialize($data->ByteArray);
					SetValueInteger($this->GetIDForIdent("OutputBank0"), $ByteArray[1]);
					SetValueInteger($this->GetIDForIdent("OutputBank1"), $ByteArray[2]);
					for ($i = 0; $i <= 7; $i++) {
						$Bitvalue = boolval($ByteArray[1]&(1<<$i));					
					    	If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) <> $Bitvalue) {
							SetValueBoolean($this->GetIDForIdent("Output_X".$i), $Bitvalue);
						}
					}
					for ($i = 8; $i <= 15; $i++) {
						$Bitvalue = boolval($ByteArray[2]&(1<<($i - 8)));					
					    	If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) <> $Bitvalue) {
							SetValueBoolean($this->GetIDForIdent("Output_X".$i), $Bitvalue);
						}
					}
			   	}
			  	break;
	 	}
 	}
	
	public function RequestAction($Ident, $Value) 
	{
		$Number = intval(substr($Ident, 8, 2));
		$this->SetOutputPin($Number, $Value);
	}
	    
	// Beginn der Funktionen
	public function SetOutputPin(Int $Output, Bool $Value)
	{
		$Output = min(15, max(0, $Output));
		$Value = min(1, max(0, $Value));
		If ($this->ReadPropertyBoolean("Open") == true) {
			If ($Output <= 7) {
				$Bitmask = GetValueInteger($this->GetIDForIdent("OutputBank0"));
				If ($Value == true) {
					$Bitmask = $this->setBit($Bitmask, $Output);
				}
				else {
					$Bitmask = $this->unsetBit($Bitmask, $Output);
				}
				$ByteArray = array();
				$ByteArray[0] = hexdec("02");
				$ByteArray[1] = $Bitmask;
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "InstanceID" => $this->InstanceID, "ByteArray" => serialize($ByteArray) )));
			}
			else {
				$Bitmask = GetValueInteger($this->GetIDForIdent("OutputBank1"));
				If ($Value == true) {
					$Bitmask = $this->setBit($Bitmask, $Output - 8);
				}
				else {
					$Bitmask = $this->unsetBit($Bitmask, $Output - 8);
				}
				$ByteArray = array();
				$ByteArray[0] = hexdec("03");
				$ByteArray[1] = $Bitmask;
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "InstanceID" => $this->InstanceID, "ByteArray" => serialize($ByteArray) )));
			}
			$this->GetOutput();
		}
	}	
	
	private function GetOutput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_bytes", "InstanceID" => $this->InstanceID, "Register" => $this->ReadPropertyInteger("DeviceAddress"), "Count" => 2)));
		}
	}
	    
	public function SetOutputBank(int $Bank, int $Value) 
	{
		$Value = min(255, max(0, $Value));
		$Bank = min(1, max(0, $Bank));
		$ByteArray = array();
		If ($Bank == 0) {
			$ByteArray[0] = hexdec("02");
			$ByteArray[1] = $Value;
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "InstanceID" => $this->InstanceID, "ByteArray" => serialize($ByteArray) )));
		}
		else {
			$ByteArray[0] = hexdec("03");
			$ByteArray[1] = $Value;
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "InstanceID" => $this->InstanceID, "ByteArray" => serialize($ByteArray) )));
		}
		$this->GetOutput();
	}
	    
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$ByteArray = array();
			$ByteArray[0] = hexdec("06");
			$ByteArray[1] = hexdec("00");
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "InstanceID" => $this->InstanceID, "ByteArray" => serialize($ByteArray) )));
			$ByteArray = array();
			$ByteArray[0] = hexdec("07");
			$ByteArray[1] = hexdec("00");
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "InstanceID" => $this->InstanceID, "ByteArray" => serialize($ByteArray) )));
		}
	}
	
	private function setBit($byte, $significance) { 
 		// ein bestimmtes Bit auf 1 setzen
 		return $byte | 1<<$significance;   
 	} 
	
	private function unsetBit($byte, $significance) {
	    // ein bestimmtes Bit auf 0 setzen
	    return $byte & ~(1<<$significance);
	}
	    
	private function HasActiveParent()
    	{
		$Instance = @IPS_GetInstance($this->InstanceID);
		if ($Instance['ConnectionID'] > 0)
		{
			$Parent = IPS_GetInstance($Instance['ConnectionID']);
			if ($Parent['InstanceStatus'] == 102)
			return true;
		}
        return false;
    	}  
}
?>
