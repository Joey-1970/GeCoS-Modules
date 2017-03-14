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
			$this->RegisterVariableBoolean("Output_X".$i, "Ausgang X".$i, "~Switch", ($i + 1) * 10);
			$this->EnableAction("Output_X".$i);	
		}
		
		$this->RegisterVariableInteger("OutputBank0", "Output Bank 0", "", 170);
          	$this->DisableAction("OutputBank0");
		IPS_SetHidden($this->GetIDForIdent("OutputBank0"), false);
		
		$this->RegisterVariableInteger("OutputBank1", "Output Bank 1", "", 180);
          	$this->DisableAction("OutputBank1");
		IPS_SetHidden($this->GetIDForIdent("OutputBank1"), false);
		
		If (IPS_GetKernelRunlevel() == 10103) {
			// Logging setzen
			
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';

			$this->SetReceiveDataFilter($Filter);
		
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				
				// Setup
				$this->Setup();
				$this->GetPinOutput();
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
			  case "set_i2c_byte_block":
			   	If ($data->DeviceIdent == $this->GetBuffer("DeviceIdent")) {
			   		$ByteArray = array();
					$ByteArray = unserialize($data->ByteArray);
					SetValueInteger($this->GetIDForIdent("OutputBank0"), $ByteArray[1]);
					SetValueInteger($this->GetIDForIdent("OutputBank1"), $ByteArray[2]);
					//IPS_LogMessage("GeCoS_16Out", "Bank 0: ".$ByteArray[1]);
					//IPS_LogMessage("GeCoS_16Out", "Bank 1: ".$ByteArray[2]);
					for ($i = 0; $i <= 7; $i++) {
						$Bitvalue = boolval($ByteArray[1]&(1<<$i));					
					    	If ($Bitvalue == true) {
							If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) == false) {
								SetValueBoolean($this->GetIDForIdent("Output_X".$i), true);
							}
					    	}
					    	else {
							If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) == true) {
								SetValueBoolean($this->GetIDForIdent("Output_X".$i), false);
							}
					    	}
					}
					for ($i = 8; $i <= 15; $i++) {
						$Bitvalue = boolval($ByteArray[2]&(1<<($i - 8)));					
					    	If ($Bitvalue == true) {
							If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) == false) {
								SetValueBoolean($this->GetIDForIdent("Output_X".$i), true);
							}
					    	}
					    	else {
							If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) == true) {
								SetValueBoolean($this->GetIDForIdent("Output_X".$i), false);
							}
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
		case "Output_X0":
	            $this->SetPinOutput(0, $Value);
	            break;
	        case "Output_X1":
	            $this->SetPinOutput(1, $Value);
	            break;
	        case "Output_X2":
	            $this->SetPinOutput(2, $Value);
	            break;
	        case "Output_X3":
	            $this->SetPinOutput(3, $Value);
	            break;
	        case "Output_X4":
	            $this->SetPinOutput(4, $Value);
	            break;
	        case "Output_X5":
	            $this->SetPinOutput(5, $Value);
	            break;    
	        case "Output_X6":
	            $this->SetPinOutput(6, $Value);
	            break;
	        case "Output_X7":
	            $this->SetPinOutput(7, $Value);
	            break;
	        case "Output_X8":
	            $this->SetPinOutput(8, $Value);
	            break;
	        case "Output_X9":
	            $this->SetPinOutput(9, $Value);
	            break;
	        case "Output_X10":
	            $this->SetPinOutput(10, $Value);
	            break;
	        case "Output_X11":
	            $this->SetPinOutput(11, $Value);
	            break;
	        case "Output_X12":
	            $this->SetPinOutput(12, $Value);
	            break;
	        case "Output_X13":
	            $this->SetPinOutput(13, $Value);
	            break;    
	        case "Output_X14":
	            $this->SetPinOutput(14, $Value);
	            break;
	        case "Output_X15":
	            $this->SetPinOutput(15, $Value);
	            break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	    
	// Beginn der Funktionen
	// Führt eine Messung aus
	public function SetPinOutput(Int $Output, Bool $Value)
	{
		$Bitmask = 0;
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
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "ByteArray" => serialize($ByteArray) )));
			}
			else {
				$Bitmask = GetValueInteger($this->GetIDForIdent("OutputBank1"));
				If ($Value == true) {
					$Bitmask = $this->setBit($Bitmask, $Output);
				}
				else {
					$Bitmask = $this->unsetBit($Bitmask, $Output);
				}
				$ByteArray = array();
				$ByteArray[0] = hexdec("03");
				$ByteArray[1] = $Bitmask;
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "ByteArray" => serialize($ByteArray) )));
			}
			$this->GetPinOutput();
		}
	}	
	
	private function GetPinOutput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $this->ReadPropertyInteger("DeviceAddress"), "Count" => 2)));
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
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "ByteArray" => serialize($ByteArray) )));
		}
		else {
			$ByteArray[0] = hexdec("03");
			$ByteArray[1] = $Value;
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "ByteArray" => serialize($ByteArray) )));
		}
		$this->GetPinOutput();
	}
	    
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$ByteArray = array();
			$ByteArray[0] = hexdec("06");
			$ByteArray[1] = hexdec("00");
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "ByteArray" => serialize($ByteArray) )));
			$ByteArray = array();
			$ByteArray[0] = hexdec("07");
			$ByteArray[1] = hexdec("00");
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "ByteArray" => serialize($ByteArray) )));
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
}
?>
