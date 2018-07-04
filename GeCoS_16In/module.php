<?
    // Klassendefinition
    class GeCoS_16In extends IPSModule 
    {
	// PCA9655E
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("GetInput", 0);
	}
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 16);
		$this->RegisterPropertyInteger("DeviceBus", 4);
		$this->RegisterTimer("GetInput", 0, 'GeCoS16In_GetInput($_IPS["TARGET"]);');
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 15; $i++) {
			$this->RegisterVariableBoolean("Input_X".$i, "Eingang X".$i, "~Switch", ($i + 1) * 10);
			$this->DisableAction("Input_X".$i);	
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
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "I²C-Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		for ($i = 16; $i <= 23; $i++) {
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
		
		// Summary setzen
		$this->SetSummary("0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." - I²C-Bus ".($this->ReadPropertyInteger("DeviceBus") - 4));
	
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {			
			If ($this->ReadPropertyBoolean("Open") == true) {	
				//ReceiveData-Filter setzen
				$Filter = '((.*"Function":"get_used_i2c".*|.*"InstanceID":'.$this->InstanceID.'.*)|(.*"Function":"status".*|.*"Function":"interrupt".*))';
				$this->SetReceiveDataFilter($Filter);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));		
				If ($Result == true) {
					// Setup
					$this->Setup();
					$this->GetInput();
					$this->SetTimerInterval("GetInput", 15 * 1000);
				}
			}
			else {
				$this->SetStatus(104);
				$this->SetTimerInterval("GetInput", 0);
			}	
		}
		else {
			$this->SetStatus(104);
			$this->SetTimerInterval("GetInput", 0);
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
			case "interrupt":
				If ($this->ReadPropertyBoolean("Open") == true) {
					If ($this->ReadPropertyInteger("DeviceBus") == $data->DeviceBus) {
						$this->GetInput();
					}
				}
				break;	
			case "interrupt_with_result":
				If (($this->ReadPropertyBoolean("Open") == true) AND ($data->InstanceID == $this->InstanceID)) {
					$this->SendDebug("interrupt_with_result", "Ausfuehrung", 0);
					for ($i = 0; $i <= 15; $i++) {
						$Bitvalue = boolval(intval($data->Value) & pow(2, $i));					
						If (GetValueBoolean($this->GetIDForIdent("Input_X".$i)) <> $Bitvalue) {
							SetValueBoolean($this->GetIDForIdent("Input_X".$i), $Bitvalue);
						}
					}
				}
				break;		
	 	}
 	}
	    
	// Beginn der Funktionen
	public function GetInput()
	{
		$this->SendDebug("GetInput", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			$tries = 3;
			do {
				$Result= $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Read", "InstanceID" => $this->InstanceID, "Register" => 0)));
				If ($Result < 0) {
					$this->SendDebug("GetInput", "Einlesen der Werte fehlerhaft!", 0);
					$this->SetStatus(202);
				}
				else {
					$this->SendDebug("GetInput", "Ergebnis: ".$Result, 0);
					$this->SetStatus(102);
					for ($i = 0; $i <= 15; $i++) {
						$Bitvalue = boolval($Result & pow(2, $i));					
						If (GetValueBoolean($this->GetIDForIdent("Input_X".$i)) <> $Bitvalue) {
							SetValueBoolean($this->GetIDForIdent("Input_X".$i), $Bitvalue);
						}
					}
					break;
				}
			$tries--;
			} while ($tries);  
		}
	}

	private function Setup()
	{
		$this->SendDebug("Setup", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			$tries = 3;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Write", "InstanceID" => $this->InstanceID, "Register" => 6, "Value" => 65535 )));
				If (!$Result) {
					$this->SetStatus(202);
					$this->SendDebug("Setup", "nicht erfolgreich!", 0);
				}
				else {
					$this->SetStatus(102);
					$this->SendDebug("Setup", "erfolgreich", 0);
					break;
				}
			$tries--;
			} while ($tries); 
		}
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
}
?>
