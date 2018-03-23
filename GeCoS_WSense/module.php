<?
    // Klassendefinition
    class GeCoS_WSense extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{A5F663AB-C400-4FE5-B207-4D67CC030564}");
 	   
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
            	
		$this->RegisterProfileFloat("GeCoS.gm3", "Drops", "", " g/m³", 0, 1000, 0.1, 1);
		
		$this->RegisterProfileInteger("GeCoS.AirQuality", "Information", "", "", 0, 6, 1);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 0, "Kalibrierung", "Information", -1);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 1, "gut", "Information", 0x58FA58);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 2, "durchschnittlich", "Information", 0xF7FE2E);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 3, "etwas schlecht", "Information", 0xFE9A2E);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 4, "schlecht", "Information", 0xFF0000);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 5, "schlechter", "Information", 0x61380B);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 6, "sehr schlecht", "Information", 0x000000);
		
		//Status-Variablen anlegen
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 10);
		$this->DisableAction("Temperature");
		IPS_SetHidden($this->GetIDForIdent("Temperature"), false);
		
		$this->RegisterVariableFloat("Pressure", "Luftdruck (abs)", "~AirPressure.F", 20);
		$this->DisableAction("Pressure");
		IPS_SetHidden($this->GetIDForIdent("Pressure"), false);
		
		$this->RegisterVariableFloat("PressureRel", "Luftdruck (rel)", "~AirPressure.F", 30);
		$this->DisableAction("PressureRel");
		IPS_SetHidden($this->GetIDForIdent("PressureRel"), false);
		
		$this->RegisterVariableFloat("HumidityAbs", "Luftfeuchtigkeit (abs)", "IPS2GPIO.gm3", 40);
		$this->DisableAction("HumidityAbs");
		IPS_SetHidden($this->GetIDForIdent("HumidityAbs"), false);
		
		$this->RegisterVariableFloat("Humidity", "Luftfeuchtigkeit (rel)", "~Humidity.F", 50);
		$this->DisableAction("Humidity");
		IPS_SetHidden($this->GetIDForIdent("Humidity"), false);
		
		$this->RegisterVariableFloat("DewPointTemperature", "Taupunkt Temperatur", "~Temperature", 60);
		$this->DisableAction("DewPointTemperature");
		IPS_SetHidden($this->GetIDForIdent("DewPointTemperature"), false);
		
		$this->RegisterVariableFloat("PressureTrend1h", "Luftdruck 1h-Trend", "~AirPressure.F", 70);
		$this->DisableAction("PressureTrend1h");
		IPS_SetHidden($this->GetIDForIdent("PressureTrend1h"), false);
		SetValueFloat($this->GetIDForIdent("PressureTrend1h"), 0);
		
		$this->RegisterVariableFloat("PressureTrend3h", "Luftdruck 3h-Trend", "~AirPressure.F", 80);
		$this->DisableAction("PressureTrend3h");
		IPS_SetHidden($this->GetIDForIdent("PressureTrend3h"), false);
		SetValueFloat($this->GetIDForIdent("PressureTrend3h"), 0);
		
		$this->RegisterVariableFloat("PressureTrend12h", "Luftdruck 12h-Trend", "~AirPressure.F", 90);
		$this->DisableAction("PressureTrend12h");
		IPS_SetHidden($this->GetIDForIdent("PressureTrend12h"), false);
		SetValueFloat($this->GetIDForIdent("PressureTrend12h"), 0);
		
		$this->RegisterVariableFloat("PressureTrend24h", "Luftdruck 24h-Trend", "~AirPressure.F", 100);
		$this->DisableAction("PressureTrend24h");
		IPS_SetHidden($this->GetIDForIdent("PressureTrend24h"), false);
		SetValueFloat($this->GetIDForIdent("PressureTrend24h"), 0);
		
		$this->RegisterVariableInteger("AirQuality", "Luftqualität", "IPS2GPIO.AirQuality", 110);
		$this->DisableAction("AirQuality");
		IPS_SetHidden($this->GetIDForIdent("AirQuality"), false);
		SetValueInteger($this->GetIDForIdent("AirQuality"), 0);

		$this->RegisterVariableInteger("Intensity_W", "Intensität Weiß", "~Intensity.255", 120);
	        $this->DisableAction("Intensity_W");
		IPS_SetHidden($this->GetIDForIdent("Intensity_W"), false);
		
		$this->RegisterVariableInteger("Intensity_R", "Intensität Rot", "~Intensity.255", 130);
	        $this->DisableAction("Intensity_R");
		IPS_SetHidden($this->GetIDForIdent("Intensity_R"), false);
		
		$this->RegisterVariableInteger("Intensity_G", "Intensität Grün", "~Intensity.255", 140);
	        $this->DisableAction("Intensity_G");
		IPS_SetHidden($this->GetIDForIdent("Intensity_G"), false);
		
		$this->RegisterVariableInteger("Intensity_B", "Intensität Blau", "~Intensity.255", 150);
	        $this->DisableAction("Intensity_B");
		IPS_SetHidden($this->GetIDForIdent("Intensity_B"), false);
		
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {			
			If ($this->ReadPropertyBoolean("Open") == true) {	
				//ReceiveData-Filter setzen
				
				
				$this->SetStatus(102);
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
	    	$this->SendDebug("ReceiveData", "Ausfuehrung", 0);
		$data = json_decode($JSONString);
	 	$this->SendDebug("ReceiveData", $JSONString, 0);
	
 	}
	
	public function RequestData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Temperatur ermitteln
			$Temp = GetData(3, 120, 1);
			if($Temp === false) {
				return;
			}
			$Temp = (unpack("n*", substr($Temp,2)));
			
			// Luftfeuchtigkeit ermitteln
			$Humidity = GetData(3, 121, 1);
			if($Humidity === false) {
				return;
			}
			$Humidity = (unpack("n*", substr($Humidity,2)));
			
			// Luftdruck ermitteln
			$Pressure = GetData(3, 122, 1);
			if($Pressure === false) {
				return;
			}
			$Pressure = (unpack("n*", substr($Pressure,2)));
			
			// Luftdruck ermitteln
			$IAQ = GetData(3, 123, 1);
			if($IAQ === false) {
				return;
			}
			$IAQ = (unpack("n*", substr($IAQ,2)));
			
			// Weißwert ermitteln
			$Ambient = GetData(3, 125, 1);
			if($Ambient === false) {
				return;
			}
			$Ambient = (unpack("n*", substr($Ambient,2)));
			
			// Rotwert ermitteln
			$Red = GetData(3, 126, 1);
			if($Red === false) {
				return;
			}
			$Red = (unpack("n*", substr($Red,2)));
			
			// Grünwert ermitteln
			$Green = GetData(3, 127, 1);
			if($Green === false) {
				return;
			}
			$Green = (unpack("n*", substr($Green,2)));
			
			// Blauwert ermitteln
			$Blue = GetData(3, 128, 1);
			if($Blue === false) {
				return;
			}
			$Blue = (unpack("n*", substr($Blue,2)));
		}
	}
	    
	public function GetData(Int $Function, Int $Address, Int $Quantity)
	{
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetData", "Ausfuehrung - Funktion: ".$Function." Adresse: ".$Address." Menge: ".$Quantity, 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => $Function, "Address" => $Address, "Quantity" => $Quantity, "Data" => "")));
			$this->SendDebug("GetData", $Result, 0);
			$Result = (unpack("n*", substr($Result,2)));
			$this->SendDebug("GetData", serialize($Result), 0);
		}
	}

	// Beginn der Funktionen
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
