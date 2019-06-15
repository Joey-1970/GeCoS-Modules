<?
    // Klassendefinition
    class GeCoS_PWM16Out extends IPSModule 
    {
	// PCA9685
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 80);
		$this->RegisterPropertyInteger("DeviceBus", 0);
		$this->RegisterPropertyInteger("Frequency", 100);
		
		// Profil anlegen
		$this->RegisterProfileInteger("Intensity.4096", "Intensity", "", " %", 0, 4095, 1);
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 15; $i++) {
			$this->RegisterVariableBoolean("Output_Bln_X".$i, "Ausgang X".$i, "~Switch", ($i + 1) * 10);
			$this->EnableAction("Output_Bln_X".$i);	
			$this->RegisterVariableInteger("Output_Int_X".$i, "Ausgang X".$i, "Intensity.4096", (($i + 1) * 10) + 5);
			$this->EnableAction("Output_Int_X".$i);	
		}
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
		for ($i = 80; $i <= 87; $i++) {
		    	$arrayOptions[] = array("label" => $i." / 0x".strtoupper(dechex($i))."", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 0", "value" => 0);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 1", "value" => 1);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 2", "value" => 2);
		
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "GeCoS I²C-Bus", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "100", "value" => 100);
		$arrayOptions[] = array("label" => "200", "value" => 200);
		$arrayElements[] = array("type" => "Select", "name" => "Frequency", "caption" => "Frequenz (Hz)", "options" => $arrayOptions );
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
		
		// Summary setzen
		$this->SetSummary("0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." - I²C-Bus ".($this->ReadPropertyInteger("DeviceBus") - 4));

		$this->SetBuffer("ErrorCounter", 0);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				//ReceiveData-Filter setzen
				$Filter = '((.*"Function":"get_used_modules".*|.*"InstanceID":'.$this->InstanceID.'.*)|.*"Function":"status".*)';
				$this->SetReceiveDataFilter($Filter);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_modules", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->SetStatus(102);
				}
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
			case "get_used_modules":
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
		$this->SendDebug("SetOutputPinValue", "Ausfuehrung", 0);
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
			// Ausgang setzen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_4_byte", "InstanceID" => $this->InstanceID, "Register" => $StartAddress, "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
			// Ausgang abfragen
			$this->GetOutput($StartAddress + 2);
			//$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9685_Read", "InstanceID" => $this->InstanceID, "Register" => $StartAddress + 2)));
			//$this->SetStatusVariables($StartAddress + 2, $Result);
		}
	}
	
	public function SetOutputPinStatus(Int $Output, Bool $Status)
	{ 
		$this->SendDebug("SetOutputPinStatus", "Ausfuehrung", 0);
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
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_4_byte", "InstanceID" => $this->InstanceID, "Register" => $StartAddress, "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
			// Ausgang abfragen
			$this->GetOutput($StartAddress + 2);
			//$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9685_Read", "InstanceID" => $this->InstanceID, "Register" => $StartAddress + 2)));
			//$this->SetStatusVariables($StartAddress + 2, $Result);
		}
	}     
	    
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			// Mode 1 in Sleep setzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9685_Write", "InstanceID" => $this->InstanceID, "Register" => 0, "Value" => 16)));
			If (!$Result) {
				$this->SendDebug("Setup", "Ausfuehrung in Sleep setzen fehlerhaft!", 0);
			}
			IPS_Sleep(10);
			// Prescale einstellen
			$PreScale = round((25000000 / (4096 * $this->ReadPropertyInteger("Frequency"))) - 1);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9685_Write", "InstanceID" => $this->InstanceID, "Register" => 254, "Value" => $PreScale)));
			If (!$Result) {
				$this->SendDebug("Setup", "Prescale setzen fehlerhaft!", 0);
			}
			// Mode 1 in Sleep zurücksetzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9685_Write", "InstanceID" => $this->InstanceID, "Register" => 0, "Value" => 32)));
			If (!$Result) {
				$this->SendDebug("Setup", "Mode 1 setzen fehlerhaft!", 0);
			}
			// Mode 2 auf Ausgänge setzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9685_Write", "InstanceID" => $this->InstanceID, "Register" => 1, "Value" => 4)));
			If (!$Result) {
				$this->SendDebug("Setup", "Mode 2 setzen fehlerhaft!", 0);
			}
			// Ausgänge initial einlesen
			for ($i = 6; $i < 70; $i = $i + 4) {
				$this->GetOutput($i + 2);
				//$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9685_Read", "InstanceID" => $this->InstanceID, "Register" => $i + 2)));
				//$this->SetStatusVariables($i + 2, $Result);
			}
		}
	}
	
	private function GetOutput(Int $Register)
	{
		$this->SendDebug("GetOutput", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9685_Read", "InstanceID" => $this->InstanceID, "Register" => $Register)));
			if (($Result === NULL) OR ($Result < 0) OR ($Result > 65536)) {// Falls der Splitter einen Fehler hat und 'nichts' zurückgibt.
				$this->SetBuffer("ErrorCounter", ($this->GetBuffer("ErrorCounter") + 1));
				$this->SendDebug("GetOutput", "Keine gueltige Antwort: ".$Result, 0);
				IPS_LogMessage("GeCoS_PWM16Out", "GetOutput: Keine gueltige Antwort: ".$Result);
				If ($this->GetBuffer("ErrorCounter") <= 3) {
					$this->GetOutput($Register);
				}
			}
			else {
				$this->SendDebug("GetOutput", "Ergebnis: ".$Result, 0);
				$this->SetStatusVariables($Register, $Result);
				$this->SetBuffer("ErrorCounter", 0);
			}
		}
	}
	
	private function SetStatusVariables(Int $Register, Int $Value)
	{
		$Intensity = $Value & 4095;
		$Status = !boolval($Value & 4096); 
		$Number = ($Register - 8) / 4;
		
		$this->SendDebug("SetStatusVariables", "Itensitaet: ".$Intensity." Status: ".(int)$Status, 0);
		
		If ($Intensity <> GetValueInteger($this->GetIDForIdent("Output_Int_X".$Number))) {
			SetValueInteger($this->GetIDForIdent("Output_Int_X".$Number), $Intensity);
		}
		If ($Status <> GetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Number))) {
			SetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Number), $Status);
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
	 
	private function GetBoardVersion()
	{
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "getBoardVersion" )));	
	return $Result;
	}
	    
	protected function HasActiveParent()
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
