<?
    // Klassendefinition
    class GeCoS_DS2413 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F1C0403-4A74-4F14-829F-9A217CFB2D05}");
		$this->RegisterPropertyString("DeviceAddress", "Sensor ID");
		$this->RegisterPropertyInteger("DeviceFunction_0", 1);
		$this->RegisterPropertyInteger("DeviceFunction_1", 1);
		$this->RegisterPropertyBoolean("Invert_0", false);
		$this->RegisterPropertyBoolean("Invert_1", false);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'GeCoSDS2413_Measurement($_IPS["TARGET"]);');
		
		//Status-Variablen anlegen
		$this->RegisterVariableBoolean("Status_0", "Status (0)", "~Switch", 10);
		If ($this->ReadPropertyInteger("DeviceFunction_0") == 0) {
			$this->EnableAction("Status_0");
		}
		else {
			$this->DisableAction("Status_0");
		}
		
		$this->RegisterVariableBoolean("Status_1", "Status (1)", "~Switch", 20);
		If ($this->ReadPropertyInteger("DeviceFunction_1") == 0) {
			$this->EnableAction("Status_1");
		}
		else {
			$this->DisableAction("Status_1");
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
		
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "DeviceAddress", "caption" => "Sensor ID");
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Digital Input", "value" => 1);
		$arrayOptions[] = array("label" => "Digital Output", "value" => 0);
		
		If ($this->ReadPropertyString("DeviceAddress") <> "Sensorauswahl") {
			$arrayElements[] = array("type" => "Select", "name" => "DeviceFunction_0", "caption" => "Port (0)", "options" => $arrayOptions );
			$arrayElements[] = array("name" => "Invert_0", "type" => "CheckBox",  "caption" => "Invert (0)");
			$arrayElements[] = array("type" => "Select", "name" => "DeviceFunction_1", "caption" => "Port (1)", "options" => $arrayOptions );
			$arrayElements[] = array("name" => "Invert_1", "type" => "CheckBox",  "caption" => "Invert (1)");
		}
		
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "caption" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/';");
	
		$arrayActions = array();
		If (($this->ReadPropertyString("DeviceAddress") <> "Sensorauswahl") AND ($this->ReadPropertyBoolean("Open") == true)) {
			$arrayActions[] = array("type" => "Button", "label" => "An (0)", "onClick" => 'GeCoSDS2413_SetPortStatus($id, 0, true);');
			$arrayActions[] = array("type" => "Button", "label" => "Aus (0)", "onClick" => 'GeCoSDS2413_SetPortStatus($id, 0, false);');
			$arrayActions[] = array("type" => "Button", "label" => "An (1)", "onClick" => 'GeCoSDS2413_SetPortStatus($id, 1, true);');
			$arrayActions[] = array("type" => "Button", "label" => "Aus (1)", "onClick" => 'GeCoSDS2413_SetPortStatus($id, 1, false);');
		}
		else {
			$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		}
	
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}           
	  
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Summary setzen
		$this->SetSummary("SC: ".$this->ReadPropertyString("DeviceAddress"));
		
		$OWDeviceArray = Array();
		$this->SetBuffer("OWDeviceArray", serialize($OWDeviceArray));
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {			
			If ($this->ReadPropertyBoolean("Open") == true) {	
				//ReceiveData-Filter setzen
				$Filter = '(.*"Function":"get_start_trigger".*|.*"InstanceID":'.$this->InstanceID.'.*)';
				//$this->SetReceiveDataFilter($Filter);
				
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_OWDevices", "DeviceSerial" => $this->ReadPropertyString("DeviceAddress"), "InstanceID" => $this->InstanceID)));		
				If ($Result == true) {
					If (($this->ReadPropertyInteger("DeviceFunction_0") == 1) OR ($this->ReadPropertyInteger("DeviceFunction_1") == 1)) {
						$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					}
					else {
						$this->SetTimerInterval("Messzyklus", 0);
					}
					$this->Setup();
					$this->Measurement();
					$this->SetStatus(102);
					$this->SendDebug("ApplyChanges", $this->ReadPropertyString("DeviceAddress")." ".$this->ReadPropertyInteger("DeviceAddress_0")." ".$this->ReadPropertyInteger("DeviceAddress_1"), 0);
				}
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
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
			case "status":
			   	If ($data->InstanceID == $this->InstanceID) {
				   	If ($this->ReadPropertyBoolean("Open") == true) {				
						$this->SendDebug("ReceiveData", "Statusänderung: ".$data->Status, 0);
						$this->SetStatus($data->Status);
					}
					else {
						$this->SetStatus(104);
					}	
			   	}
			   	break;
			case "get_start_trigger":
			   	$this->ApplyChanges();
				break;
			case "OWV":
			   	If ($data->DeviceAddress == $this->ReadPropertyString("DeviceAddress")) {
					//SetValueFloat($this->GetIDForIdent("Temperature"), $data->Value);
			   		$this->SetStatus(102);
				}
			   	break;	
	 	}
 	}
	 
	public function RequestAction($Ident, $Value) 
	{
		$Port = intval(substr($Ident, 7, 2));
		$this->SetPortStatus($Port, $Value);
	}
	    
	// Beginn der Funktionen
	private function Setup()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ReadPropertyString("DeviceAddress") <> "Sensorauswahl")) {
			$Result = ($this->ReadPropertyInteger("DeviceFunction_1") << 1) | $this->ReadPropertyInteger("DeviceFunction_0")| 252;
			$this->SendDebug("Setup", "Wert: ".$Result, 0);
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_DS2413Setup", "Setup" => $Result, "InstanceID" => $this->InstanceID, "DeviceAddress_0" => $this->ReadPropertyInteger("DeviceAddress_0"), "DeviceAddress_1" => $this->ReadPropertyInteger("DeviceAddress_1"))));
		}
	}
	    
	public function Measurement()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ReadPropertyString("DeviceAddress") <> "Sensorauswahl")) {
			// Messung ausführen
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "get_DS2413State", "InstanceID" => $this->InstanceID, "DeviceAddress_0" => $this->ReadPropertyInteger("DeviceAddress_0"), "DeviceAddress_1" => $this->ReadPropertyInteger("DeviceAddress_1"))));
		}
	}
	
	public function SetPortStatus(int $Port, bool $Value)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ReadPropertyString("DeviceAddress") <> "Sensorauswahl")) {
			$this->SendDebug("SetPortStatus", "Port: ".(int)$Port." Value: ".(int)$Value, 0);
			// Eingabeparameter filtern
			$Port = min(1, max(0, $Port));
			$Value = min(1, max(0, $Value));
			// zu sendenden Wert ggf. invertieren
			$arrayValues = array(); 
			$arrayValues[(int)$Port] = $Value ^ $this->ReadPropertyBoolean("Invert_".((int)$Port));
			$arrayValues[(int)!$Port] = GetValueBoolean($this->GetIDForIdent("Status_".((int)!$Port))) ^ $this->ReadPropertyBoolean("Invert_".((int)$Port));
			$Result = ($arrayValues[1] << 1) | $arrayValues[0]| 252;
			//$this->SendDebug("SetPortStatus", "Port: ".(int)$Port." Value: ".(int)$Value, 0);
			$this->SendDebug("SetPortStatus", "Wert: ".$Result, 0);
			$this->SendDebug("SetPortStatus", "Port[0]: ".$arrayValues[0]." Port[1]: ".$arrayValues[1], 0);
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_DS2413Setup", "Setup" => $Result, "InstanceID" => $this->InstanceID, "DeviceAddress_0" => $this->ReadPropertyInteger("DeviceAddress_0"), "DeviceAddress_1" => $this->ReadPropertyInteger("DeviceAddress_1"))));
			// Messung ausführen
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "get_DS2413State", "InstanceID" => $this->InstanceID, "DeviceAddress_0" => $this->ReadPropertyInteger("DeviceAddress_0"), "DeviceAddress_1" => $this->ReadPropertyInteger("DeviceAddress_1"))));
		}
	}    
	 
}
?>
