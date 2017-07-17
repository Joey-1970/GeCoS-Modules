<?
    // Klassendefinition
    class GeCoS_RGBW extends IPSModule 
    {
	// PCF8591
	    
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
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Device konnte nicht gefunden werden");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		for ($i = 88; $i <= 95; $i++) {
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
	  
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();

		// Profil anlegen
		$this->RegisterProfileInteger("Intensity.4096", "Intensity", "", " %", 0, 4095, 1);
		
		$Output = array(); 
		$this->SetBuffer("Output", serialize($Output));
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 3; $i++) {
			$this->RegisterVariableBoolean("Status_RGB_".($i + 1), "Status RGB ".($i + 1), "~Switch", 10 + ($i * 70));
			$this->EnableAction("Status_RGB_".($i + 1));
			$this->RegisterVariableInteger("Color_RGB_".($i + 1), "Farbe ".($i + 1), "~HexColor", 20 + ($i * 70));
			$this->EnableAction("Color_RGB_".($i + 1));
			$this->RegisterVariableInteger("Intensity_R_".($i + 1), "Intensity Rot ".($i + 1), "Intensity.4096", 30 + ($i * 70) );
			$this->EnableAction("Intensity_R_".($i + 1));
			$this->RegisterVariableInteger("Intensity_G_".($i + 1), "Intensity Grün ".($i + 1), "Intensity.4096", 40 + ($i * 70));
			$this->EnableAction("Intensity_G_".($i + 1));
			$this->RegisterVariableInteger("Intensity_B_".($i + 1), "Intensity Blau ".($i + 1), "Intensity.4096", 50 + ($i * 70));
			$this->EnableAction("Intensity_B_".($i + 1));
			$this->RegisterVariableBoolean("Status_W_".($i + 1), "Status Weiß ".($i + 1), "~Switch", 60 + ($i * 70));
			$this->EnableAction("Status_W_".($i + 1));
			$this->RegisterVariableInteger("Intensity_W_".($i + 1), "Intensity Weiß ".($i + 1), "Intensity.4096", 70 + ($i * 70));
			$this->EnableAction("Intensity_W_".($i + 1));			
		}
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				//ReceiveData-Filter setzen
				$Filter = '((.*"Function":"get_used_i2c".*|.*"InstanceID":'.$this->InstanceID.'.*)|.*"Function":"status".*)';
				$this->SetReceiveDataFilter($Filter);
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));

				// Setup
				$this->Setup();
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
			case "set_i2c_data":
			  	If ($data->InstanceID == $this->InstanceID) {
			  		$this->SendDebug("set_i2c_data", "Ausfuehrung", 0);
					$Output = array(); 
					$Output = unserialize($this->GetBuffer("Output"));
					// Daten zur Kalibrierung
			  		
					
					If (($data->Register >= 6) AND ($data->Register < 70)) {
			  			$Output[$data->Register] = $data->Value;
			  		}
					
					If ($data->Register % 2 !=0) {
						$ChannelArray = [
						    0 => "R",
						    4 => "G",
						    8 => "B",
						    12=> "W",

						];
						
						$Group = intval(($data->Register - 9) / 16) + 1;
						$Channel = ($data->Register - 9) - (($Group - 1) * 16);
						$Value = (($Output[$data->Register] & 15) << 8)  | $Output[$data->Register - 1]; 
						$Status = boolval($Output[$data->Register] & 16);
						
						If ($Value <> GetValueInteger($this->GetIDForIdent("Intensity_".$ChannelArray[$Channel]."_".$Group))) {
							SetValueInteger($this->GetIDForIdent("Intensity_".$ChannelArray[$Channel]."_".$Group), $Value);
						}
						If ($ChannelArray[$Channel] == "W") {
							If ($Status <> !GetValueBoolean($this->GetIDForIdent("Status_W_".$Group))) {
								SetValueBoolean($this->GetIDForIdent("Status_W_".$Group), !$Status);
							}
						}
						else {
							If ($Status <> !GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group))) {
								SetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group), !$Status);
							}
						}
						// Farbrad setzen
						$Value_R = intval(255 / 4095 * GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group)));
						$Value_G = intval(255 / 4095 * GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group)));
						$Value_B = intval(255 / 4095 * GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group)));
						SetValueInteger($this->GetIDForIdent("Color_RGB_".$Group), $this->RGB2Hex($Value_R, $Value_G, $Value_B));
							
					}
					$this->SetBuffer("Output", serialize($Output));
				}
			  	break; 
	 	}
 	}
	
	public function RequestAction($Ident, $Value) 
	{
		$Parts = explode("_", $Ident);
		$Source = $Parts[0];
		$Channel = $Parts[1];
		$Group = $Parts[2];
		
		switch($Source) {
		case "Status":
			$this->SetOutputPinStatus($Group, $Channel, $Value);
	            	break;
		case "Color":
	            	$this->SetOutputPinColor($Group, $Value);
	            	break;
		case "Intensity":
	            	$this->SetOutputPinValue($Group, $Channel, $Value);
	            	break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
		
	}
	    
	// Beginn der Funktionen
	public function SetOutputPinValue(Int $Group, String $Channel, Int $Value)
	{ 
		$this->SendDebug("SetOutputPinValue", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$Value = min(4095, max(0, $Value));
		
		$ChannelArray = [
		    "R" => 0,
		    "G" => 4,
		    "B" => 8,
		    "W" => 12,
		];
		
		$StartAddress = (($Group - 1) * 16) + $ChannelArray[$Channel] + 6;
		
		If ($Channel == "W") {
			$Status = GetValueBoolean($this->GetIDForIdent("Status_W_".$Group));
		}
		else {
			$Status = GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group));
		}
		
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
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_4_byte", "InstanceID" => $this->InstanceID, "Register" => $StartAddress, "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
			// Ausgang abfragen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_2_byte", "InstanceID" => $this->InstanceID, "Register" => $StartAddress + 2)));
		}
	}
	
	public function SetOutputPinStatus(Int $Group, String $Channel, Bool $Status)
	{ 
		$this->SendDebug("SetOutputPinStatus", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$Status = min(1, max(0, $Status));
				
		$ChannelArray = [
		    "RGB" => 0,
		    "W" => 12,
		];
		
		$StartAddress = (($Group - 1) * 16) + $ChannelArray[$Channel] + 6;

		If ($Channel == "W") {
			$Value = GetValueInteger($this->GetIDForIdent("Intensity_W_".$Group));
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
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_4_byte", "InstanceID" => $this->InstanceID, "Register" => $StartAddress, "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
				// Ausgang abfragen
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_2_byte", "InstanceID" => $this->InstanceID, "Register" => $StartAddress + 2)));
			}
		}
		else {
			$Value_R = GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group));
			$L_Bit_R = $Value_R & 255;
			$H_Bit_R = $Value_R >> 8;
			$Value_G = GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group));
			$L_Bit_G = $Value_G & 255;
			$H_Bit_G = $Value_G >> 8;
			$Value_B = GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group));
			$L_Bit_B = $Value_B & 255;
			$H_Bit_B = $Value_B >> 8;
			If ($Status == true) {
				$H_Bit_R = $this->unsetBit($H_Bit_R, 4);
				$H_Bit_G = $this->unsetBit($H_Bit_G, 4);
				$H_Bit_B = $this->unsetBit($H_Bit_B, 4);
			}
			else {
				$H_Bit_R = $this->setBit($H_Bit_R, 4);
				$H_Bit_G = $this->setBit($H_Bit_G, 4);
				$H_Bit_B = $this->setBit($H_Bit_B, 4);
			}
			If ($this->ReadPropertyBoolean("Open") == true) {
				// Ausgang setzen
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_12_byte", "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
									  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit_R, "Value_4" => $H_Bit_R, "Value_5" => 0, "Value_6" => 0, "Value_7" => $L_Bit_G, "Value_8" => $H_Bit_G, "Value_9" => 0, "Value_10" => 0, "Value_11" => $L_Bit_B, "Value_12" => $H_Bit_B)));
				// Ausgang abfragen
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_6_byte", "InstanceID" => $this->InstanceID, "Register" => $StartAddress + 2)));
				//$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCF8591_Read", "InstanceID" => $this->InstanceID, "Register" => $i + 2)));
				//$this->SendDebug("Setup", "Aktueller Zustand des Ausgangs ".($i - 6).": ".$Result, 0);
			}
		}		
	}    	    
	    
	public function SetOutputPinColor(Int $Group, Int $Color)
	{
		$this->SendDebug("SetOutputPinColor", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		
		// Farbwerte aufsplitten
		list($Value_R, $Value_G, $Value_B) = $this->Hex2RGB($Color);
		
		$StartAddress = (($Group - 1) * 16) + 6;
		$Status = GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group));
		// Werte skalieren
		$Value_R = 4095 / 255 * $Value_R;
		$Value_G = 4095 / 255 * $Value_G;
		$Value_B = 4095 / 255 * $Value_B;
		
		$L_Bit_R = $Value_R & 255;
		$H_Bit_R = $Value_R >> 8;
		$L_Bit_G = $Value_G & 255;
		$H_Bit_G = $Value_G >> 8;
		$L_Bit_B = $Value_B & 255;
		$H_Bit_B = $Value_B >> 8;
		If ($Status == true) {
			$H_Bit_R = $this->unsetBit($H_Bit_R, 4);
			$H_Bit_G = $this->unsetBit($H_Bit_G, 4);
			$H_Bit_B = $this->unsetBit($H_Bit_B, 4);
		}
		else {
			$H_Bit_R = $this->setBit($H_Bit_R, 4);
			$H_Bit_G = $this->setBit($H_Bit_G, 4);
			$H_Bit_B = $this->setBit($H_Bit_B, 4);
		}
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Ausgang setzen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_12_byte", "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
								  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit_R, "Value_4" => $H_Bit_R, "Value_5" => 0, "Value_6" => 0, "Value_7" => $L_Bit_G, "Value_8" => $H_Bit_G, "Value_9" => 0, "Value_10" => 0, "Value_11" => $L_Bit_B, "Value_12" => $H_Bit_B)));
			// Ausgang abfragen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_6_byte", "InstanceID" => $this->InstanceID, "Register" => $StartAddress + 2)));
		}
	}
	    
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			// Mode 1 in Sleep setzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCF8591_Write", "InstanceID" => $this->InstanceID, "Register" => 0, "Value" => 16)));
			If (!$Result) {
				$this->SendDebug("Setup", "Ausfuehrung in Sleep setzen fehlerhaft!", 0);
			}
			IPS_Sleep(10);
			// Prescale einstellen
			//$PreScale = round((25000000 / (4096 * $this->ReadPropertyInteger("Frequency"))) - 1);
			$PreScale = 30;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCF8591_Write", "InstanceID" => $this->InstanceID, "Register" => 254, "Value" => $PreScale)));
			If (!$Result) {
				$this->SendDebug("Setup", "Prescale setzen fehlerhaft!", 0);
			}
			// Mode 1 in Sleep zurücksetzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCF8591_Write", "InstanceID" => $this->InstanceID, "Register" => 0, "Value" => 0)));
			If (!$Result) {
				$this->SendDebug("Setup", "Mode 1 setzen fehlerhaft!", 0);
			}
			// Mode 2 auf Ausgänge setzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCF8591_Write", "InstanceID" => $this->InstanceID, "Register" => 1, "Value" => 4)));
			If (!$Result) {
				$this->SendDebug("Setup", "Mode 2 setzen fehlerhaft!", 0);
			}
			// Ausgänge initial einlesen
			for ($i = 6; $i < 70; $i = $i + 4) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_2_byte", "InstanceID" => $this->InstanceID, "Register" => $i + 2)));
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCF8591_Read", "InstanceID" => $this->InstanceID, "Register" => $i + 2)));
				$this->SendDebug("Setup", "Aktueller Zustand des Ausgangs ".($i - 6).": ".$Result, 0);
			}
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
	    
	private function Hex2RGB($Hex)
	{
		$r = (($Hex >> 16) & 0xFF);
		$g = (($Hex >> 8) & 0xFF);
		$b = (($Hex >> 0) & 0xFF);	
	return array($r, $g, $b);
	}
	
	private function RGB2Hex($r, $g, $b)
	{
		$Hex = hexdec(str_pad(dechex($r), 2,'0', STR_PAD_LEFT).str_pad(dechex($g), 2,'0', STR_PAD_LEFT).str_pad(dechex($b), 2,'0', STR_PAD_LEFT));
	return $Hex;
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
