<?
    // Klassendefinition
    class GeCoS_WSens extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Timer_1", 0);
	}
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->RequireParent("{A5F663AB-C400-4FE5-B207-4D67CC030564}");
		$this->RegisterPropertyInteger("Timer_1", 60);
		$this->RegisterTimer("Timer_1", 0, 'GeCoSWSens_RequestData($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("Altitude", 0);
		$this->RegisterPropertyFloat("TempOffset", 0);
 	   	$this->RegisterPropertyBoolean("LoggingTemp", false);
 	    	$this->RegisterPropertyBoolean("LoggingHum", false);
 	    	$this->RegisterPropertyBoolean("LoggingPres", false);
		$this->RegisterPropertyBoolean("LoggingAirQuality", false);
		$this->RegisterPropertyInteger("Temperature_ID", 0);
		$this->RegisterPropertyInteger("Humidity_ID", 0);
		
		// Profile erstellen		
		$this->RegisterProfileFloat("GeCoS.gm3", "Drops", "", " g/m³", 0, 1000, 0.1, 1);
		
		$this->RegisterProfileInteger("GeCoS.AirQuality", "Information", "", "", 0, 6, 1);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 0, "Kalibrierung", "Information", -1);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 1, "gut", "Information", 0x58FA58);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 2, "durchschnittlich", "Information", 0xF7FE2E);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 3, "etwas schlecht", "Information", 0xFE9A2E);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 4, "schlecht", "Information", 0xFF0000);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 5, "schlechter", "Information", 0x61380B);
		IPS_SetVariableProfileAssociation("GeCoS.AirQuality", 6, "sehr schlecht", "Information", 0x000000);
		
		$this->RegisterProfileInteger("GeCoS.Lux", "Bulb", "", " lx", 0, 65535, 1);
		
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
		
		$this->RegisterVariableFloat("HumidityAbs", "Luftfeuchtigkeit (abs)", "GeCoS.gm3", 40);
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
		
		$this->RegisterVariableInteger("AirQuality", "Luftqualität", "GeCoS.AirQuality", 110);
		$this->DisableAction("AirQuality");
		IPS_SetHidden($this->GetIDForIdent("AirQuality"), false);
		SetValueInteger($this->GetIDForIdent("AirQuality"), 0);
		
		$this->RegisterVariableInteger("AirQualityIndex", "Luftqualität Index", "", 120);
		$this->DisableAction("AirQualityIndex");
		IPS_SetHidden($this->GetIDForIdent("AirQualityIndex"), false);
		SetValueInteger($this->GetIDForIdent("AirQualityIndex"), 0);
		$this->RegisterVariableInteger("Intensity_W", "Intensität Weiß", "GeCoS.Lux", 130);
	        $this->DisableAction("Intensity_W");
		IPS_SetHidden($this->GetIDForIdent("Intensity_W"), false);
		
		$this->RegisterVariableInteger("Intensity_R", "Intensität Rot", "GeCoS.Lux", 140);
	        $this->DisableAction("Intensity_R");
		IPS_SetHidden($this->GetIDForIdent("Intensity_R"), false);
		
		$this->RegisterVariableInteger("Intensity_G", "Intensität Grün", "GeCoS.Lux", 150);
	        $this->DisableAction("Intensity_G");
		IPS_SetHidden($this->GetIDForIdent("Intensity_G"), false);
		
		$this->RegisterVariableInteger("Intensity_B", "Intensität Blau", "GeCoS.Lux", 160);
	        $this->DisableAction("Intensity_B");
		IPS_SetHidden($this->GetIDForIdent("Intensity_B"), false);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Device konnte nicht gefunden werden");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "ModBus-Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Timer_1", "caption" => "Sekunden");
 		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Korrektur der Temperatur");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "TempOffset", "caption" => "Kelvin", "digits" => 1);
		$arrayElements[] = array("type" => "Label", "label" => "Korrektur des Luftdrucks nach Hohenangabe");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Altitude", "caption" => "Höhe über NN (m)");
		$arrayElements[] = array("type" => "Label", "label" => "Optionale Angabe von Quellen");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "Temperature_ID", "caption" => "Temperatur (extern)");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "Humidity_ID", "caption" => "Luftfeuchtigkeit (extern)");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingTemp", "caption" => "Logging Temperatur aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingHum", "caption" => "Logging Luftfeuchtigkeit aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingPres", "caption" => "Logging Luftdruck aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingAirQuality", "caption" => "Logging Luftqualität aktivieren");
		
		
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
            			
		// Logging setzen
		AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Temperature"), $this->ReadPropertyBoolean("LoggingTemp"));
		AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Pressure"), $this->ReadPropertyBoolean("LoggingPres"));
		AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Humidity"), $this->ReadPropertyBoolean("LoggingHum"));
		AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("AirQuality"), $this->ReadPropertyBoolean("LoggingAirQuality"));
		IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);
		
		If ($this->ReadPropertyBoolean("Open") == true) {	
			$this->TempOffsetReset();
			$this->SetTimerInterval("Timer_1", ($this->ReadPropertyInteger("Timer_1") * 1000));
			$this->RequestData();
			$this->SetStatus(102);
		}
		else {
			$this->SetTimerInterval("Timer_1", 0);
			$this->SetStatus(104);
		}	
	}
	
	// Beginn der Funktionen
	public function RequestData()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->HasActiveParent() == true)) {
			// Temperatur ermitteln
			$Temp = $this->GetData(3, 120, 1);
			if($Temp === false) {
				$this->SetStatus(202);
				return;
			}
			
			// Luftfeuchtigkeit ermitteln
			$Humidity = $this->GetData(3, 121, 1);
			if($Humidity === false) {
				$this->SetStatus(202);
				return;
			}
			
			// Luftdruck ermitteln
			$Pressure = $this->GetData(3, 122, 1);
			if($Pressure === false) {
				$this->SetStatus(202);
				return;
			}
			
			
			
			// Weißwert ermitteln
			$Ambient = $this->GetData(3, 125, 1);
			if($Ambient === false) {
				$this->SetStatus(202);
				return;
			}
			
			// Rotwert ermitteln
			$Red = $this->GetData(3, 126, 1);
			if($Red === false) {
				$this->SetStatus(202);
				return;
			}
			
			// Grünwert ermitteln
			$Green = $this->GetData(3, 127, 1);
			if($Green === false) {
				$this->SetStatus(202);
				return;
			}
			
			// Blauwert ermitteln
			$Blue = $this->GetData(3, 128, 1);
			if($Blue === false) {
				$this->SetStatus(202);
				return;
			}
			
			// AQ ermitteln
			$IAQ = $this->GetData(3, 123, 1);
			if($IAQ === false) {
				$this->SetStatus(202);
				return;
			}
			$TempOffset = $this->ReadPropertyFloat("TempOffset");
			$this->SendDebug("RequestData", "BME680 - iAQ: ".$IAQ." TempOffset: ".$TempOffset." Temp: ".$Temp. " Luftfeuchte: ".$Humidity." Luftdruck: ".$Pressure, 0);
			$Temp = $Temp + $TempOffset;
			
			
			$this->SendDebug("RequestData", "APDS9960 - Weiss: ".$Ambient." Rot: ".$Red." Gruen: ".$Green." Blau: ".$Blue, 0);
			
			$this->SetStatus(102);
			
			SetValueInteger($this->GetIDForIdent("Intensity_W"), $Ambient);
			SetValueInteger($this->GetIDForIdent("Intensity_R"), $Red);
			SetValueInteger($this->GetIDForIdent("Intensity_G"), $Green);
			SetValueInteger($this->GetIDForIdent("Intensity_B"), $Blue);
			$Temp = $Temp / 100;
			SetValueFloat($this->GetIDForIdent("Temperature"), round($Temp, 2));
			If (($Pressure > 800) AND ($Pressure < 1200)) {
				SetValueFloat($this->GetIDForIdent("Pressure"), round($Pressure, 2));
			}
			$Humidity = $Humidity / 100;
			SetValueFloat($this->GetIDForIdent("Humidity"), round($Humidity, 2));
			SetValueInteger($this->GetIDForIdent("AirQualityIndex"), $IAQ);
			
			// Berechnung von Taupunkt und absoluter Luftfeuchtigkeit
			if ($Temp < 0) {
				$a = 7.6; 
				$b = 240.7;
			}  
			elseif ($Temp >= 0) {
				$a = 7.5;
				$b = 237.3;
			}
			$sdd = 6.1078 * pow(10.0, (($a * $Temp) / ($b + $Temp)));
			$dd = $Humidity / 100 * $sdd;
			$v = log10($dd/6.1078);
			$td = $b * $v / ($a - $v);
			$af = pow(10,5) * 18.016 / 8314.3 * $dd / ($Temp + 273.15);
			// Taupunkttemperatur
			SetValueFloat($this->GetIDForIdent("DewPointTemperature"), round($td, 2));
			// Absolute Feuchtigkeit
			SetValueFloat($this->GetIDForIdent("HumidityAbs"), round($af, 2));
			
			// Relativen Luftdruck
			$Altitude = $this->ReadPropertyInteger("Altitude");
			If ($this->ReadPropertyInteger("Temperature_ID") > 0) {
				// Wert der Variablen zur Berechnung nutzen
				$Temperature = GetValueInteger($this->ReadPropertyInteger("Temperature_ID"));
			}
			else {
				// Wert dieses BME680 verwenden
				$Temperature = $Temp;
			}
			If ($this->ReadPropertyInteger("Humidity_ID") > 0) {
				// Wert der Variablen zur Berechnung nutzen
				$Humidity = GetValueInteger($this->ReadPropertyInteger("Humidity_ID"));
			}
			
			$g_n = 9.80665; // Erdbeschleunigung (m/s^2)
			$gam = 0.0065; // Temperaturabnahme in K pro geopotentiellen Metern (K/gpm)
			$R = 287.06; // Gaskonstante für trockene Luft (R = R_0 / M)
			$M = 0.0289644; // Molare Masse trockener Luft (J/kgK)
			$R_0 = 8.314472; // allgemeine Gaskonstante (J/molK)
			$T_0 = 273.15; // Umrechnung von °C in K
			$C = 0.11; // DWD-Beiwert für die Berücksichtigung der Luftfeuchte
			$E_0 = 6.11213; // (hPa)
			$f_rel = $Humidity / 100; // relative Luftfeuchte (0-1.0)
			// momentaner Stationsdampfdruck (hPa)
			$e_d = $f_rel * $E_0 * exp((17.5043 * $Temperature) / (241.2 + $Temperature));
			$PressureRel = $Pressure * exp(($g_n * $Altitude) / ($R * ($Temperature + $T_0 + $C * $e_d + (($gam * $Altitude) / 2))));
			SetValueFloat($this->GetIDForIdent("PressureRel"), round($PressureRel, 2));
			// Luftdruck Trends
			If ($this->ReadPropertyBoolean("LoggingPres") == true) {
				SetValueFloat($this->GetIDForIdent("PressureTrend1h"), $this->PressureTrend(1));
				SetValueFloat($this->GetIDForIdent("PressureTrend3h"), $this->PressureTrend(3));
				SetValueFloat($this->GetIDForIdent("PressureTrend12h"), $this->PressureTrend(12));
				SetValueFloat($this->GetIDForIdent("PressureTrend24h"), $this->PressureTrend(24));
			}
			
			// Umrechnung für die Air-Qualität-Anzeige
			$air_quality_score = max(0, min(500, $IAQ));
			$IAQ_Index = array(50, 100, 150, 200, 300, 500);
			$i = 0;
			while ($air_quality_score > $IAQ_Index[$i]) {
			    $i++;  
			}
			SetValueInteger($this->GetIDForIdent("AirQuality"), ($i + 1));
		}
	}
	    
	public function GetData(Int $Function, Int $Address, Int $Quantity)
	{
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			//$this->SendDebug("GetData", "Ausfuehrung - Funktion: ".$Function." Adresse: ".$Address." Menge: ".$Quantity, 0);
			$Response = false;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => $Function, "Address" => $Address, "Quantity" => $Quantity, "Data" => "")));
			$Result = (unpack("n*", substr($Result,2)));
			If (is_array($Result)) {
				If (count($Result) == 1) {
					$Response = $Result[1];
				}
			}
			return $Response;	
		}
	}
	
	private function TempOffsetReset()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->HasActiveParent() == true)) {
			// TemperaturOffset ermitteln
			$TempOffset = $this->GetData(3, 101, 1);
			if($TempOffset === false) {
				$this->SetStatus(202);
				return;
			}
			elseif ($TempOffset <> 0) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{E310B701-4AE7-458E-B618-EC13A1A6F6A8}", "Function" => 16, "Address" => 101, "Quantity" => 1, "Data" => "\u0000\u0000")));
			}
		}
	}
	    
	private function PressureTrend(int $interval)
	{
		$Result = 0;
		$LoggingArray = AC_GetLoggedValues(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Pressure"), time()- (3600 * $interval), time(), 0); 
		$Result = @($LoggingArray[0]['Value'] - end($LoggingArray)['Value']); 
	return $Result;
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
		//$this->SendDebug("HasActiveParent", "Ausfuehrung", 0);
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
