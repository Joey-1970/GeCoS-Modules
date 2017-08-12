<?
    // Klassendefinition
    class GeCoS_16Out extends IPSModule 
    {
	// PCA9655E
	    
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
		
		$this->SetBuffer("OutputBank", 0);
		$this->SetBuffer("ErrorCounter", 0);

		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
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
			$Bitmask = $this->GetBuffer("OutputBank");
			If ($Value == true) {
				$Bitmask = $this->setBit($Bitmask, $Output);
			}
			else {
				$Bitmask = $this->unsetBit($Bitmask, $Output);
			}
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Write", "InstanceID" => $this->InstanceID, "Register" => 2, "Value" => $Bitmask )));
			If ($Result) {
				$this->SendDebug("SetOutputPin", "Output ".$Output." Value: ".$Value." erfolgreich", 0);
				$this->GetOutput();
			}
			else {
				$this->SendDebug("SetOutputPin", "Output ".$Output." Value: ".$Value." nicht erfolgreich!", 0);
				IPS_LogMessage("GeCoS_16Out", "SetOutputPin: Output ".$Output." Value: ".$Value." nicht erfolgreich!");	
			}

		}
	}	
	
	public function GetOutput()
	{
		$this->SendDebug("GetOutput", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Result= $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Read", "InstanceID" => $this->InstanceID, "Register" => 2)));
			if (($Result === NULL) OR ($Result < 0) OR ($Result > 65535)) {// Falls der Splitter einen Fehler hat und 'nichts' zurückgibt.
				$this->SetBuffer("ErrorCounter", ($this->GetBuffer("ErrorCounter") + 1));
				$this->SendDebug("GetOutput", "Keine gueltige Antwort: ".$Result, 0);
				IPS_LogMessage("GeCoS_16Out", "GetOutput: Keine gueltige Antwort: ".$Result);
				If ($this->GetBuffer("ErrorCounter") <= 3) {
					$this->GetOutput();
				}
			}
			else {
				$this->SendDebug("GetOutput", "Ergebnis: ".$Result, 0);
				$this->SetBuffer("OutputBank", $Result);

				for ($i = 0; $i <= 15; $i++) {
					$Bitvalue = boolval($Result & pow(2, $i));					
					If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) <> $Bitvalue) {
						SetValueBoolean($this->GetIDForIdent("Output_X".$i), $Bitvalue);
					}
				}
				$this->SetBuffer("ErrorCounter", 0);
			}
		}
	return $Result;
	}
	
	public function GetOutputPin(Int $Output)
	{
		$Output = min(15, max(0, $Output));
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Result= $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Read", "InstanceID" => $this->InstanceID, "Register" => 2)));
			if (($Result === NULL) OR ($Result < 0) OR ($Result > 65535)) {// Falls der Splitter einen Fehler hat und 'nichts' zurückgibt.
				$this->SetBuffer("ErrorCounter", ($this->GetBuffer("ErrorCounter") + 1));
				$this->SendDebug("GetOutput", "Keine gueltige Antwort: ".$Result, 0);
				IPS_LogMessage("GeCoS_16Out", "GetOutput: Keine gueltige Antwort: ".$Result);
				If ($this->GetBuffer("ErrorCounter") <= 3) {
					$this->GetOutput();
				}
			}
			else {
				$this->SendDebug("GetOutputPin", "Ergebnis: ".$Result, 0);
				$this->SetBuffer("OutputBank", $Result);

				for ($i = 0; $i <= 15; $i++) {
					$Bitvalue = boolval($Result & pow(2, $i));					
					If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) <> $Bitvalue) {
						SetValueBoolean($this->GetIDForIdent("Output_X".$i), $Bitvalue);
					}
				}
				$this->SetBuffer("ErrorCounter", 0);
			}
		}
		
	return boolval($Result & pow(2, $Output));
	}    
	    
	public function SetOutput(int $Value) 
	{
		$Value = min(65536, max(0, $Value));
		$this->SendDebug("SetOutputBank", "Value: ".$Value, 0);
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Write", "InstanceID" => $this->InstanceID, "Register" => 2, "Value" => $Value )));
		If ($Result) {
			$this->SendDebug("SetOutput", "Value: ".$Value." erfolgreich", 0);
			$this->GetOutput();
		}
		else {
			$this->SendDebug("SetOutput", "Value: ".$Value." nicht erfolgreich!", 0);
			IPS_LogMessage("GeCoS_16Out", "SetOutput: "."Value: ".$Value." nicht erfolgreich!");
		}
	}    
	    
	private function Setup()
	{
		$this->SendDebug("Setup", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Write", "InstanceID" => $this->InstanceID, "Register" => 6, "Value" => 0 )));
			If ($Result) {
				$this->SendDebug("Setup", "erfolgreich", 0);
			}
			else {
				$this->SendDebug("Setup", "nicht erfolgreich!", 0);
				IPS_LogMessage("GeCoS_16Out", "Setup: nicht erfolgreich!");
			}
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
