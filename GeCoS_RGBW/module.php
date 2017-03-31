<?
    // Klassendefinition
    class GeCoS_RGBW extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 88);
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
		for ($i = 88; $i <= 95; $i++) {
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
	  
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
            	//Connect to available splitter or create a new one
	    	$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
	    	
	    	// Profil anlegen
		$this->RegisterProfileInteger("Intensity.4096", "Intensity", "", " %", 0, 4095, 1);
		
		$Output = array(); 
		$this->SetBuffer("Output", serialize($Output));
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 4; $i++) {
			$this->RegisterVariableBoolean("Status_".($i + 1), "Status ".($i + 1), "~Switch", 10 * $i);
			$this->EnableAction("Status_".($i + 1));
			$this->RegisterVariableInteger("Intensity_R_".($i + 1), "Intensity Rot ".($i + 1), "Intensity.4096",20 );
			$this->EnableAction("Intensity_R_".($i + 1));
			$this->RegisterVariableInteger("Intensity_G_".($i + 1), "Intensity Grün ".($i + 1), "Intensity.4096", 30);
			$this->EnableAction("Intensity_G_".($i + 1));
			$this->RegisterVariableInteger("Intensity_B_".($i + 1), "Intensity Blau ".($i + 1), "Intensity.4096", 40);
			$this->EnableAction("Intensity_B_".($i + 1));
			$this->RegisterVariableInteger("Color_".($i + 1), "Farbe ".($i + 1), "~HexColor", 50);
			$this->EnableAction("Color_".($i + 1));
		}
		
		If (IPS_GetKernelRunlevel() == 10103) {
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			$this->SetReceiveDataFilter($Filter);
		
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				
				// Setup
				$this->Setup();
				//$this->GetOutput();
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
			   	If ($data->DeviceIdent == $this->GetBuffer("DeviceIdent")) {
				   	$this->SetStatus($data->Status);
			   	}
			   	break;
			case "set_i2c_data":
			  	If ($data->DeviceIdent == $this->GetBuffer("DeviceIdent")) {
			  		$Output = array(); 
					$Output = unserialize($this->GetBuffer("Output"));
					// Daten zur Kalibrierung
			  		If (($data->Register >= 6) AND ($data->Register < 70)) {
			  			$Output[$data->Register] = $data->Value;
			  		}
					
					If ($data->Register % 2 !=0) {
						$Number = ($data->Register - 9) / 4;
						$Value = (($Output[$data->Register] & 15) << 8)  | $Output[$data->Register - 1]; 
						$Status = boolval($Output[$data->Register] & 16);
						If ($Value <> GetValueInteger($this->GetIDForIdent("Output_Int_X".$Number))) {
							SetValueInteger($this->GetIDForIdent("Output_Int_X".$Number), $Value);
						}
						If ($Status <> !GetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Number))) {
							SetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Number), !$Status);
						}	
					}
					
				}
				$this->SetBuffer("Output", serialize($Output));
			  	break; 
	 	}
 	}
	
	public function RequestAction($Ident, $Value) 
	{
		$Source = substr($Ident, 7, 3);  
		$Number = intval(substr($Ident, 12, 2));
		
		switch($Source) {
		case "Bln":
			$this->SetOutputPinStatus($Number, $Value);
	            	break;
		case "Int":
	            	$this->SetOutputPinValue($Number, $Value);
	            	break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	    
	// Beginn der Funktionen
	public function SetOutputPinValue(Int $Output, Int $Value)
	{ 
		$Output = min(15, max(0, $Output));
		$Value = min(4095, max(0, $Value));
		
		$ByteArray = array();
		$StartAddress = ($Output * 4) + 6;
		$Status = GetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Output));
		$L_Bit = $Value & 255;
		$H_Bit = $Value >> 8;
		
		If ($Status == true) {
			$H_Bit = $this->unsetBit($H_Bit, 4);
		}
		else {
			$H_Bit = $this->setBit($H_Bit, 4);
		}
		If ($this->ReadPropertyBoolean("Open") == true) {
			/*
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $StartAddress, "Value" => 0)));
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $StartAddress + 1, "Value" => 0)));
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $StartAddress + 2, "Value" => $L_Bit)));
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $StartAddress + 3, "Value" => $H_Bit)));
			*/
			
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_4_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $StartAddress, "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
			
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $StartAddress + 2)));
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $StartAddress + 3)));
			//$this->GetOutput();
		}
	}
	
	public function SetOutputPinStatus(Int $Output, Bool $Status)
	{ 
		$Output = min(15, max(0, $Output));
		$Status = min(1, max(0, $Status));
		
		$ByteArray = array();
		$StartAddress = ($Output * 4) + 6;
		$Value = GetValueInteger($this->GetIDForIdent("Output_Int_X".$Output));
		$L_Bit = $Value & 255;
		$H_Bit = $Value >> 8;
		
		If ($Status == true) {
			$H_Bit = $this->unsetBit($H_Bit, 4);
		}
		else {
			$H_Bit = $this->setBit($H_Bit, 4);
		}
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Ausgang setzen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_4_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $StartAddress, "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
			// Ausgang abfragen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $StartAddress + 2)));
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $StartAddress + 3)));
		}
	}    
	    
	private function GetOutput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			for ($i = 6; $i < 70; $i = $i + 4) {
	    			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $i + 2)));
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $i + 3)));
			}
		}
	}    
	    
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Mode 1 in Sleep setzen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0, "Value" => 16)));
			IPS_Sleep(10);
			// Prescale einstellen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 254, "Value" => 61)));
			// Mode 1 in Sleep zurücksetzen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0, "Value" => 0)));
			// Mode 2 auf Ausgänge setzen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 1, "Value" => 4)));
		}
	}
	
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);    
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