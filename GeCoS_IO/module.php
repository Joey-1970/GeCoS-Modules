<?
class GeCoS_IO extends IPSModule
{
 	public function __construct($InstanceID) {
            	// Diese Zeile nicht löschen
            	parent::__construct($InstanceID);
	}
	 
	public function Create() 
	{
	    	// Diese Zeile nicht entfernen
	    	parent::Create();
	    
	    	// Modul-Eigenschaftserstellung
	    	$this->RegisterPropertyBoolean("Open", false);
	    	$this->RegisterPropertyString("IPAddress", "127.0.0.1");
		$this->RegisterPropertyString("User", "User");
	    	$this->RegisterPropertyString("Password", "Passwort");
	    	$this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
	}
  
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		$arrayElements[] = array("name" => "IPAddress", "type" => "ValidationTextBox",  "caption" => "IP");
		$arrayElements[] = array("type" => "Label", "label" => "Zugriffsdaten des Raspberry Pi SSH:");
		$arrayElements[] = array("name" => "User", "type" => "ValidationTextBox",  "caption" => "User");
		$arrayElements[] = array("name" => "Password", "type" => "PasswordTextBox",  "caption" => "Password");

		
		
		$arrayActions = array();
		If ($this->ReadPropertyBoolean("Open") == true) {   
			$arrayActions[] = array("type" => "Button", "label" => "PIGPIO Restart", "onClick" => 'GeCoSIO_PIGPIOD_Restart($id);');
		}
		else {
			$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		}
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	} 
	  
	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		
		// Nachrichten abonnieren
		// Kernel
	        $this->RegisterMessage(0, 10100); // Alle Kernelmessages (10103 muss im MessageSink ausgewertet werden.)
		
		If (IPS_GetKernelRunlevel() == 10103) {
			$this->RegisterVariableInteger("Handle", "Handle", "", 100);
			$this->DisableAction("Handle");
			IPS_SetHidden($this->GetIDForIdent("Handle"), true);
			
			$this->RegisterVariableInteger("HardwareRev", "HardwareRev", "", 105);
			$this->DisableAction("HardwareRev");
			IPS_SetHidden($this->GetIDForIdent("HardwareRev"), true);
		
			$this->RegisterVariableString("Hardware", "Hardware", "", 107);
			$this->DisableAction("Hardware");
			IPS_SetHidden($this->GetIDForIdent("Hardware"), true);
			
			$this->RegisterVariableInteger("SoftwareVersion", "SoftwareVersion", "", 108);
			$this->DisableAction("SoftwareVersion");
			IPS_SetHidden($this->GetIDForIdent("SoftwareVersion"), true);
			
			$this->RegisterVariableString("I2C_Handle", "I2C_Handle", "", 160);
			$this->DisableAction("I2C_Handle");
			IPS_SetHidden($this->GetIDForIdent("I2C_Handle"), true);
			
			$this->SetBuffer("SerialNotify", "false");
			$this->SetBuffer("Default_I2C_Bus", 1);
			$this->SetBuffer("Default_Serial_Bus", 0);
			$this->SetBuffer("MUX_Channel", 1);
			
			$ParentID = $this->GetParentID();
		        // Änderung an den untergeordneten Instanzen
		        $this->RegisterMessage($this->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
		        $this->RegisterMessage($this->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
		        // INSTANCEMESSAGE
		        $this->RegisterMessage($ParentID, 10505); // Status hat sich geändert
	
			If ($ParentID > 0) {
				If (IPS_GetProperty($ParentID, 'Host') <> $this->ReadPropertyString('IPAddress')) {
		                	IPS_SetProperty($ParentID, 'Host', $this->ReadPropertyString('IPAddress'));
				}
				If (IPS_GetProperty($ParentID, 'Port') <> 8888) {
		                	IPS_SetProperty($ParentID, 'Port', 8888);
				}
				If (IPS_GetName($ParentID) == "Client Socket") {
		                	IPS_SetName($ParentID, "GeCoS");
				}
			}
	
			If (($this->ConnectionTest()) AND ($this->ReadPropertyBoolean("Open") == true))  {
				// Hardware und Softwareversion feststellen
				$this->CommandClientSocket(pack("LLLL", 17, 0, 0, 0).pack("LLLL", 26, 0, 0, 0), 32);
				
				// I2C-Handle zurücksetzen
				$this->ResetI2CHandle();

				$I2C_DeviceHandle = array();
				SetValueString($this->GetIDForIdent("I2C_Handle"), serialize($I2C_DeviceHandle));
				
				// Notify Handle zurücksetzen falls gesetzt
				If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
					// Handle löschen
					// Alle Handle bis GetValueInteger($this->GetIDForIdent("Handle") löschen!
					//$this->ClientSocket(pack("LLLL", 21, GetValueInteger($this->GetIDForIdent("Handle")), 0, 0));
				}
				// Notify Starten
				SetValueInteger($this->GetIDForIdent("Handle"), -1);
				$this->ClientSocket(pack("LLLL", 99, 0, 0, 0));
				
				// I²C Bus 1 für RTC, Serielle Schnittstelle,
				//Notify Pin 17 + 27 + 15= Bitmask 134381568
				$this->CommandClientSocket(pack("LLLL", 19, GetValueInteger($this->GetIDForIdent("Handle")), 134381568, 0), 16);
								
				// RTC einrichten
				$this->GetOnboardI2CHandle(104);
				// MUX einrichten
				$this->GetOnboardI2CHandle(112);
				// MUX setzen
				//$this->SetMUX(1);
				
				$this->Get_PinUpdate();
				$this->SetStatus(102);	
			}
			else {
				$this->SetStatus(104);
			}
		}
		else {
			return;
		}
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
        IPS_LogMessage("IPS2GPIO MessageSink", "Message from SenderID ".$SenderID." with Message ".$Message."\r\n Data: ".print_r($Data, true));
		switch ($Message) {
			case 10100:
				If ($Data[0] == 10103) {
					$this->ApplyChanges();
				}
				break;
			case 11101:
				IPS_LogMessage("IPS2GPIO MessageSink", "Instanz ".$SenderID." wurde verbunden");
				break;
			case 11102:
				IPS_LogMessage("IPS2GPIO MessageSink", "Instanz  ".$SenderID." wurde getrennt");
				break;	
			case 10505:
				If ($Data[0] == 102) {
					$this->ApplyChanges();
				}
				break;
		}
    	}
	  
	 public function ForwardData($JSONString) 
	 {
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	
	 	switch ($data->Function) {
		// interne Kommunikation
		
		   case "get_pinupdate":
		   	$this->Get_PinUpdate();
		   	break;
		   
		   // I2C Kommunikation
		   case "set_used_i2c":		   	
		   	// die genutzten Device Adressen anlegen
		   	$I2C_DeviceHandle = unserialize(GetValueString($this->GetIDForIdent("I2C_Handle")));
		   	// DeviceIdent bilden
			$I2C_DeviceHandle[($data->DeviceBus << 7) + $data->DeviceAddress] = -1;
			// genutzte DeviceIdent noch ohne Handle sichern
		   	SetValueString($this->GetIDForIdent("I2C_Handle"), serialize($I2C_DeviceHandle));
		   	// Messages einrichten
			$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
		        $this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
		   	// Handle ermitteln
			IPS_LogMessage("GeCoS_IO","DeviceBus: ".$data->DeviceBus." DeviceAddress: ".$data->DeviceAddress); 
		   	$this->SetMUX($data->DeviceBus);
			$this->CommandClientSocket(pack("LLLLL", 54, 1, $data->DeviceAddress, 4, 0), 16);	
		   	
			//IPS_LogMessage("IPS2GPIO I2C Handle: ","Device Adresse: ".$data->DeviceAddress.", Hardware Rev:: ".GetValueInteger($this->GetIDForIdent("HardwareRev"))); 
		   	break;
		   case "i2c_destroy":
		   	//IPS_LogMessage("IPS2GPIO I2C Destroy: ",$data->DeviceAddress." , ".$data->Register); 
		   	If ($this->GetI2C_DeviceHandle($data->DeviceAddress) >= 0) {
		   		$I2C_DeviceHandle = unserialize(GetValueString($this->GetIDForIdent("I2C_Handle")));
		   		// Handle für das Device löschen
		   		$this->CommandClientSocket(pack("L*", 55, GetI2C_DeviceHandle($data->DeviceAddress), 0, 0), 16);
		   		// Device aus dem Array löschen
				array_splice($I2C_DeviceHandle, $data->DeviceAddress, 1); 
				If (Count($I2C_DeviceHandle) == 0) {
					SetValueBoolean($this->GetIDForIdent("I2C_Used"), false);
				}
				SetValueString($this->GetIDForIdent("I2C_Handle"), serialize($I2C_DeviceHandle));
		   	}
		   	break;
		case "i2c_read_byte":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Byte Parameter: ",$data->Handle." , ".$data->Register); 
		   	If ($this->GetI2C_DeviceHandle($data->DeviceIdent) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 61, $this->GetI2C_DeviceHandle($data->DeviceIdent), $data->Register, 0), 16);
		   	}
		   	break;
		case "i2c_read_bytes":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Bytes",$this->GetI2C_DeviceHandle($data->DeviceAddress)." , ".$data->Register." , ".$data->Count);  	
		   	If ($this->GetI2C_DeviceHandle($data->DeviceIdent) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 56, $this->GetI2C_DeviceHandle($data->DeviceIdent), $data->Count, 0), 16 + ($data->Count));
		   	}
			break;  
		case "i2c_write_bytes":
		   	//IPS_LogMessage("IPS2GPIO I2C Write Byte Handle: ","DeviceAdresse: ".$data->DeviceAddress.", Handle: ".$this->GetI2C_DeviceHandle($data->DeviceAddress).", Wert: ".$data->Value);  	
		   	//IPS_LogMessage("GeCoS_IO","DeviceIdent: ".$data->DeviceIdent); 
			If ($this->GetI2C_DeviceHandle($data->DeviceIdent) >= 0) {
		   		IPS_LogMessage("GeCoS_IO","Command: ".$data->Command);
				$this->SetMUX($data->DeviceIdent >> 7);
				$Command = $data->Command;
				//IPS_LogMessage("GeCoS_IO","DeviceAdresse: ".$data->DeviceAddress.", Handle: ".$this->GetI2C_DeviceHandle($data->DeviceAddress).", Wert: ".$data->Value.", Bus: ".$data->DeviceIdent >> 7); 
				$this->CommandClientSocket(pack("L*", 57, intval($this->GetI2C_DeviceHandle($data->DeviceIdent)), 0, 4).$Command, 16);
		   	}
		   	break;	
		case "i2c_read_byte_onhandle":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Byte Handle: ","DeviceAdresse: ".$data->DeviceAddress.", Handle: ".$this->GetI2C_DeviceHandle($data->DeviceAddress));  	
		   	If ($this->GetI2C_DeviceHandle($data->DeviceIdent) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 59, intval($this->GetI2C_DeviceHandle($data->DeviceIdent)), 0, 0), 16);
		   	}
		   	break;	
		case "i2c_write_byte_onhandle":
		   	//IPS_LogMessage("IPS2GPIO I2C Write Byte Handle: ","DeviceAdresse: ".$data->DeviceAddress.", Handle: ".$this->GetI2C_DeviceHandle($data->DeviceAddress).", Wert: ".$data->Value);  	
		   	If ($this->GetI2C_DeviceHandle($data->DeviceIdent) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 60, intval($this->GetI2C_DeviceHandle($data->DeviceIdent)), $data->Value, 0), 16);
		   	}
		   	break;
		case "i2c_write_byte":
		   	//IPS_LogMessage("IPS2GPIO I2C Write Byte : ",$data->Handle." , ".$data->Register." , ".$data->Value);  	
		   	If ($this->GetI2C_DeviceHandle($data->DeviceIdent) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle($data->DeviceIdent), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		case "i2c_read_word":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Word Parameter : ","DeviceAdresse: ".$data->DeviceAddress.", Handle: ".$this->GetI2C_DeviceHandle($data->DeviceAddress)." ,Register: ".$data->Register);
		   	If ($this->GetI2C_DeviceHandle($data->DeviceIdent) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 63, intval($this->GetI2C_DeviceHandle($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break; 
		case "i2c_read_block_byte":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Block Byte Parameter : ",$data->Handle." , ".$data->Register." , ".$data->Count);  	
		   	If ($this->GetI2C_DeviceHandle($data->DeviceIdent) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle($data->DeviceIdent), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
			break;
		   
		   
		   
		  
		   	
		   
		   // Serielle Kommunikation
		   case "get_handle_serial":
	   		//IPS_LogMessage("IPS2GPIO Get Handle Serial", "Handle anfordern");
	   		$this->CommandClientSocket(pack("L*", 76, $data->Baud, 0, strlen($data->Device)).$data->Device.pack("L*", 19, GetValueInteger($this->GetIDForIdent("Handle")), $this->CalcBitmask(), 0), 32);
			// Messages einrichten
			$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
		        $this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
	   		break;
		   case "write_bytes_serial":
		   	$Command = utf8_decode($data->Command);
		   	//IPS_LogMessage("IPS2GPIO Write Bytes Serial", "Handle: ".GetValueInteger($this->GetIDForIdent("Serial_Handle"))." Command: ".$Command);
		   	$this->CommandClientSocket(pack("L*", 81, GetValueInteger($this->GetIDForIdent("Serial_Handle")), 0, strlen($Command)).$Command, 16);
		   	break;
		   case "check_bytes_serial":
		   	//IPS_LogMessage("IPS2GPIO Check Bytes Serial", "Handle: ".GetValueInteger($this->GetIDForIdent("Serial_Handle")));
		   	$this->CommandClientSocket(pack("L*", 83, GetValueInteger($this->GetIDForIdent("Serial_Handle")), 0, 0), 16);
		   	break;
		    
		    // Raspberry Pi Kommunikation
		    case "get_RPi_connect":
		   	// SSH Connection
			If ($data->IsArray == false) {
				// wenn es sich um ein einzelnes Kommando handelt
				//IPS_LogMessage("IPS2GPIO SSH-Connect", $data->Command );
				$Result = $this->SSH_Connect($data->Command);
				//IPS_LogMessage("IPS2GPIO SSH-Connect", $Result );
				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_RPi_connect", "InstanceID" => $data->InstanceID, "CommandNumber" => $data->CommandNumber, "Result"=>utf8_encode($Result), "IsArray"=>false  )));
			}
			else {
				// wenn es sich um ein Array von Kommandos handelt
				$Result = $this->SSH_Connect_Array($data->Command);
				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_RPi_connect", "InstanceID" => $data->InstanceID, "CommandNumber" => $data->CommandNumber, "Result"=>utf8_encode($Result), "IsArray"=>true  )));
			}
			break;
		    // 1-Wire
		    case "get_1wire_devices":
			$Result = $this->GetOneWireDevices();
			$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_1wire_devices", "InstanceID" => $data->InstanceID, "Result"=>utf8_encode($Result) )));
			break;
		    case "get_1W_data":
			$Result = $this->SSH_Connect_Array($data->Command);
			//IPS_LogMessage("IPS2GPIO 1-Wire-Data", $Result );
			$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_1wire_data", "InstanceID" => $data->InstanceID, "Result"=>utf8_encode($Result) )));
			break;
		}
	  }
	
	 public function ReceiveData($JSONString) {
 	    	$CmdPossible = array(19, 21, 76, 81, 99, 115, 116);
 	    	$RDlen = array(16, 32);	
 	    	// Empfangene Daten vom I/O
	    	$Data = json_decode($JSONString);
	    	$Message = utf8_decode($Data->Buffer);
	    	$MessageLen = strlen($Message);
	    	$MessageArray = unpack("L*", $Message);
		$Command = $MessageArray[1];
	    	
	    	If ((in_array($Command, $CmdPossible)) AND (in_array($MessageLen, $RDlen))) {
	    		// wenn es sich um mehrere Standarddatensätze handelt
	    		$DataArray = str_split($Message, 16);
	    		//IPS_LogMessage("IPS2GPIO ReceiveData", "Überlänge: ".Count($DataArray)." Command-Datensätze");
	    		for ($i = 0; $i < Count($DataArray); $i++) {
    				$this->ClientResponse($DataArray[$i]);
			}
	    	}
		elseif (($MessageLen / 12) == intval($MessageLen / 12)) {
	    		// wenn es sich um mehrere Notifikationen handelt
	    		$DataArray = str_split($Message, 12);
	    		//IPS_LogMessage("IPS2GPIO ReceiveData", "Überlänge: ".Count($DataArray)." Notify-Datensätze");
	    		for ($i = 0; $i < min(5, Count($DataArray)); $i++) {
				$MessageParts = unpack("L*", $DataArray[$i]);
				
				// Wert von Pin 17
				$Bitvalue_17 = boolval($MessageParts[3]&(1<<17));
				IPS_LogMessage("GeCoS", "Bit 17: ".$Bitvalue_17);
				// Wert von Pin 27
				$Bitvalue_27 = boolval($MessageParts[3]&(1<<27));
				IPS_LogMessage("GeCoS", "Bit 27: ".$Bitvalue_27);
				// Wert von Pin 15
				$Bitvalue_15 = boolval($MessageParts[3]&(1<<15));
				IPS_LogMessage("GeCoS", "Bit 15: ".$Bitvalue_15);
			}
		}
	 	else {
	 		// Prüfen ob Daten im Serial Buffer vorhanden sind
			IPS_Sleep(75);
			$this->CommandClientSocket(pack("L*", 82, GetValueInteger($this->GetIDForIdent("Serial_Handle")), 0, 0), 16);
	 	}
	 }
 
	  public function RequestAction($Ident, $Value) 
	  {
		    switch($Ident) {
		        case "Open":
		            If ($Value = True) {
		            		$this->SetStatus(101);
		            		$this->ConnectionTest();
		            	}
		 	   else {
		 	   		$this->SetStatus(104);
		 	   	}
		            //Neuen Wert in die Statusvariable schreiben
		            SetValue($this->GetIDForIdent($Ident), $Value);
		            break;
		        default:
		            throw new Exception("Invalid Ident");
		    }
	 }
  
	// Aktualisierung der genutzten Pins und der Notifikation
	private function Get_PinUpdate()
	{
		// Pins ermitteln für die ein Notify erforderlich ist
		
		// Notify zurücksetzen	
		If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
	           	$this->CommandClientSocket(pack("LLLL", 19, GetValueInteger($this->GetIDForIdent("Handle")), $this->CalcBitmask(), 0), 16);
		}
		// Ermitteln ob der I2C-Bus genutzt wird und welcher Device Adressen
		// Bisherige I2C-Handle löschen
		$I2C_DeviceHandle = array_values(unserialize(GetValueString($this->GetIDForIdent("I2C_Handle"))));
		for ($i = 2; $i < Count($I2C_DeviceHandle); $i++) {
			$this->CommandClientSocket(pack("L*", 55, $I2C_DeviceHandle[$i], 0, 0), 16);
		}
		// Pins ermitteln die genutzt werden
		$PinUsed = array();
		// Reservieren der Schnittstellen GPIO
		$PinUsed[2] = 99999; 
		$PinUsed[3] = 99999;
		$this->CommandClientSocket(pack("LLLL", 0, 2, 4, 0).pack("LLLL", 0, 3, 4, 0), 32);
		
		/*
		SetValueBoolean($this->GetIDForIdent("Serial_Used"), false);
		If ($this->ReadPropertyBoolean("Serial_Used") == true)  {
			$PinUsed[14] = 99999; 
			$PinUsed[15] = 99999;
			// Raspberry Pi 3 = Alt5(Rxd1/TxD1) => 2
			// Alle anderen = Alt0(Rxd0/TxD0) => 4
			If ($this->GetBuffer("Default_Serial_Bus") == 0) {
				$this->CommandClientSocket(pack("LLLL", 0, 14, 4, 0).pack("LLLL", 0, 15, 4, 0), 32);
			}
			elseif ($this->GetBuffer("Default_Serial_Bus") == 1) {
				// Beim Raspberry Pi 3 ist Bus 0 schon durch die Bluetooth-Schnittstelle belegt
				$this->CommandClientSocket(pack("LLLL", 0, 14, 2, 0).pack("LLLL", 0, 15, 2, 0), 32);
			}
			If (GetValueInteger($this->GetIDForIdent("Serial_Handle")) >= 0) {
				$this->CommandClientSocket(pack("L*", 77, GetValueInteger($this->GetIDForIdent("Serial_Handle")), 0, 0), 16);
			}
			SetValueInteger($this->GetIDForIdent("Serial_Handle"), -1);
			SetValueBoolean($this->GetIDForIdent("Serial_Used"), false);
			// den Notify für den TxD-Pin einschalten
		}
		else {
			// wird Serial nicht benötigt die Pin auf in Input setzen
			$this->CommandClientSocket(pack("LLLL", 0, 14, 0, 0).pack("LLLL", 0, 15, 0, 0), 16);
		}
		If ($this->ReadPropertyBoolean("SPI_Used") == true)  {
			for ($i = 7; $i < 11; $i++) {
    				$PinUsed[$i] = 99999;
			}
		}
		else {
			// wird SPI nicht benötigt die Pin auf Input setzen
		}
		// Reseervierung des 1-Wire-Pins
		If ($this->ReadPropertyBoolean("1Wire_Used") == true)  {
			$PinUsed[4] = 99999;
			$this->CommandClientSocket(pack("LLLL", 0, 4, 1, 0), 16);
		}
		else {
			// wird 1-Wire nicht benötigt die Pin auf Input setzen
			$this->CommandClientSocket(pack("LLLL", 0, 4, 0, 0), 16);
		}
		*/
		// Sichern der Voreinstellungen
		// Ermitteln der genutzten I2C-Adressen
		$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"get_used_i2c")));
	}
	
	private function ClientSocket(String $message)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$res = $this->SendDataToParent(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Buffer" => utf8_encode($message))));  
		}
	}
	
	private function CommandClientSocket(String $message, $ResponseLen = 16)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			if (IPS_SemaphoreEnter("CommandClientSocket", 25))
			{
				// Socket erstellen
				if(!($sock = socket_create(AF_INET, SOCK_STREAM, 0))) {
					$errorcode = socket_last_error();
					$errormsg = socket_strerror($errorcode);
					IPS_LogMessage("IPS2GPIO Socket: ", "Fehler beim Erstellen ".$errorcode." ".$errormsg);
					return;
				}
				// Timeout setzen
				socket_set_option($sock,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>2, "usec"=>0));
				// Verbindung aufbauen
				if(!(socket_connect($sock, $this->ReadPropertyString("IPAddress"), 8888))) {
					$errorcode = socket_last_error();
					$errormsg = socket_strerror($errorcode);
					IPS_LogMessage("IPS2GPIO Socket: ", "Fehler beim Verbindungsaufbaus ".$errorcode." ".$errormsg);
					return;
				}
				// Message senden
				if( ! socket_send ($sock, $message, strlen($message), 0))
				{
					$errorcode = socket_last_error();
					$errormsg = socket_strerror($errorcode);
					IPS_LogMessage("IPS2GPIO Socket: ", "Fehler beim beim Senden ".$errorcode." ".$errormsg);
					return;
				}
				//Now receive reply from server
				if(socket_recv ($sock, $buf, $ResponseLen, MSG_WAITALL ) === FALSE) {
					$errorcode = socket_last_error();
					$errormsg = socket_strerror($errorcode);
					IPS_LogMessage("IPS2GPIO Socket: ", "Fehler beim beim Empfangen ".$errorcode." ".$errormsg);
					return;
				}
				// Anfragen mit variabler Rückgabelänge
				$CmdVarLen = array(56, 67, 70, 73, 75, 80, 88, 91, 92, 106, 109);
				$MessageArray = unpack("L*", $buf);
				$Command = $MessageArray[1];
				If (in_array($Command, $CmdVarLen)) {
					$this->ClientResponse($buf);
					//IPS_LogMessage("IPS2GPIO ReceiveData", strlen($buf)." Zeichen");
				}
				// Standardantworten
				elseIf ((strlen($buf) == 16) OR ((strlen($buf) / 16) == intval(strlen($buf) / 16))) {
					$DataArray = str_split($buf, 16);
					//IPS_LogMessage("IPS2GPIO ReceiveData", strlen($buf)." Zeichen");
					for ($i = 0; $i < Count($DataArray); $i++) {
						$this->ClientResponse($DataArray[$i]);
					}
				}
				else {
					IPS_LogMessage("IPS2GPIO ReceiveData", strlen($buf)." Zeichen - nicht differenzierbar!");
				}
				IPS_SemaphoreLeave("CommandClientSocket");
			}
		}	
	}
	
	private function ClientResponse(String $Message)
	{
		$response = unpack("L*", $Message);
		switch($response[1]) {
		        case "0":
		        	If ($response[4] == 0) {
		        		//IPS_LogMessage("IPS2GPIO Set Mode", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        	}
		        	else {
		        		IPS_LogMessage("IPS2GPIO Set Mode", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])));
		        	}
		        	break;
		        case "2":
		        	If ($response[4] == 0) {
		        		//IPS_LogMessage("IPS2GPIO Set Pull-up/Down-Widerstand", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        	}
		        	else {
		        		IPS_LogMessage("IPS2GPIO Set Pull-up/Down-Widerstand", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])));
		        	}
		        	break;
			case "4":
		        	If ($response[4] == 0) {
		        		//IPS_LogMessage("IPS2GPIO Write", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"result", "Pin" => $response[2], "Value"=> $response[3])));
		        	}
		        	else {
		        		IPS_LogMessage("IPS2GPIO Write", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])));
		        	}
		        	break;
		        case "5":
		        	If ($response[4] == 0) {
		        		//IPS_LogMessage("IPS2GPIO PWM", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"result", "Pin" => $response[2], "Value"=> $response[3])));
		        	}
		        	else {
		        		IPS_LogMessage("IPS2GPIO PWM", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])));
		        	}
		        	break;
		        case "17":
		            	//IPS_LogMessage("IPS2GPIO Hardwareermittlung: ","gestartet");
		            	$Model[0] = array(2, 3);
		            	$Model[1] = array(4, 5, 6, 13, 14, 15);
		            	$Model[2] = array(16);
		            	$Typ[0] = array(0, 1, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 21, 22, 23, 24, 25);	
           			$Typ[1] = array(2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 22, 23, 24, 25, 27);
           			$Typ[2] = array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27);
           			
           			SetValueInteger($this->GetIDForIdent("HardwareRev"), $response[4]);
				SetValueString($this->GetIDForIdent("Hardware"), $this->GetHardware($response[4]));
           			
           			if (in_array($response[4], $Model[0])) {
    					 IPS_LogMessage("IPS2GPIO Hardwareermittlung: ","Raspberry Pi Typ 0");
				}
				else if (in_array($response[4], $Model[1])) {
					IPS_LogMessage("IPS2GPIO Hardwareermittlung: ","Raspberry Pi Typ 1");
				}
				else if ($response[4] >= 16) {
					IPS_LogMessage("IPS2GPIO Hardwareermittlung: ","Raspberry Pi Typ 2");
				}
				else
					IPS_LogMessage("IPS2GPIO Hardwareermittlung","nicht erfolgreich! Fehler:".$this->GetErrorText(abs($response[4])));
				break;
           		case "19":
           			IPS_LogMessage("IPS2GPIO Notify","gestartet");
		            	break;
           		case "21":
           			IPS_LogMessage("IPS2GPIO Notify","gestoppt");
		            	break;
			case "26":
           			If ($response[4] >= 0 ) {
					SetValueInteger($this->GetIDForIdent("SoftwareVersion"), $response[4]);
					If ($response[4] < 60 ) {
						IPS_LogMessage("IPS2GPIO PIGPIO Software Version","Bitte neuste PIGPIO-Software installieren!");
					}
				}
           			else {
           				IPS_LogMessage("IPS2GPIO PIGPIO Software Version","Fehler: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "54":
		        	If ($response[4] >= 0 ) {
           				//IPS_LogMessage("IPS2GPIO I2C Handle",$response[4]." für Device ".$response[3]);
           				$I2C_DeviceHandle = unserialize(GetValueString($this->GetIDForIdent("I2C_Handle")));
 					// Hier wird der ermittelte Handle der DiviceAdresse/Bus hinzugefügt
					$I2C_DeviceHandle[($this->GetBuffer("MUX_Channel") << 7) + $response[3]] = $response[4];
					
					//$I2C_DeviceHandle[$response[3]] = $response[4];
 					SetValueString($this->GetIDForIdent("I2C_Handle"), serialize($I2C_DeviceHandle));
           			}
           			else {
           				IPS_LogMessage("GeCoS_IO I2C Handle","Fehlermeldung: ".$this->GetErrorText(abs($response[4]))." Handle für Device ".$response[3]." nicht vergeben!");
           			}
           			
		        	break;
		        case "55":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO I2C Close Handle","Handle: ".$response[2]." Value: ".$response[4]);
           			}
           			else {
           				//IPS_LogMessage("IPS2GPIO I2C Close Handle","Handle: ".$response[2]." Value: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "56":
           			If ($response[4] >= 0) {
					//IPS_LogMessage("IPS2GPIO I2C Read Bytes","Handle: ".$response[2]." Register: ".$response[3]." Count: ".$response[4]);
					$ByteMessage = substr($Message, -($response[4]));
					$ByteResponse = unpack("C*", $ByteMessage);
					$ByteArray = serialize($ByteResponse);
					$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_byte_block", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Count" => $response[4], "ByteArray" => $ByteArray)));
				}
		            	else {
           				IPS_LogMessage("IPS2GPIO I2C Read Bytes","Handle: ".$response[2]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
				break;
			case "57":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("GeCoS_IO I2C Write Bytes","Handle: ".$response[2]." Value: ".$response[4]);
		            		$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Value" => $response[4])));
           			}
           			else {
           				IPS_LogMessage("GeCoS_IO I2C Write Bytes","Handle: ".$response[2]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
			case "59":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO I2C Read Byte Handle","Handle: ".$response[2]." Value: ".$response[4]);
		            		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Value" => $response[4])));
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO I2C Read Byte Handle","Handle: ".$response[2]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "60":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("GeCoS_IO","Handle: ".$response[2]." Value: ".$response[4]);
		            		$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
           			}
           			else {
           				IPS_LogMessage("GeCoS_IO I2C Write Byte Handle","Handle: ".$response[2]." Value: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "61":
		            	If ($response[4] >= 0) {
		            		//IPS_LogMessage("IPS2GPIO I2C Read Byte","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]." DeviceSign: ".$this->GetI2C_HandleDevice($response[2]));
		            		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
		            	}
		            	else {
		            		IPS_LogMessage("IPS2GPIO I2C Read Byte","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));	
		            	}
		            	break;
		        case "62":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO I2C Write Byte","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]);
		            		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO I2C Write Byte","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "63":
		            	If ($response[4] >= 0) {
		            		//IPS_LogMessage("IPS2GPIO I2C Read Word","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]);
		            		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
		            	}
		            	else {
		            		IPS_LogMessage("IPS2GPIO I2C Read Word","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
		            	}
		            	break;
		        case "67":
           			If ($response[4] >= 0) {
					//IPS_LogMessage("IPS2GPIO I2C Read Block Byte","Handle: ".$response[2]." Register: ".$response[3]." Count: ".$response[4]." DeviceSign: ".$this->GetI2C_HandleDevice($response[2]));
					$ByteMessage = substr($Message, -($response[4]));
					$ByteResponse = unpack("C*", $ByteMessage);
					$ByteArray = serialize($ByteResponse);
					$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_byte_block", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Count" => $response[4], "ByteArray" => $ByteArray)));
				}
				else {
		            		IPS_LogMessage("IPS2GPIO I2C Read Block Byte","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
		            	}
				break;
		        case "76":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO Serial Handle","Serial Handle: ".$response[4]);
           				SetValueInteger($this->GetIDForIdent("Serial_Handle"), $response[4]);
           				SetValueBoolean($this->GetIDForIdent("Serial_Used"), true);
				}
				else {
					IPS_LogMessage("IPS2GPIO I2C Get Serial Handle","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
				}
		            	break;
		        case "77":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO Serial Close Handle","Serial Handle: ".$response[2]." Value: ".$response[4]);
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO Serial Close Handle","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));	
           			}
           			
		            	break;
		        case "80":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO Serial Read","Serial Handle: ".$response[2]." Value: ".substr($Message, -($response[4])));
           				If ($response[4] > 0) {
	           				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_data", "Value"=>utf8_encode(substr($Message, -($response[4]))) )));
           				}
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO Serial Read","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
  		            	break;
		        case "81":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO Serial Write","Serial Handle: ".$response[2]." Value: ".$response[4]);
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO Serial Write","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
  		            	break;
  		        case "82":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO Check Bytes Serial","Serial Handle: ".$response[2]." Bytes zum Lesen: ".$response[4]);
           				If ($response[4] > 0) {
           					// Einlesen der vorliegenden Daten
           					$this->CommandClientSocket(pack("L*", 80, GetValueInteger($this->GetIDForIdent("Serial_Handle")), $response[4], 0), 16 + $response[4]);
           				}
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO Check Bytes Serial","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
          			}
  		            	break;
		        case "97":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO GlitchFilter","gesetzt");
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO GlitchFilter","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
         
		            	break;
		        case "99":
           			If ($response[4] >= 0 ) {
           				IPS_LogMessage("IPS2GPIO Handle",$response[4]);
           				SetValueInteger($this->GetIDForIdent("Handle"), $response[4]);
           				
           				$this->ClientSocket(pack("LLLL", 19, $response[4], 134381568, 0));
           			}
           			else {
           				$this->ClientSocket(pack("LLLL", 99, 0, 0, 0));		
           			}
           			break;
			case "115":
           			If ($response[4] >= 0) {
           				IPS_LogMessage("IPS2GPIO Set Event Monitor","gesetzt");
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO Set Event Monitor","Fehler beim Setzen: ".$this->GetErrorText(abs($response[4])));
           			}
         
		            	break;
			case "116":
           			If ($response[4] >= 0) {
           				IPS_LogMessage("IPS2GPIO Trigger Event","gemeldet");
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO Trigger Event","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
         
		            	break;
		    }
	}
	
	public function PIGPIOD_Restart()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			// Verbindung trennen
			IPS_SetProperty($this->GetParentID(), "Open", false);
			IPS_ApplyChanges($this->GetParentID());
			// PIGPIO beenden und neu starten
			$this->SSH_Connect("sudo killall pigpiod");
			// Wartezeit
			IPS_Sleep(2000);
			$this->SSH_Connect("sudo pigpiod");
			// Wartezeit
			IPS_Sleep(2000);
			IPS_SetProperty($this->GetParentID(), "Open", true);
			IPS_ApplyChanges($this->GetParentID());			
		}
	}
	
	private function SSH_Connect(String $Command)
	{
	        If (($this->ReadPropertyBoolean("Open") == true) ) {
			set_include_path(__DIR__);
			require_once (__DIR__ . '/Net/SSH2.php');
			$ssh = new Net_SSH2($this->ReadPropertyString("IPAddress"));
			$login = @$ssh->login($this->ReadPropertyString("User"), $this->ReadPropertyString("Password"));
			if ($login == false)
			{
			    	IPS_LogMessage("IPS2GPIO SSH-Connect","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			    	$Result = "";
				return false;
			}
			$Result = $ssh->exec($Command);
			$ssh->disconnect();
		}
		else {
			$Result = "";
		}
	
        return $Result;
	}
	
	private function GetOneWireDevices()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			set_include_path(__DIR__);
			require_once (__DIR__ . '/Net/SFTP.php');
			$sftp = new Net_SFTP($this->ReadPropertyString("IPAddress"));
			$login = @$sftp->login($this->ReadPropertyString("User"), $this->ReadPropertyString("Password"));
			
			if ($login == false)
			{
			    	IPS_LogMessage("IPS2GPIO SFTP-Connect","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			    	$Result = "";
				return false;
			}
			//IPS_LogMessage("IPS2GPIO SFTP-Connect","Verbindung hergestellt");
			
			$Path = "/sys/bus/w1/devices";
			// Prüfen, ob der 1-Wire Server die Verzeichnisse angelegt hat
			if (!$sftp->file_exists($Path)) {
				IPS_LogMessage("IPS2GPIO SFTP-Connect",$Path." nicht gefunden! Ist 1-Wire aktiviert?");
				return;
			}
			
			// den Inhalt des Verzeichnisses ermitteln
			$Sensors = array();
			$Dir = $sftp->nlist($Path);
			for ($i = 0; $i < Count($Dir); $i++) {
				if ($Dir[$i] != "." && $Dir[$i] != ".." && $Dir[$i] != "w1_bus_master1") {
					$Sensors[] = $Dir[$i];
					//IPS_LogMessage("IPS2GPIO SFTP-Connect", $Dir[$i]);
				}
			}
			
			$Result = serialize($Sensors);
			
		}
		else {
			$ResultArray = Array();
			$Result = serialize($ResultArray);
		}
	return $Result;
	}
	
	private function ConnectionTest()
	{
	      $result = false;
	      If (Sys_Ping($this->ReadPropertyString("IPAddress"), 2000)) {
			IPS_LogMessage("IPS2GPIO Netzanbindung","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert");
			$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8888, $errno, $errstr, 10);
				if (!$status) {
					IPS_LogMessage("IPS2GPIO Netzanbindung: ","Port ist geschlossen!");
					// Versuchen PIGPIO zu starten
					IPS_LogMessage("IPS2GPIO Netzanbindung: ","Versuche PIGPIO per SSH zu starten...");
					$this->SSH_Connect("sudo pigpiod");
					$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8888, $errno, $errstr, 10);
					if (!$status) {
						IPS_LogMessage("IPS2GPIO Netzanbindung: ","Port ist geschlossen!");
						$this->SetStatus(104);
					}
					else {
						fclose($status);
						IPS_LogMessage("IPS2GPIO Netzanbindung: ","Port ist geöffnet");
						$result = true;
						$this->SetStatus(102);
					}
	   			}
	   			else {
	   				fclose($status);
					IPS_LogMessage("IPS2GPIO Netzanbindung: ","Port ist geöffnet");
					$result = true;
					$this->SetStatus(102);
	   			}
		}
		else {
			IPS_LogMessage("GPIO Netzanbindung: ","IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			$this->SetStatus(104);
		}
	return $result;
	}
	
	private function GetI2C_DeviceHandle(Int $DeviceAddress)
	{
		// Gibt für ein Device den verknüpften Handle aus
		$I2C_HandleData = unserialize(GetValueString($this->GetIDForIdent("I2C_Handle")));
 		If (array_key_exists($DeviceAddress, $I2C_HandleData)) {
 			$I2C_Handle = $I2C_HandleData[$DeviceAddress];
 		}
 		else {
 			$I2C_Handle = -1;	
 		}			  
	return $I2C_Handle;
	}
	
	private function GetI2C_HandleDevice(Int $I2C_Handle)
	{
		// Gibt für ein I2C-Device die Adresse aus
		$I2C_HandleData = unserialize(GetValueString($this->GetIDForIdent("I2C_Handle")));
 		If (array_search($I2C_Handle, $I2C_HandleData) == false) {
 			$I2C_Device = -1;
 		}
 		else {
 			$I2C_Device = array_search($I2C_Handle, $I2C_HandleData);	
 		}			  
	return $I2C_Device;
	}
	
	private function ResetI2CHandle()
	{
		$I2C_DeviceHandle = unserialize(GetValueString($this->GetIDForIdent("I2C_Handle")));
		If (is_array($I2C_DeviceHandle)) {			
			If  ((count($I2C_DeviceHandle) > 0) AND (max($I2C_DeviceHandle) > -1)) {
				for ($i = 0; $i <= max($I2C_DeviceHandle); $i++) {
					// Handle löschen
					$this->CommandClientSocket(pack("LLLL", 55, $i, 0, 0), 16);
				}
			}
		}
	}
	
	private function GetParentID()
	{
		$ParentID = (IPS_GetInstance($this->InstanceID)['ConnectionID']);  
	return $ParentID;
	}
  	
  	private function GetParentStatus()
	{
		$Status = (IPS_GetInstance($this->GetParentID())['InstanceStatus']);  
	return $Status;
	}
  	
	private function SetMUX($Port)
	{
		// PCA9542
		// 0 = No Channel selected
		// 4 = Channel 0
		// 5 = Channel 1
		$this->SetBuffer("MUX_Channel", $Port);
		If ($Port == 1) {
			$this->CommandClientSocket(pack("L*", 60, intval($this->GetI2C_DeviceHandle(240)), 0, 0), 16);
		}
		else {
			$this->CommandClientSocket(pack("L*", 60, intval($this->GetI2C_DeviceHandle(240)), $Port, 0), 16);
		}
	return;
	}
	
	private function GetOnboardI2CHandle($DeviceAddress)
	{
		// die genutzten Device Adressen anlegen
		$I2C_DeviceHandle = unserialize(GetValueString($this->GetIDForIdent("I2C_Handle")));
		// Bei Bus 1 Addition von 128
		$I2C_DeviceHandle[(1 << 7) + $DeviceAddress] = -1;
		// genutzte Device-Ident noch ohne Handle sichern
		SetValueString($this->GetIDForIdent("I2C_Handle"), serialize($I2C_DeviceHandle));
		// Handle ermitteln
		$this->CommandClientSocket(pack("L*", 54, 1, $DeviceAddress, 4, 0), 16);	
		   	
	}
	
  	private function GetErrorText(Int $ErrorNumber)
	{
		$ErrorMessage = array(1 => "PI_INIT_FAILED", 2 => "PI_BAD_USER_GPIO", 3 => "PI_BAD_GPIO", 4 => "PI_BAD_MODE", 5 => "PI_BAD_LEVEL", 6 => "PI_BAD_PUD", 7 => "PI_BAD_PULSEWIDTH",
			8 => "PI_BAD_DUTYCYCLE", 15 => "PI_BAD_WDOG_TIMEOUT", 21 => "PI_BAD_DUTYRANGE", 24 => "PI_NO_HANDLE", 25 => "PI_BAD_HANDLE",
			35 => "PI_BAD_WAVE_BAUD", 36 => "PI_TOO_MANY_PULSES", 37 => "PI_TOO_MANY_CHARS", 38 => "PI_NOT_SERIAL_GPIO", 41 => "PI_NOT_PERMITTED",
			42 => "PI_SOME_PERMITTED", 43 =>"PI_BAD_WVSC_COMMND", 44 => "PI_BAD_WVSM_COMMND", 45 =>"PI_BAD_WVSP_COMMND", 46 => "PI_BAD_PULSELEN",
			47 => "PI_BAD_SCRIPT", 48 => "PI_BAD_SCRIPT_ID", 49 => "PI_BAD_SER_OFFSET", 50 => "PI_GPIO_IN_USE", 51 =>"PI_BAD_SERIAL_COUNT",
			52 => "PI_BAD_PARAM_NUM", 53 => "PI_DUP_TAG", 54 => "PI_TOO_MANY_TAGS", 55 => "PI_BAD_SCRIPT_CMD", 56 => "PI_BAD_VAR_NUM",
			57 => "PI_NO_SCRIPT_ROOM", 58 => "PI_NO_MEMORY", 59 => "PI_SOCK_READ_FAILED", 60 => "PI_SOCK_WRIT_FAILED", 61 => "PI_TOO_MANY_PARAM",
			62 => "PI_SCRIPT_NOT_READY", 63 => "PI_BAD_TAG", 64 => "PI_BAD_MICS_DELAY", 65 => "PI_BAD_MILS_DELAY", 66 => "PI_BAD_WAVE_ID",
			67 => "PI_TOO_MANY_CBS", 68 => "PI_TOO_MANY_OOL", 69 => "PI_EMPTY_WAVEFORM", 70 => "PI_NO_WAVEFORM_ID", 71 => "PI_I2C_OPEN_FAILED",
			72 => "PI_SER_OPEN_FAILED", 73 => "PI_SPI_OPEN_FAILED", 74 => "PI_BAD_I2C_BUS", 75 => "PI_BAD_I2C_ADDR", 76 => "PI_BAD_SPI_CHANNEL",
			77 => "PI_BAD_FLAGS", 78 => "PI_BAD_SPI_SPEED", 79 => "PI_BAD_SER_DEVICE", 80 => "PI_BAD_SER_SPEED", 81 => "PI_BAD_PARAM",
			82 => "PI_I2C_WRITE_FAILED", 83 => "PI_I2C_READ_FAILED", 84 => "PI_BAD_SPI_COUNT", 85 => "PI_SER_WRITE_FAILED",
			86 => "PI_SER_READ_FAILED", 87 => "PI_SER_READ_NO_DATA", 88 => "PI_UNKNOWN_COMMAND", 89 => "PI_SPI_XFER_FAILED",
			91 => "PI_NO_AUX_SPI", 92 => "PI_NOT_PWM_GPIO", 93 => "PI_NOT_SERVO_GPIO", 94 => "PI_NOT_HCLK_GPIO", 95 => "PI_NOT_HPWM_GPIO",
			96 => "PI_BAD_HPWM_FREQ", 97 => "PI_BAD_HPWM_DUTY", 98 => "PI_BAD_HCLK_FREQ", 99 => "PI_BAD_HCLK_PASS", 100 => "PI_HPWM_ILLEGAL",
			101 => "PI_BAD_DATABITS", 102 => "PI_BAD_STOPBITS", 103 => "PI_MSG_TOOBIG", 104 => "PI_BAD_MALLOC_MODE", 107 => "PI_BAD_SMBUS_CMD",
			108 => "PI_NOT_I2C_GPIO", 109 => "PI_BAD_I2C_WLEN", 110 => "PI_BAD_I2C_RLEN", 111 => "PI_BAD_I2C_CMD", 112 => "PI_BAD_I2C_BAUD",
			113 => "PI_CHAIN_LOOP_CNT", 114 => "PI_BAD_CHAIN_LOOP", 115 => "PI_CHAIN_COUNTER", 116 => "PI_BAD_CHAIN_CMD", 117 => "PI_BAD_CHAIN_DELAY",
			118 => "PI_CHAIN_NESTING", 119 => "PI_CHAIN_TOO_BIG", 120 => "PI_DEPRECATED", 121 => "PI_BAD_SER_INVERT", 124 => "PI_BAD_FOREVER",
			125 => "PI_BAD_FILTER", 126 => "PI_BAD_PAD", 127 => "PI_BAD_STRENGTH", 128 => "PI_FIL_OPEN_FAILED", 129 => "PI_BAD_FILE_MODE",
			130 => "PI_BAD_FILE_FLAG", 131 => "PI_BAD_FILE_READ", 132 => "PI_BAD_FILE_WRITE", 133 => "PI_FILE_NOT_ROPEN",
			134 => "PI_FILE_NOT_WOPEN", 135 => "PI_BAD_FILE_SEEK", 136 => "PI_NO_FILE_MATCH", 137 => "PI_NO_FILE_ACCESS",
			138 => "PI_FILE_IS_A_DIR", 139 => "PI_BAD_SHELL_STATUS", 140 => "PI_BAD_SCRIPT_NAME", 141 => "PI_BAD_SPI_BAUD",
			142 => "PI_NOT_SPI_GPIO", 143 => "PI_BAD_EVENT_ID" );
		If (array_key_exists($ErrorNumber, $ErrorMessage)) {
			$ErrorText = $ErrorMessage[$ErrorNumber];
		}
		else {
			$ErrorText = "unknown Error -".$ErrorNumber;
		}
	return $ErrorText;
	}
  	
	private function GetHardware(Int $RevNumber)
	{
		$Hardware = array(2 => "Rev.0002 Model B PCB-Rev. 1.0 256MB", 3 => "Rev.0003 Model B PCB-Rev. 1.0 256MB", 4 => "Rev.0004 Model B PCB-Rev. 2.0 256MB Sony", 5 => "Rev.0005 Model B PCB-Rev. 2.0 256MB Qisda", 
			6 => "Rev.0006 Model B PCB-Rev. 2.0 256MB Egoman", 7 => "Rev.0007 Model A PCB-Rev. 2.0 256MB Egoman", 8 => "Rev.0008 Model A PCB-Rev. 2.0 256MB Sony", 9 => "Rev.0009 Model A PCB-Rev. 2.0 256MB Qisda",
			13 => "Rev.000d Model B PCB-Rev. 2.0 512MB Egoman", 14 => "Rev.000e Model B PCB-Rev. 2.0 512MB Sony", 15 => "Rev.000f Model B PCB-Rev. 2.0 512MB Qisda", 16 => "Rev.0010 Model B+ PCB-Rev. 1.0 512MB Sony",
			17 => "Rev.0011 Compute Module PCB-Rev. 1.0 512MB Sony", 18 => "Rev.0012 Model A+ PCB-Rev. 1.1 256MB Sony", 19 => "Rev.0013 Model B+ PCB-Rev. 1.2 512MB", 20 => "Rev.0014 Compute Module PCB-Rev. 1.0 512MB Embest",
			21 => "Rev.0015 Model A+ PCB-Rev. 1.1 256/512MB Embest", 10489920 => "Rev.a01040 2 Model B PCB-Rev. 1.0 1GB", 10489921 => "Rev.a01041 2 Model B PCB-Rev. 1.1 1GB Sony", 10620993 => "Rev.a21041 2 Model B PCB-Rev. 1.1 1GB Embest",
			10625090 => "Rev.a22042 2 Model B PCB-Rev. 1.2 1GB Embest", 9437330 => "Rev.900092 Zero PCB-Rev. 1.2 512MB Sony", 9437331 => "Rev.900093 Zero PCB-Rev. 1.3 512MB Sony", 10494082 => "Rev.a02082 3 Model B PCB-Rev. 1.2 1GB Sony",
			10625154 => "Rev.a22082 3 Model B PCB-Rev. 1.2 1GB Embest", 44044353 => "Rev.2a01041 2 Model B PCB-Rev. 1.1 1GB Sony (overvoltage)");
		If (array_key_exists($RevNumber, $Hardware)) {
			$HardwareText = $Hardware[$RevNumber];
		}
		else {
			$HardwareText = "Unbekannte Revisions Nummer!";
		}
		// Einige Besonderheiten setzen
		If ($RevNumber <= 3) {
			$this->SetBuffer("Default_I2C_Bus", 0);
		}
		else {
			$this->SetBuffer("Default_I2C_Bus", 1);
		}
		If (($RevNumber == 10494082) OR ($RevNumber == 10625154)) {
			$this->SetBuffer("Default_Serial_Bus", 1);
		}
		else {
			$this->SetBuffer("Default_Serial_Bus", 0);
		}
	return $HardwareText;
	}
}
?>
