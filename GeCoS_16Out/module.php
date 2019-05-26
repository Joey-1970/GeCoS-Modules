<?
    // Klassendefinition
    class GeCoS_16Out extends IPSModule 
    {
	// PCA9655E
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 25);
		$this->RegisterPropertyInteger("DeviceBus", 4);
		$this->RegisterPropertyInteger("StartOption", -1);
		$this->RegisterPropertyInteger("StartValue", 0);
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 15; $i++) {
			$this->RegisterVariableBoolean("Output_X".$i, "Ausgang X".$i, "~Switch", ($i + 1) * 10);
			$this->EnableAction("Output_X".$i);	
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
		for ($i = 25; $i <= 31; $i++) {
		    	$arrayOptions[] = array("label" => $i." / 0x".strtoupper(dechex($i))." - V1.x", "value" => $i);
		}
		for ($i = 36; $i <= 39; $i++) {
		    	$arrayOptions[] = array("label" => $i." / 0x".strtoupper(dechex($i))." - V2.x", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 0", "value" => 4);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 1", "value" => 5);
		If ($this->GetBoardVersion() == 1) {
			$arrayOptions[] = array("label" => "GeCoS I²C-Bus 2", "value" => 6);
		}
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "GeCoS I²C-Bus", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Ausgänge nach der Initialisierung setzen");

		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Status erhalten", "value" => -1);
		$arrayOptions[] = array("label" => "alle Ausgänge aus", "value" => 0);
		$arrayOptions[] = array("label" => "alle Ausgänge ein", "value" => 65535);
		$arrayOptions[] = array("label" => "bestimmter Status", "value" => -2);
		$arrayElements[] = array("type" => "Select", "name" => "StartOption", "caption" => "Start-Status", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "StartValue", "caption" => "Startwert");	
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

		$this->SetBuffer("OutputBank", 0);
		$this->SetBuffer("ErrorCounter", 0);

		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				//ReceiveData-Filter setzen
				$Filter = '((.*"Function":"get_used_i2c".*|.*"InstanceID":'.$this->InstanceID.'.*)|.*"Function":"status".*)';
				$this->SetReceiveDataFilter($Filter);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					// Setup
					$this->Setup();
					$this->GetOutput();
				}
			}
			else {
				$this->SetStatus(104);
			}	
		}
		else {
			$this->SetStatus(104);
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
	 	}
 	}
	
	public function RequestAction($Ident, $Value) 
	{
		$Number = intval(substr($Ident, 8, 2));
		$this->SetOutputPin($Number, $Value);
	}
	    
	// Beginn der Funktionen
	public function SetOutputPin(Int $Output, Bool $Value)
	{
		$Output = min(15, max(0, $Output));
		$Value = min(1, max(0, $Value));
		$Result = -1;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetOutputPin", "Value: ".$Value, 0);
			If ($this->ReadPropertyInteger("DeviceAddress") >= 36) {
				// 16OutV2
				$SetPort = $Output;
				// Bank ermitteln
				If ($Output <=7) {
					$Bitmask = $this->GetBuffer("OLATA");
					$Register = 0x14;
				}
				else {
					$Bitmask = $this->GetBuffer("OLATB");
					$Register = 0x15;
					$Output = $Output - 8;
				}
				// Bit setzen bzw. löschen
				If ($Value == true) {
					$Bitmask = $this->setBit($Bitmask, $Output);
				}
				else {
					$Bitmask = $this->unsetBit($Bitmask, $Output);
				}
				// Neuen Wert senden
				$OutputArray = Array();
				$OutputArray[0] = $Bitmask;
				$tries = 3;
				do {
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_write", "InstanceID" => $this->InstanceID, "Register" => $Register, 
												  "Parameter" => serialize($OutputArray) )));
					If ($Result) {
						$this->SendDebug("SetOutputPin", "Output ".$SetPort." Value: ".$Value." erfolgreich", 0);
						If (GetValueBoolean($this->GetIDForIdent("Output_X".$SetPort)) <> $Value) {
							SetValueBoolean($this->GetIDForIdent("Output_X".$SetPort), $Value);
						}
						$this->GetOutput();
						$this->SetStatus(102);
						$Result = true;
						break;
					}
					else {
						$this->SetStatus(202);
						$Result = false;
						$this->SendDebug("SetOutputPin", "Output ".$Output." Value: ".$Value." nicht erfolgreich!", 0);
					}
				$tries--;
				} while ($tries);  
			}
			else {
				// 16OutV1
				$Bitmask = $this->GetBuffer("OutputBank");
				If ($Value == true) {
					$Bitmask = $this->setBit($Bitmask, $Output);
				}
				else {
					$Bitmask = $this->unsetBit($Bitmask, $Output);
				}
				$tries = 3;
				do {
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Write", "InstanceID" => $this->InstanceID, "Register" => 2, "Value" => $Bitmask )));
					If ($Result) {
						$this->SendDebug("SetOutputPin", "Output ".$Output." Value: ".$Value." erfolgreich", 0);
						for ($i = 0; $i <= 15; $i++) {
							$Bitvalue = boolval($Bitmask & pow(2, $i));					
							If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) <> $Bitvalue) {
								SetValueBoolean($this->GetIDForIdent("Output_X".$i), $Bitvalue);
							}
						}
						$this->GetOutput();
						$this->SetStatus(102);
						$Result = true;
						break;
					}
					else {
						$this->SetStatus(202);
						$Result = false;
						$this->SendDebug("SetOutputPin", "Output ".$Output." Value: ".$Value." nicht erfolgreich!", 0);
					}
				$tries--;
				} while ($tries); 
			}
		}
	return $Result;
	}	
	
	public function GetOutput()
	{
		$Result = -1;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetOutput", "Ausfuehrung", 0);
			If ($this->ReadPropertyInteger("DeviceAddress") >= 36) {
				// 16OutV2
				$tries = 3;
				do {
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_read", "InstanceID" => $this->InstanceID, "Register" => hexdec("14"), "Count" => 2)));
					If ($Result < 0) {
						$this->SendDebug("GetOutput", "Einlesen der Werte fehlerhaft!", 0);
						$this->SetStatus(202);
						$Result = false;
					}
					else {
						If (is_array(unserialize($Result))) {
							$this->SetStatus(102);
							$OutputArray = array();
							// für Ausgänge LAT benutzen für Eingänge PORT 
							$OutputArray = unserialize($Result);
							// Ergebnis sichern
							$this->SetBuffer("OLATA", $OutputArray[1]);
							$this->SetBuffer("OLATB", $OutputArray[2]);
							$OLATA = $OutputArray[1];
							$OLATB = $OutputArray[2];
							$this->SendDebug("GetOutput", "OLATA: ".$OLATA." OLATB: ".$OLATB, 0);
							// Statusvariablen setzen
							for ($i = 0; $i <= 7; $i++) {
								// OLATA A
								$Value = $OLATA & pow(2, $i);
								If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) == !$Value) {
									SetValueBoolean($this->GetIDForIdent("Output_X".$i), $Value);
								}
								// Port B
								$Value = $OLATB & pow(2, $i);
								If (GetValueBoolean($this->GetIDForIdent("Output_X".($i + 8))) == !$Value) {
									SetValueBoolean($this->GetIDForIdent("Output_X".($i + 8)), $Value);
								}
							}
							$Result = ($OLATB << 8) | $OLATA;
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
					$Result= $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Read", "InstanceID" => $this->InstanceID, "Register" => 2)));
					if ($Result < 0) {
						$this->SendDebug("GetOutput", "Einlesen der Werte fehlerhaft!", 0);
						$this->SetStatus(202);
						$Result = false;
					}
					else {
						$this->SendDebug("GetOutput", "Ergebnis: ".$Result, 0);
						$this->SetBuffer("OutputBank", $Result);

						for ($i = 0; $i <= 15; $i++) {
							$Bitvalue = boolval($Result & pow(2, $i));					
							If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) <> $Bitvalue) {
								SetValueBoolean($this->GetIDForIdent("Output_X".$i), $Bitvalue);
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
	
	public function GetOutputPin(Int $Output)
	{
		$Output = min(15, max(0, $Output));
		$Result = -1;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetOutput", "Ausfuehrung", 0);
			$Result = $this->GetOutput();
			If ($Result >= 0) {
				$Result = boolval($Result & pow(2, $Output));
			}
		}
		
	return $Result;
	}    
	    
	public function SetOutput(int $Value) 
	{
		$Value = min(65535, max(0, $Value));
		$Result = -1;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetOutputBank", "Value: ".$Value, 0);
			If ($this->ReadPropertyInteger("DeviceAddress") >= 36) {
				// 16OutV2
				$OLATA = $Value & 255;
				$OLATB = ($Value >> 8) & 255;
				$OutputArray = Array();
				$OutputArray[0] = $OLATA;
				$OutputArray[1] = $OLATB;
				$tries = 3;
				do {
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_write", "InstanceID" => $this->InstanceID, "Register" => 0x14, 
												  "Parameter" => serialize($OutputArray) )));
					If ($Result) {
						$this->SendDebug("SetOutput", "Value: ".$Value." erfolgreich", 0);

						for ($i = 0; $i <= 7; $i++) {
							// OLATA A
							$SetPort = $OLATA & pow(2, $i);
							If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) == !$SetPort) {
								SetValueBoolean($this->GetIDForIdent("Output_X".$i), $SetPort);
							}
							// Port B
							$SetPort = $OLATB & pow(2, $i);
							If (GetValueBoolean($this->GetIDForIdent("Output_X".($i + 8))) == !$SetPort) {
								SetValueBoolean($this->GetIDForIdent("Output_X".($i + 8)), $SetPort);
							}
						}	
						$this->GetOutput();
						$this->SetStatus(102);
						$Result = true;
						break;
					}
					else {
						$this->SetStatus(202);
						$this->SendDebug("SetOutput", "Value: ".$Value." nicht erfolgreich!", 0);
						$Result = false;
					}
				$tries--;
				} while ($tries); 
			}
			else {
				// 16OutV1
				$tries = 3;
				do {
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Write", "InstanceID" => $this->InstanceID, "Register" => 2, "Value" => $Value )));
					If ($Result) {
						$this->SendDebug("SetOutput", "Value: ".$Value." erfolgreich", 0);
						for ($i = 0; $i <= 15; $i++) {
							$Bitvalue = boolval($Value & pow(2, $i));					
							If (GetValueBoolean($this->GetIDForIdent("Output_X".$i)) <> $Bitvalue) {
								SetValueBoolean($this->GetIDForIdent("Output_X".$i), $Bitvalue);
							}
						}
						$this->GetOutput();
						$this->SetStatus(102);
						$Result = true;
						break;
					}
					else {
						$this->SetStatus(202);
						$this->SendDebug("SetOutput", "Value: ".$Value." nicht erfolgreich!", 0);
						$Result = false;
					}
				$tries--;
				} while ($tries); 
			}
		}
	return $Result;
	}    
	
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			If ($this->ReadPropertyInteger("DeviceAddress") >= 36) {
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
				$MIRROR = 0;
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
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_write", "InstanceID" => $this->InstanceID, "Register" => hexdec("0A"), 
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
				$ConfigArray[0] = 0; // Adresse 00
				$ConfigArray[1] = 0; // Adresse 01
				$this->SendDebug("Setup", "IO-Byte A: 0 IO-Byte B: 0", 0);
				// Polariät des Eingangs festlegen
				$ConfigArray[2] = 0; // Adresse 02
				$ConfigArray[3] = 0; // Adresse 03
				$this->SendDebug("Setup", "Polaritaets-Byte A: 0 Polaritaets-Byte B: 0", 0);
				// Interrupt enable ermitteln
				$ConfigArray[4] = 0; // Adresse 04
				$ConfigArray[5] = 0; // Adresse 05
				$this->SendDebug("Setup", "Interrupt-Byte A: 0 Interrupt-Byte B: 0", 0);
				// Referenzwert-Byte ermitteln
				$ConfigArray[6] = 0; // Adresse 06
				$ConfigArray[7] = 0; // Adresse 07
				$this->SendDebug("Setup", "Referenzwert-Byte A/B = 0", 0);
				// Interrupt-Referenz-Byte ermitteln
				$ConfigArray[8] = 0; // Adresse 08
				$ConfigArray[9] = 0; // Adresse 09
				$this->SendDebug("Setup", "Interrupt-Referenzwert-Byte A/B = 0", 0);
				// Pull-Up-Byte ermitteln
				$ConfigArray[10] = 0; // Adresse 0C
				$ConfigArray[11] = 0; // Adresse 0D
				$this->SendDebug("Setup", "Pull-up-Byte A: 0 Pull-up-Byte B: 0", 0);
				$tries = 5;
				do {
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_MCP23017_write", "InstanceID" => $this->InstanceID, "Register" => hexdec("00"), 
												  "Parameter" => serialize($ConfigArray) )));
					If (!$Result) {
						$this->SendDebug("Setup", "Konfigurations-Byte setzen fehlerhaft!", 0);
						$this->SetStatus(202);
					}
					else {
						$this->SendDebug("Setup", "Konfigurations-Byte erfolgreich gesetzt", 0);
						If ($this->ReadPropertyInteger("StartOption") >= 0) {
							$this->SetOutput($this->ReadPropertyInteger("StartOption"));
						}
						elseif ($this->ReadPropertyInteger("StartOption") == -2) {
							$Value = min(65535, max(0, $this->ReadPropertyInteger("StartValue")));
							$this->SetOutput($Value);
						}
						$this->SetStatus(102);
						break;
					}
				$tries--;
				} while ($tries);  
			}
			else {
				// 16OutV1
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_PCA9655E_Write", "InstanceID" => $this->InstanceID, "Register" => 6, "Value" => 0 )));
				If ($Result) {
					$this->SendDebug("Setup", "erfolgreich", 0);
					If ($this->ReadPropertyInteger("StartOption") >= 0) {
						$this->SetOutput($this->ReadPropertyInteger("StartOption"));
					}
					elseif ($this->ReadPropertyInteger("StartOption") == -2) {
						$Value = min(65535, max(0, $this->ReadPropertyInteger("StartValue")));
						$this->SetOutput($Value);
					}
				}
				else {
					$this->SendDebug("Setup", "nicht erfolgreich!", 0);
					IPS_LogMessage("GeCoS_16Out", "Setup: nicht erfolgreich!");
				}
			}
		}    
	    
	private function setBit($byte, $significance) { 
 		// ein bestimmtes Bit auf 1 setzen
 		return $byte | 1<<$significance;   
 	} 
	
	private function unsetBit($byte, $significance) {
	    // ein bestimmtes Bit auf 0 setzen
	    return $byte & ~(1<<$significance);
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
