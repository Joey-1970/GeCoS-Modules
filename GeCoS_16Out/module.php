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
		
		$this->SetBuffer("OutputBank0", 0);
		$this->SetBuffer("OutputBank1", 0);
			
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
			If ($Output <= 7) {
				$Bitmask = $this->GetBuffer("OutputBank0");
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
				$Bitmask = $this->GetBuffer("OutputBank1");
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
		$this->SendDebug("GetOutput", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			//$Result= $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Read", "InstanceID" => $this->InstanceID, "Register" => 2, "Count" => 2)));
			IPS_Sleep(100);
			$Result= $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Read", "InstanceID" => $this->InstanceID, "Register" => 2)));
			if (($Result === NULL) OR ($Result > 0) OR ($Result > pow(2,16))) {// Falls der Splitter einen Fehler hat und 'nichts' zurückgibt.
				$this->SendDebug("GetOutput", "Keine gültige Antwort!", 0);
				return;
			}
			$this->SendDebug("GetOutput", "Ergebnis: ".$Result, 0);
			$this->SetBuffer("OutputBank0", $Result & 255);
			$this->SetBuffer("OutputBank1", $Result >> 8);
			for ($i = 0; $i <= 15; $i++) {
				$Bitvalue = boolval($Result & pow(2, $i));					
				If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) <> $Bitvalue) {
					SetValueBoolean($this->GetIDForIdent("Output_X".$i), $Bitvalue);
				}
			}
			/*
			$ByteArray = array();
			$ByteArray = unserialize($Result); 
			If (count($ByteArray) == 2) {
				$ByteArray[3] = ($ByteArray[2] << 8) | $ByteArray[1];
				$this->SendDebug("GetOutput", "Bank 0: ".$ByteArray[1]." Bank 1: ".$ByteArray[2]." Summe: ".$ByteArray[3], 0);
				$this->SetBuffer("OutputBank0", $ByteArray[1]);
				$this->SetBuffer("OutputBank1", $ByteArray[2]);

				$ByteArray[3] = ($ByteArray[2] << 8) | $ByteArray[1];
				$this->SendDebug("GetOutput", "Bank 0: ".$ByteArray[1]." Bank 1: ".$ByteArray[2]." Summe: ".$ByteArray[3], 0);
				for ($i = 0; $i <= 15; $i++) {
					$Bitvalue = boolval($ByteArray[3] & pow(2, $i));					
					If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) <> $Bitvalue) {
						SetValueBoolean($this->GetIDForIdent("Output_X".$i), $Bitvalue);
					}
				}
			}
			*/
		}
	}
	    
	public function SetOutputBank(int $Bank, int $Value) 
	{
		$Value = min(255, max(0, $Value));
		$Bank = min(1, max(0, $Bank));
		$ByteArray = array();
		$this->SendDebug("SetOutputBank", "Bank ".$Bank." Value: ".$Value, 0);
		$ByteArray[0] = 2;
		If ($Bank == 0) {
			$ByteArray[1] = $Value;
			$ByteArray[2] = $this->GetBuffer("OutputBank1");
		}
		else {
			$ByteArray[1] = $this->GetBuffer("OutputBank0");
			$ByteArray[2] = $Value;
		}
		$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "InstanceID" => $this->InstanceID, "ByteArray" => serialize($ByteArray) )));
		$this->GetOutput();
	}
	    
	private function Setup()
	{
		$this->SendDebug("Setup", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			$ByteArray = array();
			$ByteArray[0] = hexdec("06");
			$ByteArray[1] = hexdec("00");
			$ByteArray[2] = hexdec("00");
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
