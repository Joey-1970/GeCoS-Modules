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
		$this->RegisterPropertyString("IPAddress", "127.0.0.1");
		$this->RegisterPropertyInteger("Timer_1", 60);
		$this->RegisterTimer("Timer_1", 0, 'GeCoSWSens_RequestData($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("Altitude", 0);
 	   	$this->RegisterPropertyBoolean("LoggingTemp", false);
 	    	$this->RegisterPropertyBoolean("LoggingHum", false);
 	    	$this->RegisterPropertyBoolean("LoggingPres", false);
		$this->RegisterPropertyBoolean("LoggingAirQuality", false);
		$this->RegisterPropertyBoolean("LoggingAmbient", false);
		$this->RegisterPropertyInteger("Temperature_ID", 0);
		$this->RegisterPropertyInteger("Humidity_ID", 0);
		
		// Profile erstellen		
		$this->RegisterProfileFloat("GeCoS.gm3", "Drops", "", " g/m³", 0, 1000, 0.1, 1);
		$this->RegisterProfileInteger("GeCoS.ppm", "Gauge", "", " ppm", 0, 6000, 1);
		
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
				//Status-Variablen anlegen
		$this->RegisterVariableFloat("Hardware", "Hardware-Version", "", 10);
		
		$this->RegisterVariableFloat("Firmware", "Firmware-Version", "", 20);
		
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 30);
		
		$this->RegisterVariableFloat("Pressure", "Luftdruck (abs)", "~AirPressure.F", 40);
		
		$this->RegisterVariableFloat("PressureRel", "Luftdruck (rel)", "~AirPressure.F", 50);
		
		$this->RegisterVariableFloat("HumidityAbs", "Luftfeuchtigkeit (abs)", "GeCoS.gm3", 60);
		
		$this->RegisterVariableFloat("Humidity", "Luftfeuchtigkeit (rel)", "~Humidity.F", 70);
		
		$this->RegisterVariableFloat("DewPointTemperature", "Taupunkt Temperatur", "~Temperature", 80);
		
		$this->RegisterVariableFloat("PressureTrend1h", "Luftdruck 1h-Trend", "~AirPressure.F", 90);
		
		$this->RegisterVariableFloat("PressureTrend3h", "Luftdruck 3h-Trend", "~AirPressure.F", 100);
		
		$this->RegisterVariableFloat("PressureTrend12h", "Luftdruck 12h-Trend", "~AirPressure.F", 110);
		
		$this->RegisterVariableFloat("PressureTrend24h", "Luftdruck 24h-Trend", "~AirPressure.F", 120);
		
		$this->RegisterVariableInteger("AirQuality", "Luftqualität", "GeCoS.AirQuality", 130);
		SetValueInteger($this->GetIDForIdent("AirQuality"), 0);
		
		$this->RegisterVariableInteger("AirQualityIndex", "Luftqualität Index", "", 140);
		
		$this->RegisterVariableInteger("AirQualityGenauigkeit", "Luftqualität Genauigkeit", "", 142);
		
		$this->RegisterVariableInteger("CO2", "CO 2", "GeCoS.ppm", 144);
		
		$this->RegisterVariableInteger("CO2Genauigkeit", "CO 2 Genauigkeit", "", 146);
		
		$this->RegisterVariableInteger("Intensity_W", "Intensität Weiß", "GeCoS.Lux", 150);
		
		$this->RegisterVariableInteger("Intensity_R", "Intensität Rot", "GeCoS.Lux", 160);
		
		$this->RegisterVariableInteger("Intensity_G", "Intensität Grün", "GeCoS.Lux", 170);
		
		$this->RegisterVariableInteger("Intensity_B", "Intensität Blau", "GeCoS.Lux", 180);
		
		$this->RegisterVariableInteger("FailureCode", "Fehlercode", "", 190);
		SetValueInteger($this->GetIDForIdent("FailureCode"), 0);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Device konnte nicht gefunden werden");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
		$arrayElements[] = array("type" => "Label", "label" => "IP oder Hostname");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "IPAddress", "caption" => "IP");
		$arrayElements[] = array("type" => "Label", "label" => "Miniumum 5 Sekunden, 0 => Aus");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Timer_1", "caption" => "Sekunden");
 		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Korrektur des Luftdrucks nach Hohenangabe");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Altitude", "caption" => "Höhe über NN (m)");
		$arrayElements[] = array("type" => "Label", "label" => "Optionale Angabe von externen Quellen");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "Temperature_ID", "caption" => "Temperatur");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "Humidity_ID", "caption" => "Luftfeuchtigkeit");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingTemp", "caption" => "Logging Temperatur aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingHum", "caption" => "Logging Luftfeuchtigkeit aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingPres", "caption" => "Logging Luftdruck aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingAirQuality", "caption" => "Logging Luftqualität aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingAmbient", "caption" => "Logging Weiß-Wert aktivieren");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "caption" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/'");
	
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
		AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Intensity_W"), $this->ReadPropertyBoolean("LoggingAmbient"));
		IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);
		
		// Summary setzen
		$this->SetSummary($this->ReadPropertyString('IPAddress'));
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Timer_1 = $this->ReadPropertyInteger("Timer_1");
			If (($Timer_1 > 0) AND ($Timer_1 < 5)) {
				$Timer_1 = 5;
			}
			$this->SetTimerInterval("Timer_1", ($Timer_1 * 1000));
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
		If ($this->ReadPropertyBoolean("Open") == true)  {
			// Datenermittlung über JSON
			$IP = $this->ReadPropertyString("IPAddress");
			$contents = @file_get_contents('http://'.$IP.'/json');
			If ($contents === false) {
				$this->SendDebug("RequestData", "Fehler bei der Datenermittlung!", 0);		
				$this->SetStatus(202);
				return false;
			}
			
			$this->SetStatus(102);
			$contents = utf8_encode($contents); 
			$data = json_decode($contents);
			$Temp = floatval($data->Temperatur);
			$Pressure = floatval($data->Luftdruck); 
			$Humidity = floatval($data->Luftfeuchtigkeit); 
			$IAQ = floatval($data->{'Luftqualitaet-Index'}); 
			
			$Hardware = floatval($data->{'Hardware-Version'});
			$Firmware = floatval($data->{'Firmware-Version'});
			If (GetValueFloat($this->GetIDForIdent("Hardware")) <> $Hardware) {
				SetValueFloat($this->GetIDForIdent("Hardware"), ($Hardware));
				$this->SetSummary("HW-Version: ".$Hardware." SW-Version: ".$Firmware);
			}
			If (GetValueFloat($this->GetIDForIdent("Firmware")) <> $Firmware) {
				SetValueFloat($this->GetIDForIdent("Firmware"), ($Firmware));
				$this->SetSummary("HW-Version: ".$Hardware." SW-Version: ".$Firmware);
			}
			if (isset($data->{'BME-Error'})) {
				$FailureCode = intval($data->{'BME-Error'});
			}
			else {
				$FailureCode = 0;
			}
			If (GetValueInteger($this->GetIDForIdent("FailureCode")) <> $FailureCode) {
				SetValueInteger($this->GetIDForIdent("FailureCode"), ($FailureCode));
				
			}
			
			If (property_exists($data, "Luftqualitaet-Genauigkeit")) {
				SetValueInteger($this->GetIDForIdent("AirQualityGenauigkeit"), intval($data->Luftqualitaet-Genauigkeit));
        		}  
			
			If (property_exists($data, "CO2")) {
				SetValueInteger($this->GetIDForIdent("CO2"), intval($data->CO2));
        		}  
			
			If (property_exists($data, "CO2-Genauigkeit")) {
				SetValueInteger($this->GetIDForIdent("CO2Genauigkeit"), intval($data->CO2-Genauigkeit));
        		}  
			
			$Ambient = intval($data->{'Intensitaet-Weiss'});
    			$Red = intval($data->{'Intensitaet-Rot'});
			$Green = intval($data->{'Intensitaet-Gruen'});
			$Blue = intval($data->{'Intensitaet-Blau'});
			
			$this->SendDebug("RequestData", "BME680 - iAQ: ".$IAQ." Temp: ".$Temp." C Luftfeuchte: ".$Humidity."% Luftdruck: ".$Pressure." hPa", 0);		
			$this->SendDebug("RequestData", "APDS9960 - Weiss: ".$Ambient." lx Rot: ".$Red." lx Gruen: ".$Green." lx Blau: ".$Blue." lx", 0);
			
			SetValueInteger($this->GetIDForIdent("Intensity_W"), $Ambient);
			SetValueInteger($this->GetIDForIdent("Intensity_R"), $Red);
			SetValueInteger($this->GetIDForIdent("Intensity_G"), $Green);
			SetValueInteger($this->GetIDForIdent("Intensity_B"), $Blue);
			
			SetValueFloat($this->GetIDForIdent("Temperature"), round($Temp, 2));
			
			If (($Pressure > 800) AND ($Pressure < 1200)) {
				SetValueFloat($this->GetIDForIdent("Pressure"), round($Pressure, 2));
			}
			
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
				$VaribleID = $this->ReadPropertyInteger("Temperature_ID");
				$VariableType = IPS_GetVariable($VaribleID)['VariableType'];
				If ($VariableType == 1) {
					$Temperature = GetValueInteger($VaribleID);
				}
				elseif ($VariableType == 2) {
					$Temperature = GetValueFloat($VaribleID);
				}
				else {
					// Wert dieses BME680 verwenden
					$Temperature = $Temp;
				}
			}
			else {
				// Wert dieses BME680 verwenden
				$Temperature = $Temp;
			}
					
			If ($this->ReadPropertyInteger("Humidity_ID") > 0) {
				// Wert der Variablen zur Berechnung nutzen
				$VaribleID = $this->ReadPropertyInteger("Humidity_ID");
				$VariableType = IPS_GetVariable($VaribleID)['VariableType'];
				If ($VariableType == 1) {
					$Humidity = GetValueInteger($VaribleID);
				}
				elseif ($VariableType == 2) {
					$Humidity = GetValueFloat($VaribleID);
				}
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
			return true;
		}
		else {
			return false;
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
}
?>
