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
		$this->ConnectParent("{5F1C0403-4A74-4F14-829F-9A217CFB2D05}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 32);
		$this->RegisterPropertyInteger("DeviceBus", 0);
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
		for ($i = 32; $i <= 35; $i++) {
		    	$arrayOptions[] = array("label" => $i." / 0x".strtoupper(dechex($i))." - V2.x", "value" => $i);
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
				$Filter = '((.*"Function":"get_used_modules".*|.*"InstanceID":'.$this->InstanceID.'.*)|(.*"Function":"status".*|.*"Function":"interrupt".*))';
				$this->SetReceiveDataFilter($Filter);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_modules", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));		
				If ($Result == true) {
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
			case "SAI":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->SendDebug("ReceiveData", "SAI", 0);
					$Value = intval($data->Value); 
					// Statusvariablen setzen
					for ($i = 0; $i <= 15; $i++) {
						$Bitvalue = boolval($Value & pow(2, $i));					
						If (GetValueBoolean($this->GetIDForIdent("Input_X".$i)) <> $Bitvalue) {
							SetValueBoolean($this->GetIDForIdent("Input_X".$i), $Bitvalue);
						}
					}
				}
				break; 
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
			case "interrupt":
				If ($this->ReadPropertyBoolean("Open") == true) {
					If ($this->ReadPropertyInteger("DeviceBus") == $data->DeviceBus) {
						If ($this->ReadPropertyInteger("DeviceAddress") >= 32) {
							// 16OutV2
							$this->Interrupt();
						}
						else {
							// 16OutV1
							$this->GetInput();
						}
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
			case "interrupt_with_result_2":
				If (($this->ReadPropertyBoolean("Open") == true) AND ($data->InstanceID == $this->InstanceID)) {
					$this->SendDebug("interrupt_with_result_2", "Ausfuehrung", 0);
					
					$OutputArray = array();
					// für Ausgänge LAT benutzen für Eingänge PORT 
					$OutputArray = unserialize($data->Value);
					$INTFA = $OutputArray[1]; // INTCAPA Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
					$INTFB = $OutputArray[2]; // INTCAPB Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
					$this->SendDebug("Interrupt", "INTFA: ".$INTFA." INTFB: ".$INTFB, 0);

					$INTCAPA = $OutputArray[3]; // INTCAPA Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
					$INTCAPB = $OutputArray[4]; // INTCAPB Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
					$this->SendDebug("Interrupt", "INTCAPA: ".$INTCAPA." INTCAPB: ".$INTCAPB, 0);

					// Statusvariablen setzen
					for ($i = 0; $i <= 7; $i++) {
						If (((pow(2, $i) & $INTFA) >> $i) == true) {
							// Port A
							$Value = boolval($INTCAPA & pow(2, $i));
							If (GetValueBoolean($this->GetIDForIdent("Input_X".$i)) == !$Value) {
								SetValueBoolean($this->GetIDForIdent("Input_X".$i), $Value);
							}
						}
						If (((pow(2, $i) & $INTFB) >> $i) == true) {
							 // Port B
							$Value = boolval($INTCAPB & pow(2, $i));
							If (GetValueBoolean($this->GetIDForIdent("Input_X".($i + 8))) == !$Value) {
								SetValueBoolean($this->GetIDForIdent("Input_X".($i + 8)), $Value);
							}
						}
					}
					$this->GetInput();
				}
				break;	
	 	}
 	}
	    
	// Beginn der Funktionen
	public function GetInput()
	{
		$Result = -1;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetInput", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "SAI")));
			If ($Result == true) {
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(202);
			}
		}
	return $Result;
	}
	    
	private function Interrupt()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Interrupt", "Ausfuehrung", 0);
			// Adressen 12 13
			$tries = 3;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_read", "InstanceID" => $this->InstanceID, "Register" => 0x0E, "Count" => 4)));
				If ($Result < 0) {
					$this->SendDebug("Interrupt", "Einlesen der Werte fehlerhaft!", 0);
					$this->SetStatus(202);
				}
				else {
					If (is_array(unserialize($Result))) {
						$this->SetStatus(102);
						$OutputArray = array();
						// für Ausgänge LAT benutzen für Eingänge PORT 
						$OutputArray = unserialize($Result);
						$INTFA = $OutputArray[1]; // INTCAPA Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
						$INTFB = $OutputArray[2]; // INTCAPB Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
						$this->SendDebug("Interrupt", "INTFA: ".$INTFA." INTFB: ".$INTFB, 0);
						
						$INTCAPA = $OutputArray[3]; // INTCAPA Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
						$INTCAPB = $OutputArray[4]; // INTCAPB Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
						$this->SendDebug("Interrupt", "INTCAPA: ".$INTCAPA." INTCAPB: ".$INTCAPB, 0);
						
						// Statusvariablen setzen
						for ($i = 0; $i <= 7; $i++) {
							If (((pow(2, $i) & $INTFA) >> $i) == true) {
								// Port A
								$Value = boolval($INTCAPA & pow(2, $i));
								If (GetValueBoolean($this->GetIDForIdent("Input_X".$i)) == !$Value) {
									SetValueBoolean($this->GetIDForIdent("Input_X".$i), $Value);
								}
							}
							If (((pow(2, $i) & $INTFB) >> $i) == true) {
								 // Port B
								$Value = boolval($INTCAPB & pow(2, $i));
								If (GetValueBoolean($this->GetIDForIdent("Input_X".($i + 8))) == !$Value) {
									SetValueBoolean($this->GetIDForIdent("Input_X".($i + 8)), $Value);
								}
							}
						}
						$this->GetInput();
						break;
					}
				}
			$tries--;
			} while ($tries);  
		}
	}   
	    
	protected function HasActiveParent()
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
