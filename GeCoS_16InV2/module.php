<?
    // Klassendefinition
    class GeCoS_16InV2 extends IPSModule 
    {
	// MCP23017
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
 	    	$this->RegisterPropertyInteger("DeviceAddress", 32);
		$this->RegisterPropertyInteger("DeviceBus", 4);
		$this->RegisterTimer("GetInput", 0, 'GeCoS16InV2_GetInput($_IPS["TARGET"]);');
		
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
						$this->Interrupt();
					}
				}
				break;		
	 	}
 	}
	    
	// Beginn der Funktionen
	public function GetInput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetInput", "Ausfuehrung", 0);
			// Adressen 12 13
			
			$tries = 3;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_read", "InstanceID" => $this->InstanceID, "Register" => hexdec("12"), "Count" => 2)));
				If ($Result < 0) {
					$this->SendDebug("GetInput", "Einlesen der Werte fehlerhaft!", 0);
					$this->SetStatus(202);
				}
				else {
					If (is_array(unserialize($Result))) {
						$this->SetStatus(102);
						$OutputArray = array();
						// für Eingänge PORT benutzen
						$OutputArray = unserialize($Result);
						$GPIOA = $OutputArray[1];
						$GPIOB = $OutputArray[2];
						
						$this->SendDebug("GetOutput", "GPIOA: ".$GPIOA." GPIOB: ".$GPIOB, 0);
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
	}
	
	private function Interrupt()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Interrupt", "Ausfuehrung", 0);
			// Adressen 12 13
			$tries = 3;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_read", "InstanceID" => $this->InstanceID, "Register" => hexdec("10"), "Count" => 2)));
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
						$INTCAPA = $OutputArray[1]; // INTCAPA Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
						$INTCAPB = $OutputArray[2]; // INTCAPB Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
						$this->SendDebug("Interrupt", "INTCAPA: ".$INTCAPA." INTCAPB: ".$INTCAPB, 0);
						// Statusvariablen setzen
						for ($i = 0; $i <= 7; $i++) {
							// Port A
							$Value = $INTCAPA & pow(2, $i);
							If (GetValueBoolean($this->GetIDForIdent("Input_X".$i)) == !$Value) {
								SetValueBoolean($this->GetIDForIdent("Input_X".$i), $Value);
							}
							
							// Port B
							$Value = $INTCAPB & pow(2, $i);
							If (GetValueBoolean($this->GetIDForIdent("Input_X".($i + 8))) == !$Value) {
								SetValueBoolean($this->GetIDForIdent("Input_X".($i + 8)), $Value);
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
	
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
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
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_write", "InstanceID" => $this->InstanceID, "Register" => hexdec("A0"), 
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
			// IO-Bytes ermitteln
			$GPAIODIR = 255; 
			$ConfigArray[0] = $GPAIODIR; // Adresse 00
			$this->SetBuffer("GPAIODIR", $GPAIODIR);
			
			$GPBIODIR = 255;
			$ConfigArray[1] = $GPBIODIR; // Adresse 01
			$this->SetBuffer("GPBIODIR", $GPBIODIR);
			$this->SendDebug("Setup", "IO-Byte A: ".$GPAIODIR." IO-Byte B: ".$GPBIODIR, 0);
			
			// Polariät des Eingangs ermitteln
			$GPAIPOL = 0;
			$ConfigArray[2] = $GPAIPOL; // Adresse 02
			
			$GPBIPOL = 0;
			$ConfigArray[3] = $GPBIPOL; // Adresse 03
			$this->SendDebug("Setup", "Polaritaets-Byte A: ".$GPAIPOL." Polaritaets-Byte B: ".$GPBIPOL, 0);
			
			// Interrupt enable ermitteln
			$GPAINTEN = 255;
			$ConfigArray[4] = $GPAINTEN; // Adresse 04
			
			$GPBINTEN = 255;
			$ConfigArray[5] = $GPBINTEN; // Adresse 05
			$this->SendDebug("Setup", "Interrupt-Byte A: ".$GPAINTEN." Interrupt-Byte B: ".$GPBINTEN, 0);
			
			// Referenzwert-Byte ermitteln
			$ConfigArray[6] = 0; // Adresse 06
			$ConfigArray[7] = 0; // Adresse 07
			$this->SendDebug("Setup", "Referenzwert-Byte A/B = 0", 0);
			
			// Interrupt-Referenz-Byte ermitteln
			$ConfigArray[8] = 0; // Adresse 08
			$ConfigArray[9] = 0; // Adresse 09
			$this->SendDebug("Setup", "Interrupt-Referenzwert-Byte A/B = 0", 0);
			
			// Pull-Up-Byte ermitteln
			$GPAPU = 255;
			$ConfigArray[10] = $GPAPU; // Adresse 0C
			
			$GPBPU = 255;
			$ConfigArray[11] = $GPBPU; // Adresse 0D
			$this->SendDebug("Setup", "Pull-up-Byte A: ".$GPAPU." Pull-up-Byte B: ".$GPBPU, 0);
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
					$this->GetInput();
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
