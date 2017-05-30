<?
    // Klassendefinition
    class GeCoS_DS18B20 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
 	    	$this->RegisterPropertyString("DeviceSerial", "Sensorauswahl");
		$this->RegisterPropertyString("DeviceAddress", "Sensorauswahl");
		$this->RegisterPropertyInteger("DeviceAddress_0", 0);
		$this->RegisterPropertyInteger("DeviceAddress_1", 0);
		$this->RegisterPropertyInteger("Resolution", 0);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'GeCoSDS18B20_Measurement($_IPS["TARGET"]);');
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
		$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "get_OWDevices", "FamilyCode" => "28", "InstanceID" => $this->InstanceID)));
		$OWDeviceArray = Array();
		$OWDeviceArray = unserialize($this->GetBuffer("OWDeviceArray"));
		If ($this->ReadPropertyString("DeviceSerial") == "Sensorauswahl") {
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
			$arrayOptions[] = array("label" => $this->ReadPropertyString("DeviceSerial"), "value" => $arrayValues);
		}
		If (count($OWDeviceArray ,COUNT_RECURSIVE) >= 3) {
		//If (count($OWDeviceArray) > 0) {
			for ($i = 0; $i < Count($OWDeviceArray); $i++) {
				//$arrayOptions[] = array("label" => $OWDeviceArray[$i], "value" => $OWDeviceArray[$i]);
				$arrayValues = Array();
				$arrayValues[] = array("name" => "DeviceAddress", "value" => $OWDeviceArray[$i][0]);
				$arrayValues[] = array("name" => "DeviceAddress_0", "value" => $OWDeviceArray[$i][1]);
				$arrayValues[] = array("name" => "DeviceAddress_1", "value" => $OWDeviceArray[$i][2]);
				$arrayOptions[] = array("label" => $OWDeviceArray[$i][0], "value" => $arrayValues);
			}
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceSerial", "caption" => "Geräte-ID", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "9-Bit", "value" => 0);
		$arrayOptions[] = array("label" => "10-Bit", "value" => 1);
		$arrayOptions[] = array("label" => "11-Bit", "value" => 2);
		$arrayOptions[] = array("label" => "12-Bit", "value" => 3);
		If ($this->ReadPropertyString("DeviceSerial") <> "Sensorauswahl") {
			$arrayElements[] = array("type" => "Select", "name" => "Resolution", "caption" => "Präzision", "options" => $arrayOptions );
		}
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Messzyklus (sek)");
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
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 10);
          	$this->DisableAction("Temperature");
		IPS_SetHidden($this->GetIDForIdent("Temperature"), false);
		
		$OWDeviceArray = Array();
		$this->SetBuffer("OWDeviceArray", serialize($OWDeviceArray));
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {			
			If ($this->ReadPropertyBoolean("Open") == true) {	
				//ReceiveData-Filter setzen
				$Filter = '(.*"Function":"status".*|.*"InstanceID":'.$this->InstanceID.'.*)';
				$this->SetReceiveDataFilter($Filter);
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_OWDevices", "DeviceSerial" => $this->ReadPropertyString("DeviceSerial"), "InstanceID" => $this->InstanceID)));		
				$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
				$this->Setup();
				$this->Measurement();
				$this->SetStatus(102);
				$this->SendDebug("ApplyChanges", $this->ReadPropertyString("DeviceSerial")." ".$this->ReadPropertyString("DeviceAddress")." ".$this->ReadPropertyInteger("DeviceAddress_0")." ".$this->ReadPropertyInteger("DeviceAddress_1"), 0);
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
			case "set_DS18B20Temperature":
			   	If ($data->InstanceID == $this->InstanceID) {
					SetValueFloat($this->GetIDForIdent("Temperature"), $data->Result);
			   	}
			   	break;	
	 	}
 	}
	    
	// Beginn der Funktionen
	private function Setup()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ReadPropertyString("DeviceSerial") <> "Sensorauswahl")) {
			$Resolution = array( 31, 63, 95, 127);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_DS18B20Setup", "Resolution" => $Resolution[$this->ReadPropertyInteger("Resolution")], "InstanceID" => $this->InstanceID, "DeviceAddress_0" => $this->ReadPropertyInteger("DeviceAddress_0"), "DeviceAddress_1" => $this->ReadPropertyInteger("DeviceAddress_1"))));
		}
	}
	    
	public function Measurement()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ReadPropertyString("DeviceSerial") <> "Sensorauswahl")) {
			$Time = array( 95, 190, 380, 750);
			// Messung ausführen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "get_DS18B20Temperature", "Time" => $Time[$this->ReadPropertyInteger("Resolution")], "InstanceID" => $this->InstanceID, "DeviceAddress_0" => $this->ReadPropertyInteger("DeviceAddress_0"), "DeviceAddress_1" => $this->ReadPropertyInteger("DeviceAddress_1"))));
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
