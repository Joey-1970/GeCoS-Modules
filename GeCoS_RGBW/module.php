<?
    // Klassendefinition
    class GeCoS_RGBW extends IPSModule 
    {
	// PCA9685
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F1C0403-4A74-4F14-829F-9A217CFB2D05}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 88);
		$this->RegisterPropertyInteger("DeviceBus", 0);
		
		// Profil anlegen
		$this->RegisterProfileInteger("Intensity.4096", "Intensity", "", " %", 0, 4095, 1);
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 3; $i++) {
			$this->RegisterVariableBoolean("Status_RGB_".($i + 1), "Status RGB ".($i + 1), "~Switch", 10 + ($i * 70));
			$this->EnableAction("Status_RGB_".($i + 1));
			$this->RegisterVariableInteger("Color_RGB_".($i + 1), "Farbe ".($i + 1), "~HexColor", 20 + ($i * 70));
			$this->EnableAction("Color_RGB_".($i + 1));
			$this->RegisterVariableInteger("Intensity_R_".($i + 1), "Intensität Rot ".($i + 1), "Intensity.4096", 30 + ($i * 70) );
			$this->EnableAction("Intensity_R_".($i + 1));
			$this->RegisterVariableInteger("Intensity_G_".($i + 1), "Intensität Grün ".($i + 1), "Intensity.4096", 40 + ($i * 70));
			$this->EnableAction("Intensity_G_".($i + 1));
			$this->RegisterVariableInteger("Intensity_B_".($i + 1), "Intensität Blau ".($i + 1), "Intensity.4096", 50 + ($i * 70));
			$this->EnableAction("Intensity_B_".($i + 1));
			$this->RegisterVariableBoolean("Status_W_".($i + 1), "Status Weiß ".($i + 1), "~Switch", 60 + ($i * 70));
			$this->EnableAction("Status_W_".($i + 1));
			$this->RegisterVariableInteger("Intensity_W_".($i + 1), "Intensität Weiß ".($i + 1), "Intensity.4096", 70 + ($i * 70));
			$this->EnableAction("Intensity_W_".($i + 1));			
		}
		$this->RegisterVariableBoolean("Status_RGB_5", "Status RGB Alle", "~Switch", 290);
		$this->EnableAction("Status_RGB_5");
		$this->RegisterVariableInteger("Color_RGB_5", "Farbe Alle", "~HexColor", 300);
		$this->EnableAction("Color_RGB_5");
		$this->RegisterVariableInteger("Intensity_R_5", "Intensität Rot Alle", "Intensity.4096", 310);
		$this->EnableAction("Intensity_R_5");
		$this->RegisterVariableInteger("Intensity_G_5", "Intensität Grün Alle", "Intensity.4096", 320);
		$this->EnableAction("Intensity_G_5");
		$this->RegisterVariableInteger("Intensity_B_5", "Intensität Blau Alle", "Intensity.4096", 330);
		$this->EnableAction("Intensity_B_5");
		$this->RegisterVariableBoolean("Status_W_5", "Status Weiß Alle", "~Switch", 340);
		$this->EnableAction("Status_W_5");
		$this->RegisterVariableInteger("Intensity_W_5", "Intensität Weiß Alle", "Intensity.4096", 350);
		$this->EnableAction("Intensity_W_5");	
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
		for ($i = 88; $i <= 95; $i++) {
		    	$arrayOptions[] = array("label" => $i." / 0x".strtoupper(dechex($i))."", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 0", "value" => 0);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 1", "value" => 1);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 2", "value" => 2);
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "GeCoS I²C-Bus", "options" => $arrayOptions );
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
		$Parts = explode("_", $Ident);
		$Source = $Parts[0]."_".$Parts[1];
		$Group = $Parts[2];
		
		switch($Source) {
		case "Status_RGB":
			If ($Group <= 4) {
				$this->SetOutputPinStateRGB($Group, $Value);
			}
			elseif ($Group == 5) {
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputPinStateRGB($Group, $Value);
					SetValueBoolean($this->GetIDForIdent($Ident), $Value);
				}
			}
	            	break;
		case "Status_W":
			If ($Group <= 4) {
				$this->SetOutputPinStatusW($Group, $Value);
			}
			elseif ($Group == 5) {
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputPinStateW($Group, $Value);
					SetValueBoolean($this->GetIDForIdent($Ident), $Value);
				}
			}
	            	break;
		case "Intensity_R":
	            	If ($Group <= 4) {
				$this->SetOutputPinValueR($Group, $Value);
			}
			elseif ($Group == 5) {
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputPinValueR($i, $Channel, $Value);
					SetValueInteger($this->GetIDForIdent($Ident), $Value);
				}
			}
	            	break;
		case "Intensity_G":
	            	If ($Group <= 4) {
				$this->SetOutputPinValueG($Group, $Value);
			}
			elseif ($Group == 5) {
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputPinValueG($i, $Channel, $Value);
					SetValueInteger($this->GetIDForIdent($Ident), $Value);
				}
			}
	            	break;
		case "Intensity_B":
	            	If ($Group <= 4) {
				$this->SetOutputPinValueB($Group, $Value);
			}
			elseif ($Group == 5) {
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputPinValueB($i, $Channel, $Value);
					SetValueInteger($this->GetIDForIdent($Ident), $Value);
				}
			}
	            	break;
		case "Intensity_W":
	            	If ($Group <= 4) {
				$this->SetOutputPinValueW($Group, $Value);
			}
			elseif ($Group == 5) {
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputPinValueW($i, $Channel, $Value);
					SetValueInteger($this->GetIDForIdent($Ident), $Value);
				}
			}
	            	break;	
		case "Color_RGB":
	            	If ($Group <= 4) {
				$this->SetOutputColor($Group, $Value);
			}
			elseif ($Group == 5) {
				for ($i = 1; $i <= 4; $i++) {
					$this->SetOutputColor($i, $Value);
					SetValueInteger($this->GetIDForIdent($Ident), $Value);
				}
			}
	            	break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
		
	}
	    
	// Beginn der Funktionen
	public function SetOutput(Int $Group, Bool $StateRGB, Bool $StateW, Int $IntensityR, Int $IntensityG, Int $IntensityB, Int $IntensityW)  
	{
		//{RGBW;I2C-Kanal;Adresse;RGBWKanal;StatusRGB;StatusW;R;G;B;W}
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Ausgang setzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "RGBW", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "Group" => $Group, 
									    "StateRGB" => $StateRGB, "StateW" => $StateW, "IntensityR" => $IntensityR, "IntensityG" => $IntensityG, "IntensityB" => $IntensityB, "IntensityW" => $IntensityW )));
		}
	}
			
	public function SetOutputPinStateRGB(Int $Group, Bool $StateRGB)
	{ 
		$this->SendDebug("SetOutputPinStateRGB", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$State = min(1, max(0, $Status));
		$StateW = GetValueBoolean($this->GetIDForIdent("Status_W_".$Group));
		//$StatusRGB = GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group));
		$IntensityR = GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group));
		$IntensityG = GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group));
		$IntensityB = GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group));
		$IntensityW = GetValueInteger($this->GetIDForIdent("Intensity_W_".$Group));	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}    	    
	
	public function SetOutputPinStateW(Int $Group, Bool $StateW)
	{ 
		$this->SendDebug("SetOutputPinStateW", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$State = min(1, max(0, $State));
		//$StateW = GetValueBoolean($this->GetIDForIdent("Status_W_".$Group));
		$StateRGB = GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group));
		$IntensityR = GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group));
		$IntensityG = GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group));
		$IntensityB = GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group));
		$IntensityW = GetValueInteger($this->GetIDForIdent("Intensity_W_".$Group));	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}      
	 
	public function SetOutputPinValueR(Int $Group, Int $IntensityR)
	{ 
		$this->SendDebug("SetOutputPinValueR", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$IntensityR = min(4095, max(0, $State));
		$StateW = GetValueBoolean($this->GetIDForIdent("Status_W_".$Group));
		$StateRGB = GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group));
		//$IntensityR = GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group));
		$IntensityG = GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group));
		$IntensityB = GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group));
		$IntensityW = GetValueInteger($this->GetIDForIdent("Intensity_W_".$Group));	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}         
	
	public function SetOutputPinValueG(Int $Group, Int $IntensityG)
	{ 
		$this->SendDebug("SetOutputPinValueG", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$IntensityR = min(4095, max(0, $State));
		$StateW = GetValueBoolean($this->GetIDForIdent("Status_W_".$Group));
		$StateRGB = GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group));
		$IntensityR = GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group));
		//$IntensityG = GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group));
		$IntensityB = GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group));
		$IntensityW = GetValueInteger($this->GetIDForIdent("Intensity_W_".$Group));	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}            
	
	public function SetOutputPinValueB(Int $Group, Int $IntensityB)
	{ 
		$this->SendDebug("SetOutputPinValueB", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$IntensityR = min(4095, max(0, $State));
		$StateW = GetValueBoolean($this->GetIDForIdent("Status_W_".$Group));
		$StateRGB = GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group));
		$IntensityR = GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group));
		$IntensityG = GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group));
		//$IntensityB = GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group));
		$IntensityW = GetValueInteger($this->GetIDForIdent("Intensity_W_".$Group));	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}            
	
	public function SetOutputPinValueW(Int $Group, Int $IntensityW)
	{ 
		$this->SendDebug("SetOutputPinValueR", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$IntensityR = min(4095, max(0, $State));
		$StateW = GetValueBoolean($this->GetIDForIdent("Status_W_".$Group));
		$StateRGB = GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group));
		$IntensityR = GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group));
		$IntensityG = GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group));
		$IntensityB = GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group));
		//$IntensityW = GetValueInteger($this->GetIDForIdent("Intensity_W_".$Group));	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}            
	    
	public function SetOutputColor(Int $Group, Int $Color)
	{
		$this->SendDebug("SetOutputColor", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		
		// Farbwerte aufsplitten
		list($Value_R, $Value_G, $Value_B) = $this->Hex2RGB($Color);
		// Werte skalieren
		$IntensityR = 4095 / 255 * $Value_R;
		$IntensityG = 4095 / 255 * $Value_G;
		$IntensityB = 4095 / 255 * $Value_B;
		
		$StateW = GetValueBoolean($this->GetIDForIdent("Status_W_".$Group));
		$StateRGB = GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group));
		//$IntensityR = GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group));
		//$IntensityG = GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group));
		//$IntensityB = GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group));
		$IntensityW = GetValueInteger($this->GetIDForIdent("Intensity_W_".$Group));	
		
		$this->SetOutput($Group, $StateRGB, $StateW, $IntensityR, $IntensityG, $IntensityB, $IntensityW); 
	}
	    
	private function GetOutput(Int $Register)
	{
		$this->SendDebug("GetOutput", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "SRGBW")));
			If ($Result == true) {
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(202);
			}
		}
	}
	
	private function SetStatusVariables(Int $Register, Int $Value)
	{
		$ChannelArray = [0 => "R", 4 => "G", 8 => "B", 12=> "W"];
		$Intensity = $Value & 4095;
		$Status = !boolval($Value & 4096); 
		$Group = intval(($Register - 8) / 16) + 1;
		$Channel = ($Register - 8) - (($Group - 1) * 16);
		
		$this->SendDebug("SetStatusVariables", "Gruppe: ".$Group." Kanal: ".$ChannelArray[$Channel], 0);
		$this->SendDebug("SetStatusVariables", "Itensitaet: ".$Intensity." Status: ".(int)$Status, 0);
		
		
		If ($Intensity <> GetValueInteger($this->GetIDForIdent("Intensity_".$ChannelArray[$Channel]."_".$Group))) {
			SetValueInteger($this->GetIDForIdent("Intensity_".$ChannelArray[$Channel]."_".$Group), $Intensity);
		}
		If ($ChannelArray[$Channel] == "W") {
			If ($Status <> GetValueBoolean($this->GetIDForIdent("Status_W_".$Group))) {
				SetValueBoolean($this->GetIDForIdent("Status_W_".$Group), $Status);
			}
		}
		else {
			If ($Status <> GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group))) {
				SetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group), $Status);
			}
		}
		// Farbrad setzen
		$Value_R = intval(255 / 4095 * GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group)));
		$Value_G = intval(255 / 4095 * GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group)));
		$Value_B = intval(255 / 4095 * GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group)));
		SetValueInteger($this->GetIDForIdent("Color_RGB_".$Group), $this->RGB2Hex($Value_R, $Value_G, $Value_B));		
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
	    
	private function Hex2RGB($Hex)
	{
		$r = (($Hex >> 16) & 0xFF);
		$g = (($Hex >> 8) & 0xFF);
		$b = (($Hex >> 0) & 0xFF);	
	return array($r, $g, $b);
	}
	
	private function RGB2Hex($r, $g, $b)
	{
		$Hex = hexdec(str_pad(dechex($r), 2,'0', STR_PAD_LEFT).str_pad(dechex($g), 2,'0', STR_PAD_LEFT).str_pad(dechex($b), 2,'0', STR_PAD_LEFT));
	return $Hex;
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
