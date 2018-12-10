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
		    	$arrayOptions[] = array("label" => $i." / 0x".strtoupper(dechex($i))." - V1.x", "value" => $i);
		}
		for ($i = 32; $i <= 35; $i++) {
		    	$arrayOptions[] = array("label" => $i." / 0x".strtoupper(dechex($i))." - V2.x", "value" => $i);
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
	 	}
 	}
	    
	// Beginn der Funktionen
	public function GetInput()
	{
		$Result = -1;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetInput", "Ausfuehrung", 0);
			If ($this->ReadPropertyInteger("DeviceAddress") >= 32) {
				// 16OutV2
				$tries = 3;
				do {
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_read", "InstanceID" => $this->InstanceID, "Register" => hexdec("12"), "Count" => 2)));
					If ($Result < 0) {
						$this->SendDebug("GetInput", "Einlesen der Werte fehlerhaft!", 0);
						$this->SetStatus(202);
						$Result = false;
					}
					else {
						If (is_array(unserialize($Result))) {
							$this->SetStatus(102);
							$OutputArray = array();
							// für Eingänge PORT benutzen
							$OutputArray = unserialize($Result);
							$GPIOA = $OutputArray[1];
							$GPIOB = $OutputArray[2];
							$Result = ($GPIOB << 8) | $GPIOA;

							$this->SendDebug("GetInput", "GPIOA: ".$GPIOA." GPIOB: ".$GPIOB, 0);
							// Statusvariablen setzen
							for ($i = 0; $i <= 7; $i++) {
								// Port A
								$Value = $GPIOA & pow(2, $i);
								If (GetValueBoolean($this->GetIDForIdent("Input_X".$i)) == !$Value) {
									SetValueBoolean($this->GetIDForIdent("Input_X".$i), $Value);
								}
								// Port B
								$Value = $GPIOB & pow(2, $i);
								If (GetValueBoolean($this->GetIDForIdent("Input_X".($i + 8))) == !$Value) {
									SetValueBoolean($this->GetIDForIdent("Input_X".($i + 8)), $Value);
								}
							}
							break;
						}
					}
				$tries--;
				} while ($tries);  
			}
			else {
				// 16OutV1
				$tries = 3;
				do {
					$Result= $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Read", "InstanceID" => $this->InstanceID, "Register" => 0)));
					If ($Result < 0) {
						$this->SendDebug("GetInput", "Einlesen der Werte fehlerhaft!", 0);
						$this->SetStatus(202);
						$Result = false;
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
						
						$INTCAPAold = intval($this->GetBuffer("INTCAPA"));
						$INTCAPBold = intval($this->GetBuffer("INTCAPB"));
						
						// Statusvariablen setzen
						for ($i = 0; $i <= 7; $i++) {
							If ($INTCAPA <> $INTCAPAold) {
								// Port A
								$Value = $INTCAPA & pow(2, $i);
								If (GetValueBoolean($this->GetIDForIdent("Input_X".$i)) == !$Value) {
									SetValueBoolean($this->GetIDForIdent("Input_X".$i), $Value);
								}
							}
							
							If ($INTCAPB <> $INTCAPBold) {
								// Port B
								$Value = $INTCAPB & pow(2, $i);
								If (GetValueBoolean($this->GetIDForIdent("Input_X".($i + 8))) == !$Value) {
									SetValueBoolean($this->GetIDForIdent("Input_X".($i + 8)), $Value);
								}
							}
						}
						$this->SetBuffer("INTCAPA", $INTCAPA);
						$this->SetBuffer("INTCAPB", $INTCAPB);
						$this->GetInput();
						break;
					}
				}
			$tries--;
			} while ($tries);  
		}
	}   

	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			If ($this->ReadPropertyInteger("DeviceAddress") >= 32) {
				// 16OutV2
				$Config = 0;
				// Bit 0: irrelevant
				// Bit 1: INTPOL Polarität des Interrupts
				$INTPOL = 0;
				$Config = $Config | ($INTPOL << 1);
				// Bit 2: ODR Open-Drain oder aktiver Treiber beim Interrupt
				$ODR = 0;
				$Config = $Config | ($ODR << 2);
				// Bit 3: irrelvant, nur bei der SPI-Version nutzbar
				// Bit 4: DISSLW Defaultwert = 0
				// Bit 5: SEQOP Defaultwert = 0, automatische Adress-Zeiger inkrement
				// Bit 6: MIRROR Interrupt-Konfiguration
				$MIRROR = 1;
				$Config = $Config | ($MIRROR << 6);
				// Bit 7: BANK Defaultwert = 0 Register sind in derselben Bank

				// ConfigByte senden!
				$this->SendDebug("Setup", "Config-Byte: ".$Config, 0);
				$ConfigArray = array();
				$ConfigArray[0] = $Config;
				$ConfigArray[1] = $Config;
				// Adressen 0A 0B
				$tries = 5;
				do {
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_write", "InstanceID" => $this->InstanceID, "Register" => 0x0A, 
												  "Parameter" => serialize($ConfigArray) )));
					If (!$Result) {
						$this->SendDebug("Setup", "Basis-Konfigurations-Byte setzen fehlerhaft!", 0);
						$this->SetStatus(202);
					}
					else {
						$this->SendDebug("Setup", "Basis-Konfigurations-Byte erfolgreich gesetzt", 0);
						$this->SetStatus(102);
						break;
					}
				$tries--;
				} while ($tries);  

				$ConfigArray = array();
				// IO-Bytes festlegen
				$ConfigArray[0] = 255; // Adresse 00
				$ConfigArray[1] = 255; // Adresse 01
				$this->SendDebug("Setup", "IO-Byte A: 255 IO-Byte B: 255", 0);

				// Polariät des Eingangs festlegen
				$ConfigArray[2] = 0; // Adresse 02
				$ConfigArray[3] = 0; // Adresse 03
				$this->SendDebug("Setup", "Polaritaets-Byte A: 0 Polaritaets-Byte B: 0", 0);

				// Interrupt enable ermitteln
				$ConfigArray[4] = 255; // Adresse 04
				$ConfigArray[5] = 255; // Adresse 05
				$this->SendDebug("Setup", "Interrupt-Byte A: 255 Interrupt-Byte B: 255", 0);

				// Referenzwert-Byte ermitteln
				$ConfigArray[6] = 0; // Adresse 06
				$ConfigArray[7] = 0; // Adresse 07
				$this->SendDebug("Setup", "Referenzwert-Byte A/B = 0", 0);

				// Interrupt-Referenz-Byte ermitteln
				$ConfigArray[8] = 0; // Adresse 08
				$ConfigArray[9] = 0; // Adresse 09
				$this->SendDebug("Setup", "Interrupt-Referenzwert-Byte A/B = 0", 0);

				// Erneunt Basiskonfig-Byte mit übertragen
				$ConfigArray[10] = 64; // Adresse 0A
				$ConfigArray[11] = 64; // Adresse 0B
				
				// Pull-Up-Byte ermitteln
				$ConfigArray[12] = 0; // Adresse 0C
				$ConfigArray[13] = 0; // Adresse 0D
				$this->SendDebug("Setup", "Pull-up-Byte A: 0 Pull-up-Byte B: 0", 0);
				$tries = 5;
				do {
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_write", "InstanceID" => $this->InstanceID, "Register" => hexdec("00"), 
												  "Parameter" => serialize($ConfigArray) )));
					If (!$Result) {
						$this->SendDebug("Setup", "Konfigurations-Byte setzen fehlerhaft!", 0);
						$this->SetTimerInterval("GetInput", 0);
						$this->SetStatus(202);
					}
					else {
						$this->SendDebug("Setup", "Konfigurations-Byte erfolgreich gesetzt", 0);
						$this->SetStatus(102);
						$this->SetTimerInterval("GetInput", 15 * 1000);
						break;
					}
				$tries--;
				} while ($tries);
				
				$tries = 3;
				do {
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_read", "InstanceID" => $this->InstanceID, "Register" => hexdec("10"), "Count" => 2)));
					If ($Result < 0) {
						$this->SendDebug("Setup", "Einlesen der IntCap-Werte fehlerhaft!", 0);
						$this->SetStatus(202);
					}
					else {
						If (is_array(unserialize($Result))) {
							$this->SetStatus(102);
							$OutputArray = array(); 
							$OutputArray = unserialize($Result);
							$INTCAPA = $OutputArray[1]; // INTCAPA Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
							$INTCAPB = $OutputArray[2]; // INTCAPB Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
							$this->SendDebug("Setup", "INTCAPA: ".$INTCAPA." INTCAPB: ".$INTCAPB, 0);
							$this->SetBuffer("INTCAPA", $INTCAPA);
							$this->SetBuffer("INTCAPB", $INTCAPB);
							$this->GetInput();
							break;
						}
					}
				$tries--;
				} while ($tries); 
				
				
			}
			else {
				// 16OutV1
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
