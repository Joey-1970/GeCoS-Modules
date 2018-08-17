<?
    // Klassendefinition
    class GeCoS_DS2438 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
		$this->RegisterPropertyString("DeviceAddress", "Sensorauswahl");
		$this->RegisterPropertyInteger("DeviceAddress_0", 0);
		$this->RegisterPropertyInteger("DeviceAddress_1", 0);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'GeCoSDS2438_Measurement($_IPS["TARGET"]);');
		
		//Status-Variablen anlegen
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 10);
          	$this->DisableAction("Temperature");
		
		$this->RegisterVariableFloat("VAD", "VAD", "", 20);
          	$this->DisableAction("VAD");
		
		$this->RegisterVariableFloat("VDD", "VDD", "", 30);
          	$this->DisableAction("VDD");
		
		$this->RegisterVariableFloat("XSENS", "XSENS", "", 40);
          	$this->DisableAction("XSENS");
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
		
		// Hier mus der Abruf der DS1820 erfolgen
		$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "get_OWDevices", "FamilyCode" => "26", "InstanceID" => $this->InstanceID)));
		$OWDeviceArray = Array();
		$OWDeviceArray = unserialize($this->GetBuffer("OWDeviceArray"));
		If ($this->ReadPropertyString("DeviceAddress") == "Sensorauswahl") {
			$arrayValues = Array();
			$arrayValues[] = array("name" => "DeviceAddress", "value" => "Sensorauswahl");
			$arrayValues[] = array("name" => "DeviceAddress_0", "value" => 0);
			$arrayValues[] = array("name" => "DeviceAddress_1", "value" => 0);
			$arrayOptions[] = array("label" => "Sensorauswahl", "value" => $arrayValues);
		}
		else {
			$arrayValues = Array();
			$arrayValues[] = array("name" => "DeviceAddress", "value" => $this->ReadPropertyString("DeviceAddress"));
			$arrayValues[] = array("name" => "DeviceAddress_0", "value" => $this->ReadPropertyInteger("DeviceAddress_0"));
			$arrayValues[] = array("name" => "DeviceAddress_1", "value" => $this->ReadPropertyInteger("DeviceAddress_1"));
			$arrayOptions[] = array("label" => $this->ReadPropertyString("DeviceAddress"), "value" => $arrayValues);
		}
		If (count($OWDeviceArray ,COUNT_RECURSIVE) >= 3) {
			for ($i = 0; $i < Count($OWDeviceArray); $i++) {
				$arrayValues = Array();
				$arrayValues[] = array("name" => "DeviceAddress", "value" => $OWDeviceArray[$i][0]);
				$arrayValues[] = array("name" => "DeviceAddress_0", "value" => $OWDeviceArray[$i][1]);
				$arrayValues[] = array("name" => "DeviceAddress_1", "value" => $OWDeviceArray[$i][2]);
				$arrayOptions[] = array("label" => $OWDeviceArray[$i][0], "value" => $arrayValues);
			}
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceSerial", "caption" => "Geräte-ID", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "label" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/'");
	
		$arrayActions = array();
		If (($this->ReadPropertyString("DeviceAddress") <> "Sensorauswahl") AND ($this->ReadPropertyBoolean("Open") == true)) {
			$arrayActions[] = array("type" => "Label", "label" => "Aktuell sind keine Testfunktionen definiert");
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
				$this->SetReceiveDataFilter($Filter);
				
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_OWDevices", "DeviceSerial" => $this->ReadPropertyString("DeviceAddress"), "InstanceID" => $this->InstanceID)));		
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					//$this->Setup();
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
			case "set_OWDevices":
			   	If ($data->InstanceID == $this->InstanceID) {
					$this->SetBuffer("OWDeviceArray", $data->Result);
					$this->SendDebug("ReceiveData", $data->Result, 0);
			   	}
			   	break;
			case "set_DS2438":
			   	If ($data->InstanceID == $this->InstanceID) {
					$this->SendDebug("ReceiveData", "Temperatur: ".$data->Temperature, 0);
					SetValueFloat($this->GetIDForIdent("Temperature"), $data->Temperature);
					$this->SendDebug("ReceiveData", "Volt: ".$data->Voltage_VAD, 0);
					SetValueFloat($this->GetIDForIdent("VAD"), $data->Voltage_VAD);
					$this->SendDebug("ReceiveData", "Volt: ".$data->Voltage_VDD, 0);
					SetValueFloat($this->GetIDForIdent("VDD"), $data->Voltage_VDD);
					$this->SendDebug("ReceiveData", "Strom: ".$data->Current, 0);
					SetValueFloat($this->GetIDForIdent("XSENS"), $data->Current);
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
			/*
			$Result = ($this->ReadPropertyInteger("DeviceFunction_1") << 1) | $this->ReadPropertyInteger("DeviceFunction_0")| 252;
			$this->SendDebug("Setup", "Wert: ".$Result, 0);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_DS2413Setup", "Setup" => $Result, "InstanceID" => $this->InstanceID, "DeviceAddress_0" => $this->ReadPropertyInteger("DeviceAddress_0"), "DeviceAddress_1" => $this->ReadPropertyInteger("DeviceAddress_1"))));
			*/
		}
	}
	    
	public function Measurement()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ReadPropertyString("DeviceAddress") <> "Sensorauswahl")) {
			// Messung ausführen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "get_DS2438Measurement", "InstanceID" => $this->InstanceID, "DeviceAddress_0" => $this->ReadPropertyInteger("DeviceAddress_0"), "DeviceAddress_1" => $this->ReadPropertyInteger("DeviceAddress_1"))));
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
