<?
    // Klassendefinition
    class GeCoS_4AnalogIn extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 105);
		$this->RegisterPropertyInteger("DeviceBus", 4);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterPropertyInteger("Resolution_0", 0);
		$this->RegisterPropertyInteger("Resolution_1", 0);
		$this->RegisterPropertyInteger("Resolution_2", 0);
		$this->RegisterPropertyInteger("Resolution_3", 0);
		$this->RegisterPropertyInteger("Amplifier_0", 0);
		$this->RegisterPropertyInteger("Amplifier_1", 0);
		$this->RegisterPropertyInteger("Amplifier_2", 0);
		$this->RegisterPropertyInteger("Amplifier_3", 0);
		$this->RegisterPropertyBoolean("Active_0", true);
		$this->RegisterPropertyBoolean("Active_1", true);
		$this->RegisterPropertyBoolean("Active_2", true);
		$this->RegisterPropertyBoolean("Active_3", true);
		$this->RegisterTimer("Messzyklus", 0, 'GeCoS4AnalogIn_GetInput($_IPS["TARGET"]);');
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
		for ($i = 105; $i <= 107; $i++) {
		    	$arrayOptions[] = array("label" => $i." dez. / 0x".strtoupper(dechex($i))."h", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "GeCoS I²C-Bus");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 0", "value" => 4);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 1", "value" => 5);
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );
		
		$arrayOptionsResolution = array();
		$arrayOptionsResolution[] = array("label" => "12 Bit", "value" => 0);
		$arrayOptionsResolution[] = array("label" => "14 Bit", "value" => 1);
		$arrayOptionsResolution[] = array("label" => "16 Bit", "value" => 2);
		$arrayOptionsResolution[] = array("label" => "18 Bit", "value" => 3);
		
		$arrayOptionsAmplifier = array();
		$arrayOptionsAmplifier[] = array("label" => "1x", "value" => 0);
		$arrayOptionsAmplifier[] = array("label" => "2x", "value" => 1);
		$arrayOptionsAmplifier[] = array("label" => "4x", "value" => 2);
		$arrayOptionsAmplifier[] = array("label" => "8x", "value" => 3);
		
		
		for ($i = 0; $i <= 3; $i++) {
			$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
			$arrayElements[] = array("type" => "Label", "label" => "Optionen Kanal ".($i + 1));
			$arrayElements[] = array("type" => "Label", "label" => "Auflösung des Kanals wählen (Default 12 Bit)");
			$arrayElements[] = array("name" => "Active_".$i, "type" => "CheckBox",  "caption" => "Aktiv"); 
			$arrayElements[] = array("type" => "Select", "name" => "Resolution_".$i, "caption" => "Auflösung", "options" => $arrayOptionsResolution );
			$arrayElements[] = array("type" => "Label", "label" => "Verstärkung des Kanals wählen (Default 1x)");
			$arrayElements[] = array("type" => "Select", "name" => "Amplifier_".$i, "caption" => "Verstärkung", "options" => $arrayOptionsAmplifier );
		}
				
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
            	
		// Profil anlegen
	    	$this->RegisterProfileFloat("GeCoS.mV", "Electricity", "", " mV", -100000, +100000, 0.1, 3);
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 3; $i++) {
			$this->RegisterVariableFloat("Input_X".$i, "Eingang X".$i, "GeCoS.mV", ($i + 1) * 10);
			$this->DisableAction("Input_X".$i);
			IPS_SetHidden($this->GetIDForIdent("Input_X".$i), false);
		}
		
		$MeasurementData = array();
		$this->SetBuffer("MeasurementData", serialize($MeasurementData));
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {			
			If ($this->ReadPropertyBoolean("Open") == true) {	
				//ReceiveData-Filter setzen
				$Filter = '((.*"Function":"get_used_i2c".*|.*"InstanceID":'.$this->InstanceID.'.*)|(.*"Function":"status".*|.*"Function":"interrupt".*))';
				$this->SetReceiveDataFilter($Filter);
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));		

				$this->GetInput();
			}
			else {
				$this->SetStatus(104);
			}	
		}
		else {
			$this->SendDebug("ApplyChanges", "Startrestriktionen nicht erfuellt!", 0);
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
			   		$this->SetBuffer("MeasurementData", $data->ByteArray);
			   	}
				// Test
				$MeasurementData = unserialize($this->GetBuffer("MeasurementData"));
				//IPS_LogMessage("IPS2GPIO MCP", "Anzahl Daten: ".count($MeasurementData));
				
				// Auslesen des Konfigurations-Registers
				$Configuration = $MeasurementData[count($MeasurementData)];
				$Amplifier = ($Configuration & 3);
				$Resolution = ($Configuration & 12) >> 2;
				$Channel = ($Configuration & 96) >> 5;
				$ReadyBit = ($Configuration & 128) >> 7;
				//IPS_LogMessage("IPS2GPIO MCP", "Anzahl Daten: ".count($MeasurementData)." Verst: ".$Amplifier." Aufl:: ".$Resolution." RDY:".$ReadyBit);
				If ($ReadyBit == false) {
					//IPS_LogMessage("IPS2GPIO MCP", "Channel: ".$Channel);
					switch ($Resolution) {
						case 0:	
							//IPS_LogMessage("IPS2GPIO MCP", "Auflösung 12 Bit");
							$SignBit = ($MeasurementData[1] & 8) >> 3;
							$Value = (($MeasurementData[1] & 15) << 8) | $MeasurementData[2];
							If ($SignBit == 0) {
								$Value = $Value;
							}
							else {
 								$Value = -($this->bitflip($Value));
							}
							break;
						case 1:
							//IPS_LogMessage("IPS2GPIO MCP", "Auflösung 14 Bit");
							$SignBit = ($MeasurementData[1] & 32) >> 5;
							$Value = (($MeasurementData[1] & 63) << 8) | $MeasurementData[2];
							If ($SignBit == 0) {
								$Value = $Value * 0.25;
							}
							else {
								$Value = -($this->bitflip($Value)) * 0.25;
							}
							break;
						case 2:	
							//IPS_LogMessage("IPS2GPIO MCP", "Auflösung 16 Bit");
							$SignBit = ($MeasurementData[1] & 128) >> 7;
							$Value = (($MeasurementData[1] & 255) << 8) | $MeasurementData[2];
							If ($SignBit == 0) {
								$Value = $Value * 0.0625;
							}
							else {
								$Value = -($this->bitflip($Value)) * 0.0625;
							}
							break;
						case 3:
							//IPS_LogMessage("IPS2GPIO MCP", "Auflösung 18 Bit");
							$SignBit = ($MeasurementData[1] & 2) >> 1;
							$Value = (($MeasurementData[1] & 3) << 16) | ($MeasurementData[2] << 8) | $MeasurementData[3];
							If ($SignBit == 0) {
								$Value = $Value * 0.015625;
							}
							else {
								$Value = -($this->bitflip($Value)) * 0.015625;
							}
							break;	
					}	
					SetValueFloat($this->GetIDForIdent("Input_X".($Channel + 1)), $Value);
				}
			   	break;
	 	}
 	}
	    
	// Beginn der Funktionen
	public function GetInput()
	{
		$this->SendDebug("GetInput", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Messwerterfassung setzen
			$i = 0;
			for ($i = 0; $i <= 3; $i++) {
				If ($this->ReadPropertyBoolean("Active_".$i) == true) {
					$Configuration = ($i << 5) | (1 << 4) | ($this->ReadPropertyInteger("Resolution_".$i) << 2) | $this->ReadPropertyInteger("Amplifier_".$i);
					//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte_onhandle", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Value" => $Configuration)));
					IPS_Sleep(320);
					If ($this->ReadPropertyInteger("Resolution_".$i) <= 2) { 
						//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $this->ReadPropertyInteger("DeviceAddress"), "Count" => 3)));
					}
					elseif ($this->ReadPropertyInteger("Resolution_".$i) == 3) {
						//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $this->ReadPropertyInteger("DeviceAddress"), "Count" => 4)));
					}
				}
			}
		}
	}
	    
	private function bitflip($Value)
	{
	   	// Umwandlung in einen Binär-String
		$bin = decbin($Value);
	   	$not = "";
	   	// Umstellung der Binär-Strings
		for ($i = 0; $i < strlen($bin); $i++)
	   		{
	      		if($bin[$i] == 0) { $not .= '1'; }
	      		if($bin[$i] == 1) { $not .= '0'; }
	   	}
		// Rückgabe als Integer
	return bindec($not);
	}
	    
	private function HasActiveParent()
    	{
		$this->SendDebug("HasActiveParent", "Ausfuehrung", 0);
		$Instance = @IPS_GetInstance($this->InstanceID);
		if ($Instance['ConnectionID'] > 0)
		{
			$Parent = IPS_GetInstance($Instance['ConnectionID']);
			if ($Parent['InstanceStatus'] == 102)
			return true;
		}
        return false;
    	}  
	    
	private function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 2);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 2)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	        IPS_SetVariableProfileDigits($Name, $Digits);
	}
}
?>
