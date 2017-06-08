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
		$this->RegisterPropertyInteger("GlitchFilter", 100);
		$this->RegisterPropertyString("I2C_Devices", "");
		$this->RegisterPropertyInteger("TimeCorrection", 100);
		$this->RegisterPropertyString("OW_Devices", "");
		$this->RegisterPropertyString("Raspi_Config", "");
		$this->RegisterPropertyInteger("Baud", 9600);
            	$this->RegisterPropertyString("ConnectionString", "/dev/serial0");
		$this->RegisterTimer("RTC_Data", 0, 'GeCoSIO_GetRTC_Data($_IPS["TARGET"]);');
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
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
 		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "IPAddress", "caption" => "IP");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Zugriffsdaten des Raspberry Pi SSH:");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "User", "caption" => "User");
		$arrayElements[] = array("type" => "PasswordTextBox", "name" => "Password", "caption" => "Password");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Analyse der Raspberry Pi Konfiguration:");
		$arraySort = array();
		$arraySort[] = array("column" => "Typ", "direction" => "ascending");
		$arrayColumns = array();
		$arrayColumns[] = array("label" => "Service", "name" => "ServiceTyp", "width" => "200px", "add" => "");
		$arrayColumns[] = array("label" => "Status", "name" => "ServiceStatus", "width" => "auto", "add" => "");
		$ServiceArray = array();
		$ServiceArray = unserialize($this->CheckConfig());
		$arrayValues[] = array("ServiceTyp" => "I²C", "ServiceStatus" => $ServiceArray["I2C"]["Status"], "rowColor" => $ServiceArray["I2C"]["Color"]);
		$arrayValues[] = array("ServiceTyp" => "Serielle Schnittstelle (RS232)", "ServiceStatus" => $ServiceArray["Serielle Schnittstelle"]["Status"], "rowColor" => $ServiceArray["Serielle Schnittstelle"]["Color"]);
		$arrayValues[] = array("ServiceTyp" => "Shell Zugriff", "ServiceStatus" => $ServiceArray["Shell Zugriff"]["Status"], "rowColor" => $ServiceArray["Shell Zugriff"]["Color"]);
		$arrayValues[] = array("ServiceTyp" => "PIGPIO Server", "ServiceStatus" => $ServiceArray["PIGPIO Server"]["Status"], "rowColor" => $ServiceArray["PIGPIO Server"]["Color"]);
		
		$arrayElements[] = array("type" => "List", "name" => "Raspi_Config", "caption" => "Konfiguration", "rowCount" => 4, "add" => false, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);
	
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Filter zum Entprellen angeschlossener Taster und Schalter setzen (0-5000ms):");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "GlitchFilter", "caption" => "Glitchfilter (ms)");
		//$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		
		$arrayColumns = array();
		$arrayColumns[] = array("label" => "Typ", "name" => "DeviceTyp", "width" => "120px", "add" => "");
		$arrayColumns[] = array("label" => "Adresse", "name" => "DeviceAddress", "width" => "60px", "add" => "");
		$arrayColumns[] = array("label" => "Bus", "name" => "DeviceBus", "width" => "60px", "add" => "");
		$arrayColumns[] = array("label" => "Instanz ID", "name" => "InstanceID", "width" => "70px", "add" => "");
		$arrayColumns[] = array("label" => "Status", "name" => "DeviceStatus", "width" => "auto", "add" => "");
		
		$arrayOWColumns = array();
		$arrayOWColumns[] = array("label" => "Typ", "name" => "DeviceTyp", "width" => "120px", "add" => "");
		$arrayOWColumns[] = array("label" => "Serien-Nr.", "name" => "DeviceSerial", "width" => "120px", "add" => "");
		$arrayOWColumns[] = array("label" => "Instanz ID", "name" => "InstanceID", "width" => "70px", "add" => "");
		$arrayOWColumns[] = array("label" => "Status", "name" => "DeviceStatus", "width" => "auto", "add" => "");
		
		
		If (($this->ConnectionTest()) AND ($this->ReadPropertyBoolean("Open") == true))  {
			// I²C-Devices einlesen und in das Values-Array kopieren
			$DeviceArray = array();
			$DeviceArray = unserialize($this->SearchI2CDevices());
			$arrayValues = array();
			If (count($DeviceArray , COUNT_RECURSIVE) >= 4) {
				for ($i = 0; $i < Count($DeviceArray); $i++) {
					$arrayValues[] = array("DeviceTyp" => $DeviceArray[$i][0], "DeviceAddress" => $DeviceArray[$i][1], "DeviceBus" => $DeviceArray[$i][2], "InstanceID" => $DeviceArray[$i][3], "DeviceStatus" => $DeviceArray[$i][4], "rowColor" => $DeviceArray[$i][5]);
				}
				$arrayElements[] = array("type" => "List", "name" => "I2C_Devices", "caption" => "I²C-Devices", "rowCount" => 5, "add" => false, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);
			}
			else {
				$arrayElements[] = array("type" => "Label", "label" => "Es wurden keine I²C-Devices gefunden.");
			}
			$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
			// 1-Wire-Devices einlesen und in das Values-Array kopieren
			$OWDeviceArray = array();
			$this->OWSearchStart();
			$OWDeviceArray = unserialize($this->GetBuffer("OWDeviceArray"));
			If (count($OWDeviceArray , COUNT_RECURSIVE) >= 4) {
				$arrayElements[] = array("type" => "Label", "label" => "Lesezeit der 1-Wire-Devices verändern:");
				$arrayElements[] = array("type" => "NumberSpinner", "name" => "TimeCorrection", "caption" => "Zeitkorrektur (%)");
				$arrayOWValues = array();
				for ($i = 0; $i < Count($OWDeviceArray); $i++) {
					$arrayOWValues[] = array("DeviceTyp" => $OWDeviceArray[$i][0], "DeviceSerial" => $OWDeviceArray[$i][1], "InstanceID" => $OWDeviceArray[$i][2], "DeviceStatus" => $OWDeviceArray[$i][3], "rowColor" => $OWDeviceArray[$i][4]);
				}
				$arrayElements[] = array("type" => "List", "name" => "OW_Devices", "caption" => "1-Wire-Devices", "rowCount" => 5, "add" => false, "delete" => false, "sort" => $arraySort, "columns" => $arrayOWColumns, "values" => $arrayOWValues);
			}
			else {
				$arrayElements[] = array("type" => "Label", "label" => "Es wurden keine 1-Wire-Devices gefunden.");
			}
			//$arrayElements[] = array("type" => "Button", "label" => "I²C-Devices einlesen", "onClick" => 'GeCoSIO_SearchI2CDevices($id);');
			$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
			$arrayElements[] = array("type" => "Label", "label" => "Führt einen Restart des PIGPIO aus:");
			$arrayElements[] = array("type" => "Button", "label" => "PIGPIO Restart", "onClick" => 'GeCoSIO_PIGPIOD_Restart($id);');
			$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
			$arrayElements[] = array("type" => "Label", "label" => "Setzen der Real-Time-Clock auf IPS-Zeit:");
			$arrayElements[] = array("type" => "Button", "label" => "RTC setzen", "onClick" => 'GeCoSIO_SetRTC_Data($id);');		
		}
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Definition der seriellen Schnittstelle (RS232):");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "2400", "value" => 2400);
		$arrayOptions[] = array("label" => "4800", "value" => 4800);
		$arrayOptions[] = array("label" => "9600", "value" => 9600);
		$arrayOptions[] = array("label" => "19200", "value" => 19200);
		$arrayOptions[] = array("label" => "38400", "value" => 38400);
		$arrayOptions[] = array("label" => "57600", "value" => 57600);
		$arrayOptions[] = array("label" => "115200", "value" => 115200);
		$arrayElements[] = array("type" => "Select", "name" => "Baud", "caption" => "Baud", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "Connection String der seriellen Schnittstelle (z.B. /dev/serial0):");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "ConnectionString", "caption" => "Connection String");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "label" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/'");
		
		$arrayActions = array();
		If ($this->ReadPropertyBoolean("Open") == true) {   
			$arrayActions[] = array("type" => "Label", "label" => "Aktuell sind keine Testfunktionen definiert");
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
			$this->RegisterVariableString("Hardware", "Hardware", "", 107);
			$this->DisableAction("Hardware");
			IPS_SetHidden($this->GetIDForIdent("Hardware"), true);
			
			$this->RegisterVariableInteger("SoftwareVersion", "SoftwareVersion", "", 108);
			$this->DisableAction("SoftwareVersion");
			IPS_SetHidden($this->GetIDForIdent("SoftwareVersion"), true);
			
			$this->RegisterVariableFloat("RTC_Temperature", "RTC Temperatur", "~Temperature", 110);
			$this->DisableAction("RTC_Temperature");
			IPS_SetHidden($this->GetIDForIdent("RTC_Temperature"), false);
			
			$this->RegisterVariableInteger("RTC_Timestamp", "RTC Zeitstempel", "~UnixTimestamp", 140);
			$this->DisableAction("RTC_Timestamp");
			IPS_SetHidden($this->GetIDForIdent("RTC_Timestamp"), false);
		
			$this->SetBuffer("Default_Serial_Bus", 0);
			$this->SetBuffer("MUX_Handle", -1);
			$this->SetBuffer("MUX_Channel", 1);
			$this->SetBuffer("RTC_Handle", -1);
			$this->SetBuffer("Serial_Handle", -1);
			$this->SetBuffer("OW_Handle", -1);
			
			$this->SetBuffer("owLastDevice", 0);
			$this->SetBuffer("owLastDiscrepancy", 0);
			$this->SetBuffer("owTripletDirection", 1);
			$this->SetBuffer("owTripletFirstBit", 0);
			$this->SetBuffer("owTripletSecondBit", 0);
			$this->SetBuffer("owDeviceAddress_0", 0);
			$this->SetBuffer("owDeviceAddress_1", 0);

			$ParentID = $this->GetParentID();
			
			If ($ParentID > 0) {
				If (IPS_GetProperty($ParentID, 'Host') <> $this->ReadPropertyString('IPAddress')) {
		                	IPS_SetProperty($ParentID, 'Host', $this->ReadPropertyString('IPAddress'));
				}
				If (IPS_GetProperty($ParentID, 'Port') <> 8888) {
		                	IPS_SetProperty($ParentID, 'Port', 8888);
				}
				If (IPS_GetProperty($ParentID, 'Open') <> $this->ReadPropertyBoolean("Open")) {
		                	IPS_SetProperty($ParentID, 'Open', $this->ReadPropertyBoolean("Open"));
				}
				If (IPS_GetName($ParentID) == "Client Socket") {
		                	IPS_SetName($ParentID, "GeCoS");
				}
				if(IPS_HasChanges($ParentID))
				{
				    IPS_ApplyChanges($ParentID);
				}
			}
						
		        // Änderung an den untergeordneten Instanzen
		        $this->RegisterMessage($this->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
		        $this->RegisterMessage($this->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
		        // INSTANCEMESSAGE
		        $this->RegisterMessage($ParentID, 10505); // Status hat sich geändert
			
			
			If (($this->ConnectionTest()) AND ($this->ReadPropertyBoolean("Open") == true))  {
				$this->SendDebug("ApplyChangges", "Starte Vorbereitung", 0);
				$this->CheckConfig();
				// Hardware und Softwareversion feststellen
				$this->CommandClientSocket(pack("LLLL", 17, 0, 0, 0).pack("LLLL", 26, 0, 0, 0), 32);
				
				// I2C-Handle zurücksetzen
				$this->ResetI2CHandle(0);
				
				// Serial-Handle zurücksetzen
				$this->ResetSerialHandle();
				
				// Notify Starten
				$this->SetBuffer("Handle", -1);
				$Handle = $this->ClientSocket(pack("L*", 99, 0, 0, 0));
				$this->SetBuffer("Handle", $Handle);
				If ($Handle >= 0) {
					// I²C Bus 1 für RTC, Serielle Schnittstelle,
					//Notify Pin 17 + 27 + 15= Bitmask 134381568
					$this->CommandClientSocket(pack("L*", 19, $this->GetBuffer("Handle"), 134381568, 0), 16);
				}
				
				// GlitchFilter setzen
				$GlitchFilter = min(5000, max(0, $this->ReadPropertyInteger('GlitchFilter')));
				$this->CommandClientSocket(pack("L*", 97, 17, $GlitchFilter, 0).pack("L*", 97, 27, $GlitchFilter, 0) , 32);
				
				// RTC einrichten
				$RTC_Handle = $this->GetOnboardI2CHandle(104);
				$this->SetBuffer("RTC_Handle", $RTC_Handle);
				$this->SendDebug("RTC Handle", $RTC_Handle, 0);
				
				// 1-Wire einrichten
				$OW_Handle = $this->GetOnboardI2CHandle(24);
				$this->SetBuffer("OW_Handle", $OW_Handle);
				$this->SendDebug("OW_Handle", $OW_Handle, 0);
				If ($OW_Handle >= 0) {
					// DS 2482 zurücksetzen
					$this->DS2482Reset();
					$this->OWSearchStart();
				}
				// https://pastebin.com/0d93ZuRb
				
				// MUX einrichten
				$MUX_Handle = $this->GetOnboardI2CHandle(112);
				$this->SetBuffer("MUX_Handle", $MUX_Handle);
				$this->SendDebug("MUX Handle", $MUX_Handle, 0);
				If ($MUX_Handle >= 0) {
					// MUX setzen
					$this->SetMUX(1);
				}
				
				$SerialHandle = $this->CommandClientSocket(pack("L*", 76, $this->ReadPropertyInteger('Baud'), 0, strlen($this->ReadPropertyString('ConnectionString')) ).$this->ReadPropertyString('ConnectionString'), 16);
				$this->SetBuffer("Serial_Handle", $SerialHandle);
				$this->SendDebug("Serial Handle", $SerialHandle, 0);
				
				$this->Get_PinUpdate();
				$this->SetStatus(102);
				$this->SetTimerInterval("RTC_Data", 15 * 1000);
			}
			else {
				$this->SetTimerInterval("RTC_Data", 0);
				$this->SetStatus(104);
			}
		}
		else {
			return;
		}
	}
	
	public function GetConfigurationForParent()
	{
	  	$JsonArray = array( "Host" => $this->ReadPropertyString('IPAddress'), "Port" => 8888, "Open" => $this->ReadPropertyBoolean("Open"));
	  	$Json = json_encode($JsonArray);        
	  	return $Json;
	}  
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		$this->SendDebug("MessageSink", "Message from SenderID ".$SenderID." with Message ".$Message."\r\n Data: ".print_r($Data), 0);
			
		switch ($Message) {
			case 10100:
				If ($Data[0] == 10103) {
					$this->ApplyChanges();
				}
				break;
			case 11101:
				$this->SendDebug("MessageSink", "Instanz ".$SenderID." wurde verbunden", 0);
				break;
			case 11102:
				$this->SendDebug("MessageSink", "Instanz ".$SenderID." wurde getrennt", 0);
				$I2CInstanceArray = Array();
				$I2CInstanceArray = unserialize($this->GetBuffer("I2CInstanceArray"));
				If (array_key_exists($SenderID, $I2CInstanceArray)) {
					unset ($I2CInstanceArray[$SenderID]);
				}
				$this->SetBuffer("I2CInstanceArray", serialize($I2CInstanceArray));
				$OWInstanceArray = Array();
				$OWInstanceArray = unserialize($this->GetBuffer("OWInstanceArray"));
				If (array_key_exists($SenderID, $OWInstanceArray)) {
					unset ($OWInstanceArray[$SenderID]);
				}
				$this->SetBuffer("OWInstanceArray", serialize($OWInstanceArray));
				$this->UnregisterMessage($SenderID, 11101);
				$this->UnregisterMessage($SenderID, 11102);
				break;				
			case 10505:
				$this->SendDebug("MessageSink", "Uebergeordnete Instanz ".$SenderID." meldet Status ".$Data[0], 0);
				If ($Data[0] == 102) {
					$this->ApplyChanges();
				}
				elseif (($Data[0] == 200) AND ($this->ReadPropertyBoolean("Open") == true)) {
					$this->ConnectionTest();
				}
				break;
		}
		
    	}
	  
	 public function ForwardData($JSONString) 
	 {
		 // Empfangene Daten von der Device Instanz
	    	 $data = json_decode($JSONString);
	    	 $I2CInstanceArray = Array();
		 $I2CInstanceArray = unserialize($this->GetBuffer("I2CInstanceArray"));
		 $OWInstanceArray = Array();
		 $OWInstanceArray = unserialize($this->GetBuffer("OWInstanceArray"));
	 	
		 switch ($data->Function) {
		 // interne Kommunikation
		
		   	case "get_pinupdate":
				$this->Get_PinUpdate();
				break;
		   
		   	// I2C Kommunikation
		   	case "set_used_i2c":		   	
				// die genutzten Device Adressen anlegen
				$I2CInstanceArray[$data->InstanceID]["DeviceBus"] = $data->DeviceBus;
				$I2CInstanceArray[$data->InstanceID]["DeviceAddress"] = $data->DeviceAddress;
				$I2CInstanceArray[$data->InstanceID]["Status"] = "Angemeldet";
				// MUX auf den erforderlichen Channel stellen
				$this->SetMUX($data->DeviceBus);
				$Handle = $this->CommandClientSocket(pack("L*", 54, 1, $data->DeviceAddress, 4, 0), 16);
				$I2CInstanceArray[$data->InstanceID]["Handle"] = $Handle;
				$this->SetBuffer("I2CInstanceArray", serialize($I2CInstanceArray));
				// Testweise lesen
				If ($Handle >= 0) {
					$Result = $this->CommandClientSocket(pack("L*", 59, $Handle, 0, 0), 16);
					If ($Result >= 0) {
						$this->SendDebug("Set Used I2C", "Test-Lesen auf Device-Adresse ".$data->DeviceAddress." Bus ".($data->DeviceBus - 4)." erfolgreich!", 0);
						$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"status", "InstanceID" => $data->InstanceID, "Status" => 102)));
					}
					else {
						$this->SendDebug("Set Used I2C", "Test-Lesen auf Device-Adresse ".$data->DeviceAddress." Bus ".($data->DeviceBus - 4)." nicht erfolgreich!", 0);
						IPS_LogMessage("GeCoS_IO", "Test-Lesen auf Device-Adresse ".$data->DeviceAddress." Bus ".($data->DeviceBus - 4)." nicht erfolgreich!");
						$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"status", "InstanceID" => $data->InstanceID, "Status" => 201)));
					}		
				}
				// Messages einrichten
				$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden
				$this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt
				break;
		
			case "i2c_read_bytes":
				// I2CRD h num - i2c Read bytes
				If ($I2CInstanceArray[$data->InstanceID]["Handle"] >= 0) {
					$this->SetMUX($I2CInstanceArray[$data->InstanceID]["DeviceBus"]);
					$this->CommandClientSocket(pack("L*", 56, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Count, 0), 16 + ($data->Count));
				}
				break;  
			case "i2c_write_bytes":
				// I2CWD h bvs - i2c Write data
				If ($I2CInstanceArray[$data->InstanceID]["Handle"] >= 0) {
					$this->SetMUX($I2CInstanceArray[$data->InstanceID]["DeviceBus"]);
					$ByteArray = array();
					$ByteArray = unserialize($data->ByteArray);
					$this->CommandClientSocket(pack("L*", 57, $I2CInstanceArray[$data->InstanceID]["Handle"], 0, count($ByteArray)).pack("C*", ...$ByteArray), 16);
				}
				break;	
			case "i2c_4AnalogIn":
				// I2CWS h bv - smb Write Byte: write byte
				If ($I2CInstanceArray[$data->InstanceID]["Handle"] >= 0) {
					$Result = $this->CommandClientSocket(pack("L*", 60, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Value, 0), 16);
					If ($Result >= 0) {
						IPS_Sleep($data->Time);
						$this->CommandClientSocket(pack("L*", 56, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Count, 0), 16 + ($data->Count));
					}
				}
				break;	
			 
			 
			case "i2c_read_byte":
		   		// I2CRB h r - smb Read Byte Data: read byte from register
				If ($I2CInstanceArray[$data->InstanceID]["Handle"] >= 0) {
					$this->SetMUX($I2CInstanceArray[$data->InstanceID]["DeviceBus"]);
					$this->CommandClientSocket(pack("L*", 61, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register, 0), 16);
				}
		   		break;
			case "i2c_read_2_byte":
		   		// I2CRB h r - smb Read Byte Data: read byte from register
				If ($I2CInstanceArray[$data->InstanceID]["Handle"] >= 0) {
					$this->SetMUX($I2CInstanceArray[$data->InstanceID]["DeviceBus"]);
					$this->CommandClientSocket(pack("L*", 61, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register, 0).
								   pack("L*", 61, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 1, 0), 32);
				}
		   		break;
			case "i2c_read_6_byte":
		   		// I2CRB h r - smb Read Byte Data: read byte from register
				If ($I2CInstanceArray[$data->InstanceID]["Handle"] >= 0) {
					$this->SetMUX($I2CInstanceArray[$data->InstanceID]["DeviceBus"]);
					$this->CommandClientSocket(pack("L*", 61, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register, 0).
								   pack("L*", 61, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 1, 0).
								   pack("L*", 61, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 4, 0).
								   pack("L*", 61, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 5, 0).
								   pack("L*", 61, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 8, 0).
								   pack("L*", 61, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 9, 0), 96);
				}
		   		break;
			case "i2c_write_byte":
		   		// I2CWB h r bv - smb Write Byte Data: write byte to register  	
				If ($I2CInstanceArray[$data->InstanceID]["Handle"] >= 0) {
					$this->SetMUX($I2CInstanceArray[$data->InstanceID]["DeviceBus"]);
					$this->CommandClientSocket(pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register, 4, $data->Value), 16);
				}
		   		break;
			case "i2c_write_4_byte":
		   		// I2CWB h r bv - smb Write Byte Data: write byte to register  	
				If ($I2CInstanceArray[$data->InstanceID]["Handle"] >= 0) {
					$this->SetMUX($I2CInstanceArray[$data->InstanceID]["DeviceBus"]);
					$this->CommandClientSocket(pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register, 4, $data->Value_1).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 1, 4, $data->Value_2).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 2, 4, $data->Value_3).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 3, 4, $data->Value_4), 64);
				}
		   		break;
			case "i2c_write_12_byte":
		   		// I2CWB h r bv - smb Write Byte Data: write byte to register  	
				If ($I2CInstanceArray[$data->InstanceID]["Handle"] >= 0) {
					$this->SetMUX($I2CInstanceArray[$data->InstanceID]["DeviceBus"]);
					$this->CommandClientSocket(pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register, 4, $data->Value_1).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 1, 4, $data->Value_2).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 2, 4, $data->Value_3).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 3, 4, $data->Value_4).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 4, 4, $data->Value_5).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 5, 4, $data->Value_6).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 6, 4, $data->Value_7).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 7, 4, $data->Value_8).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 8, 4, $data->Value_9).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 9, 4, $data->Value_10).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 10, 4, $data->Value_11).
								   pack("L*", 62, $I2CInstanceArray[$data->InstanceID]["Handle"], $data->Register + 11, 4, $data->Value_12), 192);
				}
		   		break;
			// Serielle Kommunikation
			case "serial_write":
				$Message = utf8_decode($data->Message);
				$this->WriteSerial($Message);
				break;
			
		    
		    	// 1-Wire
		    	case "get_OWDevices":
				$j = 0;
				$OWDeviceArray = array();
				$this->OWSearchStart();
				$OWDeviceArray = unserialize($this->GetBuffer("OWDeviceArray"));
				$DeviceSerialArray = array();
				If (count($OWDeviceArray ,COUNT_RECURSIVE) >= 4) {
					for ($i = 0; $i < Count($OWDeviceArray); $i++) {
						$DeviceSerial = $OWDeviceArray[$i][1];
						$FamilyCode = substr($DeviceSerial, -2);
						If (($FamilyCode == $data->FamilyCode) AND ($OWDeviceArray[$i][2] == 0)) {
							$DeviceSerialArray[$j][0] = $DeviceSerial; // DeviceAdresse
							$DeviceSerialArray[$j][1] = $OWDeviceArray[$i][5]; // Erster Teil der Adresse
							$DeviceSerialArray[$j][2] = $OWDeviceArray[$i][6]; // Zweiter Teil der Adresse
							$j = $j + 1;
						}
					}
				}
				$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_OWDevices", "InstanceID" => $data->InstanceID, "Result"=>serialize($DeviceSerialArray) ))); 
				break;
		  	case "set_OWDevices":
				// die genutzten Device Adressen anlegen
				$OWInstanceArray[$data->InstanceID]["DeviceSerial"] = $data->DeviceSerial;
				 
				$OWDeviceArray = array();
				$OWDeviceArray = unserialize($this->GetBuffer("OWDeviceArray"));
				If (count($OWDeviceArray , COUNT_RECURSIVE) >= 4) {
					for ($i = 0; $i < Count($OWDeviceArray); $i++) {
						If ($OWDeviceArray[$i][1] == $data->DeviceSerial) {
							$OWInstanceArray[$data->InstanceID]["Address_0"] = $OWDeviceArray[$i][5];
				 			$OWInstanceArray[$data->InstanceID]["Address_1"] = $OWDeviceArray[$i][6];
						}
					}
				}
				else {
				 	$OWInstanceArray[$data->InstanceID]["Address_0"] = 0;
				 	$OWInstanceArray[$data->InstanceID]["Address_1"] = 0;	
				}
				
				 $OWInstanceArray[$data->InstanceID]["Status"] = "Angemeldet";
				 $this->SetBuffer("OWInstanceArray", serialize($OWInstanceArray));
				 // Messages einrichten
				 $this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden
				 $this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt
				 break;
			case "get_DS18S20Temperature":
				if (IPS_SemaphoreEnter("DS18S20Temperature", 2000))
				{
					$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
					$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);

				 	if ($this->OWReset()) { //Reset was successful
						$this->OWSelect();
						$this->OWWriteByte(0x44); //start conversion
						$TimeCorrection = $this->ReadPropertyInteger("TimeCorrection") / 100;
						IPS_Sleep($data->Time * $TimeCorrection); //Wait for conversion
						
						$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
						$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);
						
						if ($this->OWReset()) { //Reset was successful
							$this->OWSelect();
							$this->OWWriteByte(0xBE); //Read Scratchpad
							$Celsius = $this->OWRead_18S20_Temperature();
							$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_DS18S20Temperature", "InstanceID" => $data->InstanceID, "Result"=>$Celsius )));
						}
						
            				}
					IPS_SemaphoreLeave("DS18S20Temperature");
				}
				else {
					$this->SendDebug("DS18S20Temperature", "Semaphore Abbruch", 0);
				}	
				break;
			 case "get_DS18B20Temperature":
				if (IPS_SemaphoreEnter("DS18B20Temperature", 2000))
				{
					$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
					$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);

					 if ($this->OWReset()) { //Reset was successful
						$this->OWSelect();
						$this->OWWriteByte(0x44); //start conversion
						$TimeCorrection = $this->ReadPropertyInteger("TimeCorrection") / 100;	
						IPS_Sleep($data->Time * $TimeCorrection); //Wait for conversion
						 
						$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
						$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);
						 
						if ($this->OWReset()) { //Reset was successful
							$this->OWSelect();
							$this->OWWriteByte(0xBE); //Read Scratchpad
							$Celsius = $this->OWRead_18B20_Temperature(); 
							$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_DS18B20Temperature", "InstanceID" => $data->InstanceID, "Result"=>$Celsius )));
						}
						
					}
					IPS_SemaphoreLeave("DS18B20Temperature");
				}
				else {
					$this->SendDebug("DS18B20Temperature", "Semaphore Abbruch", 0);
				}	
 				break;
			case "set_DS18B20Setup":
				if (IPS_SemaphoreEnter("DS18B20Setup", 2000))
				{
					$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
					$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);

					 if ($this->OWReset()) { //Reset was successful
						$this->OWSelect();
						$this->OWWriteByte(78); 
						$this->OWWriteByte(0); 
						$this->OWWriteByte(0); 
						$this->OWWriteByte($data->Resolution); 
					}
					IPS_SemaphoreLeave("DS18B20Setup");
				}
				else {
					$this->SendDebug("DS18B20Setup", "Semaphore Abbruch", 0);
				}	
 				break;
			case "get_DS2413State":
				if (IPS_SemaphoreEnter("DS2413State", 2000))
				{
					$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
					$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);

					 if ($this->OWReset()) { //Reset was successful
						$this->OWSelect();
						$this->OWWriteByte(0xF5); //PIO ACCESS READ
						$Result = $this->OWRead_2413_State();	
						$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_DS2413State", "InstanceID" => $data->InstanceID, "Result"=>$Result )));
					}
					IPS_SemaphoreLeave("DS2413State");
				}
				else {
					$this->SendDebug("DS2413State", "Semaphore Abbruch", 0);
				}	
 				break;
			case "set_DS2413Setup":
				if (IPS_SemaphoreEnter("DS2413Setup", 2000))
				{
					$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
					$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);

					 if ($this->OWReset()) { //Reset was successful
						$this->OWSelect();
						$this->OWWriteByte(0x5A); //PIO ACCESS WRITE
						$Value = $data->Setup;
						$this->OWWriteByte($Value); 
						$this->OWWriteByte($Value ^ 0xFF); 
					 }
					IPS_SemaphoreLeave("DS2413Setup");
				}
				else {
					$this->SendDebug("DS2413Setup", "Semaphore Abbruch", 0);
				}	
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
	    		for ($i = 0; $i < min(2, Count($DataArray)); $i++) {
				$MessageParts = unpack("L*", $DataArray[$i]);
				
				// Wert von Pin 17
				$Bitvalue_17 = boolval($MessageParts[3]&(1<<17));
				//IPS_LogMessage("GeCoS_IO", "Bit 17: ".$Bitvalue_17);
				$this->SendDebug("ReceiveData", "Bit 17: ".$Bitvalue_17, 0);
				$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"interrupt", "DeviceBus" => 4)));
				
				// Wert von Pin 27
				$Bitvalue_27 = boolval($MessageParts[3]&(1<<27));
				//IPS_LogMessage("GeCoS_IO", "Bit 27: ".$Bitvalue_27);
				$this->SendDebug("ReceiveData", "Bit 27: ".$Bitvalue_27, 0);
				$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"interrupt", "DeviceBus" => 5)));
				
				// Wert von Pin 15
				$Bitvalue_15 = boolval($MessageParts[3]&(1<<15));
				//IPS_LogMessage("GeCoS_IO", "Bit 15: ".$Bitvalue_15);
				$this->SendDebug("ReceiveData", "Bit 15: ".$Bitvalue_15, 0);
				IPS_Sleep(75);
				$this->CheckSerial();
				
			}
		}
	 	else {
	 		// Prüfen ob Daten im Serial Buffer vorhanden sind
			IPS_Sleep(75);
			$this->CommandClientSocket(pack("L*", 82, GetValueInteger($this->GetIDForIdent("Serial_Handle")), 0, 0), 16);
	 	}
	 }
 
	// Aktualisierung der genutzten Pins und der Notifikation
	private function Get_PinUpdate()
	{
		// I2C-Handle zurücksetzen
		$MUX_Handle = $this->GetBuffer("MUX_Handle");
		$this->ResetI2CHandle($MUX_Handle + 1);
				
		// Konfiguration der I²C-Pin
		$this->CommandClientSocket(pack("LLLL", 0, 2, 4, 0).pack("LLLL", 0, 3, 4, 0), 32);
		
		// Raspberry Pi 3 = Alt5(Rxd1/TxD1) => 2
		// Alle anderen = Alt0(Rxd0/TxD0) => 4
		If ($this->GetBuffer("Default_Serial_Bus") == 0) {
			$this->CommandClientSocket(pack("LLLL", 0, 14, 4, 0).pack("LLLL", 0, 15, 4, 0), 32);
		}
		elseif ($this->GetBuffer("Default_Serial_Bus") == 1) {
			// Beim Raspberry Pi 3 ist Bus 0 schon durch die Bluetooth-Schnittstelle belegt
			$this->CommandClientSocket(pack("LLLL", 0, 14, 2, 0).pack("LLLL", 0, 15, 2, 0), 32);
		}
		
		// Starttrigger für 1-Wire-Instanzen
		$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"get_start_trigger")));

		// Ermitteln der genutzten I2C-Adressen
		$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"get_used_i2c")));
		
	}
	
	private function ClientSocket(String $message)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$res = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => utf8_encode($message)))); 
		}
	}
	
	private function CommandClientSocket(String $message, $ResponseLen = 16)
	{
		$Result = -999;
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			
			if (IPS_SemaphoreEnter("CommandClientSocket", 100))
			{
				// Socket erstellen
				if(!($sock = socket_create(AF_INET, SOCK_STREAM, 0))) {
					$errorcode = socket_last_error();
					$errormsg = socket_strerror($errorcode);
					IPS_LogMessage("GeCoS_IO Socket", "Fehler beim Erstellen ".$errorcode." ".$errormsg);
					$this->SendDebug("CommandClientSocket", "Fehler beim Erstellen ".$errorcode." ".$errormsg, 0);
					return;
				}
				// Timeout setzen
				socket_set_option($sock,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>2, "usec"=>0));
				// Verbindung aufbauen
				if(!(socket_connect($sock, $this->ReadPropertyString("IPAddress"), 8888))) {
					$errorcode = socket_last_error();
					$errormsg = socket_strerror($errorcode);
					IPS_LogMessage("GeCoS_IO Socket", "Fehler beim Verbindungsaufbaus ".$errorcode." ".$errormsg);
					$this->SendDebug("CommandClientSocket", "Fehler beim Verbindungsaufbaus ".$errorcode." ".$errormsg, 0);
					return;
				}
				// Message senden
				if( ! socket_send ($sock, $message, strlen($message), 0))
				{
					$errorcode = socket_last_error();
					$errormsg = socket_strerror($errorcode);
					IPS_LogMessage("GeCoS_IO Socket", "Fehler beim beim Senden ".$errorcode." ".$errormsg);
					$this->SendDebug("CommandClientSocket", "Fehler beim beim Senden ".$errorcode." ".$errormsg, 0);
					return;
				}
				//Now receive reply from server
				if(socket_recv ($sock, $buf, $ResponseLen, MSG_WAITALL ) === FALSE) {
					$errorcode = socket_last_error();
					$errormsg = socket_strerror($errorcode);
					IPS_LogMessage("GeCoS_IO Socket", "Fehler beim beim Empfangen ".$errorcode." ".$errormsg);
					$this->SendDebug("CommandClientSocket", "Fehler beim beim Empfangen ".$errorcode." ".$errormsg, 0);
					return;
				}
				// Anfragen mit variabler Rückgabelänge
				$CmdVarLen = array(56, 67, 70, 73, 75, 80, 88, 91, 92, 106, 109);
				$MessageArray = unpack("L*", $buf);
				$Command = $MessageArray[1];
				If (in_array($Command, $CmdVarLen)) {
					$Result = $this->ClientResponse($buf);
					//IPS_LogMessage("IPS2GPIO ReceiveData", strlen($buf)." Zeichen");
				}
				// Standardantworten
				elseIf ((strlen($buf) == 16) OR ((strlen($buf) / 16) == intval(strlen($buf) / 16))) {
					$DataArray = str_split($buf, 16);
					//IPS_LogMessage("IPS2GPIO ReceiveData", strlen($buf)." Zeichen");
					for ($i = 0; $i < Count($DataArray); $i++) {
						$Result = $this->ClientResponse($DataArray[$i]);
					}
				}
				else {
					IPS_LogMessage("GeCoS_IO ReceiveData", strlen($buf)." Zeichen - nicht differenzierbar!");
					$this->SendDebug("CommandClientSocket", strlen($buf)." Zeichen - nicht differenzierbar!", 0);
				}
				IPS_SemaphoreLeave("CommandClientSocket");
			}
		}	
	return $Result;
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
		        case "17":
		            	//IPS_LogMessage("IPS2GPIO Hardwareermittlung: ","gestartet");
		            	$Model[0] = array(2, 3);
		            	$Model[1] = array(4, 5, 6, 13, 14, 15);
		            	$Model[2] = array(16);
		            	$Typ[0] = array(0, 1, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 21, 22, 23, 24, 25);	
           			$Typ[1] = array(2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 22, 23, 24, 25, 27);
           			$Typ[2] = array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27);
           			
				SetValueString($this->GetIDForIdent("Hardware"), $this->GetHardware($response[4]));
           			
           			if (in_array($response[4], $Model[0])) {
    					//IPS_LogMessage("GeCoS_IO Hardwareermittlung","Raspberry Pi Typ 0");
					$this->SendDebug("Hardwareermittlung", "Raspberry Pi Typ 0", 0);
				}
				else if (in_array($response[4], $Model[1])) {
					//IPS_LogMessage("GeCoS_IO Hardwareermittlung","Raspberry Pi Typ 1");
					$this->SendDebug("Hardwareermittlung", "Raspberry Pi Typ 1", 0);
				}
				else if ($response[4] >= 16) {
					//IPS_LogMessage("GeCoS_IO Hardwareermittlung","Raspberry Pi Typ 2");
					$this->SendDebug("Hardwareermittlung", "Raspberry Pi Typ 2", 0);
				}
				else {
					IPS_LogMessage("GeCoS_IO Hardwareermittlung","nicht erfolgreich! Fehler:".$this->GetErrorText(abs($response[4])));
					$this->SendDebug("Hardwareermittlung", "nicht erfolgreich! Fehler:".$this->GetErrorText(abs($response[4])), 0);
				}
				break;
           		case "19":
           			//IPS_LogMessage("GeCoS_IO Notify","gestartet");
				$this->SendDebug("Notify", "gestartet", 0);
		            	break;
           		case "21":
           			//IPS_LogMessage("GeCoS_IO Notify","gestoppt");
				$this->SendDebug("Notify", "gestoppt", 0);
		            	break;
			case "26":
           			If ($response[4] >= 0 ) {
					SetValueInteger($this->GetIDForIdent("SoftwareVersion"), $response[4]);
					If ($response[4] < 64 ) {
						IPS_LogMessage("GeCoS_IO PIGPIO Software Version","Bitte neuste PIGPIO-Software installieren!");
						$this->SendDebug("PIGPIO Version", "Bitte neuste PIGPIO-Software installieren!", 0);
					}
					else {
						$this->SendDebug("PIGPIO Version", "PIGPIO-Software ist aktuell", 0);
					}
				}
           			else {
           				IPS_LogMessage("GeCoS_IO PIGPIO Software Version","Fehler: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "54":
		        	If ($response[4] >= 0 ) {
 					//IPS_LogMessage("IPS2GPIO I2C Handle",$response[4]." für Device ".$response[3]);
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
					$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_i2c_byte_block", "InstanceID" => $this->InstanceArraySearch("Handle", $response[2]), "Register" => $response[3], "Count" => $response[4], "ByteArray" => $ByteArray)));
				}
		            	else {
           				IPS_LogMessage("GeCoS_IO I2C Read Bytes","Handle: ".$response[2]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
				break;
			case "57":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("GeCoS_IO I2C Write Bytes","Handle: ".$response[2]." Value: ".$response[4]);
		            		//$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Value" => $response[4])));
           			}
           			else {
           				IPS_LogMessage("GeCoS_IO I2C Write Bytes","Handle: ".$response[2]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "59":
		            	If ($response[4] >= 0) {
		            		//IPS_LogMessage("GeCoS_IO I2C Read Byte","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]." DeviceSign: ".$this->GetI2C_HandleDevice($response[2]));
		            		//$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
					// Keine Rückgabe da für Device-Suche!
		            	}
		            	else {
					//IPS_LogMessage("GeCoS_IO I2C Read Byte","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));	
		            	}
		            	break;
			case "60":
		            	If ($response[4] >= 0) {
		            		//IPS_LogMessage("GeCoS_IO I2C Read Byte","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]." DeviceSign: ".$this->GetI2C_HandleDevice($response[2]));
					//$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_i2c_data", "InstanceID" => $this->InstanceArraySearch("Handle", $response[2]), "Register" => $response[3], "Value" => $response[4])));
		            	}
		            	else {
		            		IPS_LogMessage("GeCoS_IO I2C Read Byte on Handle","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));	
		            	}
		            	break;
			case "61":
		            	If ($response[4] >= 0) {
		            		//IPS_LogMessage("GeCoS_IO I2C Read Byte","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]." DeviceSign: ".$this->GetI2C_HandleDevice($response[2]));
					$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_i2c_data", "InstanceID" => $this->InstanceArraySearch("Handle", $response[2]), "Register" => $response[3], "Value" => $response[4])));
		            	}
		            	else {
		            		IPS_LogMessage("GeCoS_IO I2C Read Byte","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));	
		            	}
		            	break;
			case "62":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("GeCoS_IO I2C Write Byte","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]);
		            		//$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
           			}
           			else {
           				IPS_LogMessage("GeCoS_IO I2C Write Byte","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
			case "68":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("GeCoS_IO I2C Write Bytes","Handle: ".$response[2]." Value: ".$response[4]);
		            		//$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Value" => $response[4])));
           			}
           			else {
           				IPS_LogMessage("GeCoS_IO I2C Write Bytes Register","Handle: ".$response[2]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
			
			case "76":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("GeCoS_IO Serial Handle","Serial Handle: ".$response[4]);
				}
				else {
					IPS_LogMessage("GeCoS_IO Serial Handle","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
				}
		            	break;
		        case "77":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO Serial Close Handle","Serial Handle: ".$response[2]." Value: ".$response[4]);
           			}
           			else {
           				//IPS_LogMessage("IPS2GPIO Serial Close Handle","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));	
           			}
           			
		            	break;
		        case "80":
           			If ($response[4] >= 0) {
           				$this->SendDebug("Read Serial Data", "Value: ".substr($Message, -($response[4])), 0);
					//IPS_LogMessage("GeCoS_IO Serial Read","Serial Handle: ".$response[2]." Value: ".substr($Message, -($response[4])));
           				If ($response[4] > 0) {
						$response[4] = substr($Message, -($response[4]));
	           				//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_data", "Value"=>utf8_encode(substr($Message, -($response[4]))) )));
           				}
           			}
           			else {
           				$this->SendDebug("Read Serial Data", "Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
					IPS_LogMessage("GeCoS_IO Serial Read","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
  		            	break;
		        case "81":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("GeCoS_IO Serial Write","Serial Handle: ".$response[2]." Value: ".$response[4]);
           			}
           			else {
           				IPS_LogMessage("GeCoS_IO Serial Write","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
  		            	break;
  		        case "82":
           			If ($response[4] >= 0) {
           				$this->SendDebug("Check Serial Data", "Serial Handle: ".$response[2]." - Bytes zum Lesen: ".$response[4], 0);
					//IPS_LogMessage("IPS2GPIO Check Bytes Serial","Serial Handle: ".$response[2]." Bytes zum Lesen: ".$response[4]);
           			}
           			else {
           				$this->SendDebug("Check Serial Data", "Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
					IPS_LogMessage("GeCoS_IO Check Bytes Serial","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
          			}
  		            	break;
		        case "97":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO GlitchFilter","gesetzt");
           			}
           			else {
           				IPS_LogMessage("GeCoS_IO GlitchFilter","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
         
		            	break;
		        case "99":
           			If ($response[4] >= 0 ) {
           				//IPS_LogMessage("GeCoS_IO Handle",$response[4]);
           				$this->SendDebug("IO Handle", $response[4], 0);
           			}
           			else {
           				IPS_LogMessage("GeCoS_IO Handle","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
					$this->SendDebug("IO Handle", "Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
           			}
           			break;
		    }
	return $response[4];
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
	
	
	public function GetRTC_Data()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$Sec = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 0, 0), 16);
			$Sec = str_pad(dechex($Sec & 127), 2 ,'0', STR_PAD_LEFT);
			$Min = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 1, 0), 16);
			$Min = str_pad(dechex($Min & 127), 2 ,'0', STR_PAD_LEFT);
			$Hour = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 2, 0), 16);
			If(($Hour & 64) > 0) {
				// 12 Stunden Anzeige
				If (($Hour & 32) > 0) {
					$AMPM = "PM";
				}
				else {
					$AMPM = "AM";
				}
				$Hour = $AMPM." ".str_pad(dechex($Hour & 31), 2 ,'0', STR_PAD_LEFT);
			}
			else {
				// 24 Stunden Anzeige
				$Hour = str_pad(dechex($Hour & 63), 2 ,'0', STR_PAD_LEFT);
			}
			$Date = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 4, 0), 16);
			$Date = str_pad(dechex($Date & 63), 2 ,'0', STR_PAD_LEFT);
			$Month = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 5, 0), 16);
			$Century = ($Month >> 7) & 1;
			$Month = str_pad(dechex($Month & 31), 2 ,'0', STR_PAD_LEFT);
			$Year = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 6, 0), 16);
			$Year = str_pad(dechex($Year & 255), 2 ,'0', STR_PAD_LEFT);
			If ($Century == 1) {
				$Year = $Year + 2000;
			}
			else {
				$Year = $Year + 1900;	
			}
			$Timestamp = mktime($Hour, $Min, $Sec, $Month, $Date, $Year);
			SetValueInteger($this->GetIDForIdent("RTC_Timestamp"), $Timestamp);

			$MSBofTemp = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 17, 0), 16);
			$LSBofTemp = $this->CommandClientSocket(pack("L*", 61, $this->GetBuffer("RTC_Handle"), 18, 0), 16);

			$MSBofTemp = ($MSBofTemp & 127);
			//$Temp = ($MSBofTemp << 2) | ($LSBofTemp >> 6);
			$LSBofTemp = ($LSBofTemp >> 6) * 0.25;
			$Temp = $MSBofTemp + $LSBofTemp;
			//IPS_LogMessage("GeCoS_IO getRTC_Data", $Temp);
			SetValueFloat($this->GetIDForIdent("RTC_Temperature"), $Temp);
		}
	}
	
	public function SetRTC_Data()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			// Sekunden
			$Sec = date("s");
			$this->CommandClientSocket(pack("L*", 62, $this->GetBuffer("RTC_Handle"), 0, 4, hexdec($Sec)), 16);
			$Min = date("i");
			$this->CommandClientSocket(pack("L*", 62, $this->GetBuffer("RTC_Handle"), 1, 4, hexdec($Min)), 16);
			$Hour = date("H");
			$this->CommandClientSocket(pack("L*", 62, $this->GetBuffer("RTC_Handle"), 2, 4, hexdec($Hour)), 16);
			$Date = date("d");
			$this->CommandClientSocket(pack("L*", 62, $this->GetBuffer("RTC_Handle"), 4, 4, hexdec($Date)), 16);
			$Month = date("m");
			$this->CommandClientSocket(pack("L*", 62, $this->GetBuffer("RTC_Handle"), 5, 4, (hexdec($Month) | 128) ), 16);
			$Year = date("y");
			$this->CommandClientSocket(pack("L*", 62, $this->GetBuffer("RTC_Handle"), 6, 4, hexdec($Year)), 16);

			$this->GetRTC_Data();
		}
	}
	
	public function WriteSerial(String $Message)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$Message = utf8_decode($Message);
			$this->CommandClientSocket(pack("L*", 81, $this->GetBuffer("Serial_Handle"), 0, strlen($Message)).$Message, 16);
		}
	}
	
	private function CheckSerial()
	{
		$Result = $this->CommandClientSocket(pack("L*", 82, $this->GetBuffer("Serial_Handle"), 0, 0), 16);
		//IPS_LogMessage("GeCoS_IO CheckSerial", $Result);
		If ($Result > 0) {
			$Data = $this->CommandClientSocket(pack("L*", 80, $this->GetBuffer("Serial_Handle"), $Result, 0), 16 + $Result);
			$Message = utf8_encode($Data);
			$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_serial_data", "Buffer" => $Message)));
			//IPS_LogMessage("GeCoS_IO CheckSerial", $Data);
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
			    	IPS_LogMessage("GeCoS_IO SSH-Connect","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
				$this->SendDebug("SSH-Connect", "Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!", 0);
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
	
	private function CheckConfig()
	{
		$arrayCheckConfig = array();
		$arrayCheckConfig["I2C"]["Status"] = "unbekannt";
		$arrayCheckConfig["I2C"]["Color"] = "#FFFF00";
		$arrayCheckConfig["Serielle Schnittstelle"]["Status"] = "unbekannt";
		$arrayCheckConfig["Serielle Schnittstelle"]["Color"] = "#FFFF00";
		$arrayCheckConfig["Shell Zugriff"]["Status"] = "unbekannt";
		$arrayCheckConfig["Shell Zugriff"]["Color"] = "#FFFF00";
		$arrayCheckConfig["PIGPIO Server"]["Status"] = "unbekannt";
		$arrayCheckConfig["PIGPIO Server"]["Color"] = "#FFFF00";
		
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			
			
			set_include_path(__DIR__);
			require_once (__DIR__ . '/Net/SFTP.php');
			$sftp = new Net_SFTP($this->ReadPropertyString("IPAddress"));
			$login = @$sftp->login($this->ReadPropertyString("User"), $this->ReadPropertyString("Password"));
			
			if ($login == false)
			{
			    	$this->SendDebug("CheckConfig", "Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!", 0);
				IPS_LogMessage("GeCoS_IO CheckConfig","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			    	$Result = "";
				return serialize($arrayCheckConfig);
			}
			
			// I²C Schnittstelle
			$PathConfig = "/boot/config.txt";
			// Prüfen, ob die Datei existiert
			if (!$sftp->file_exists($PathConfig)) {
				$this->SendDebug("CheckConfig", $PathConfig." nicht gefunden!", 0);
				IPS_LogMessage("GeCoS_IO CheckConfig", $PathConfig." nicht gefunden!");
			}
			else {
				$FileContentConfig = $sftp->get($PathConfig);
				// Prüfen ob I2C aktiviert ist
				$Pattern = "/(?:\r\n|\n|\r)(\s*)(device_tree_param|dtparam)=([^,]*,)*i2c(_arm)?(=(on|true|yes|1))(\s*)($:\r\n|\n|\r)/";
				if (preg_match($Pattern, $FileContentConfig)) {
					$this->SendDebug("CheckConfig", "I2C ist aktiviert", 0);
					$arrayCheckConfig["I2C"]["Status"] = "aktiviert";
					$arrayCheckConfig["I2C"]["Color"] = "#00FF00";
				} else {
					$this->SendDebug("CheckConfig", "I2C ist deaktiviert!", 0);
					IPS_LogMessage("GeCoS_IO CheckConfig", "I2C ist deaktiviert!");
					$arrayCheckConfig["I2C"]["Status"] = "deaktiviert";
					$arrayCheckConfig["I2C"]["Color"] = "#FF0000";
				}
				// Prüfen ob die serielle Schnittstelle aktiviert ist
				$Pattern = "/(?:\r\n|\n|\r)(\s*)(enable_uart)(=(on|true|yes|1))(\s*)($:\r\n|\n|\r)/";
				if (preg_match($Pattern, $FileContentConfig)) {
					$this->SendDebug("CheckConfig", "Serielle Schnittstelle ist aktiviert", 0);
					$arrayCheckConfig["Serielle Schnittstelle"]["Status"] = "aktiviert";
					$arrayCheckConfig["Serielle Schnittstelle"]["Color"] = "#00FF00";			
				} else {
					$this->SendDebug("CheckConfig", "Serielle Schnittstelle ist deaktiviert!", 0);
					IPS_LogMessage("GeCoS_IO CheckConfig", "Serielle Schnittstelle ist deaktiviert!");
					$arrayCheckConfig["Serielle Schnittstelle"]["Status"] = "deaktiviert";
					$arrayCheckConfig["Serielle Schnittstelle"]["Color"] = "#FF0000";
				}
			}
			
			//Serielle Schnittstelle
			$PathCmdline = "/boot/cmdline.txt";
			// Prüfen, ob die Datei existiert
			if (!$sftp->file_exists($PathCmdline)) {
				$this->SendDebug("CheckConfig", $PathCmdline." nicht gefunden!", 0);
				IPS_LogMessage("GeCoS_IO CheckConfig", $PathCmdline." nicht gefunden!");
			}
			else {
				$FileContentCmdline = $sftp->get($PathCmdline);
				// Prüfen ob die Shell der serielle Schnittstelle aktiviert ist
				$Pattern = "/console=(serial0|ttyAMA(0|1)|tty(0|1))/";
				if (preg_match($Pattern, $FileContentCmdline)) {
					$this->SendDebug("CheckConfig", "Shell-Zugriff auf serieller Schnittstelle ist deaktiviert", 0);
					$arrayCheckConfig["Shell Zugriff"]["Status"] = "deaktiviert";
					$arrayCheckConfig["Shell Zugriff"]["Color"] = "#00FF00";
				} else {
					$this->SendDebug("CheckConfig", "Shell-Zugriff auf serieller Schnittstelle ist aktiviert!", 0);
					IPS_LogMessage("GeCoS_IO CheckConfig", "Shell-Zugriff auf serieller Schnittstelle ist aktiviert!");
					$arrayCheckConfig["Shell Zugriff"]["Status"] = "aktiviert";
					$arrayCheckConfig["Shell Zugriff"]["Color"] = "#FF0000";
				}
			}
			
			//PIGPIOD
			$PathPIGPIOD = "/etc/systemd/system/pigpiod.service.d/public.conf";
			// Prüfen, ob die Datei existiert
			if ($sftp->file_exists($PathPIGPIOD)) {
				$this->SendDebug("CheckConfig", "PIGPIO-Server ist aktiviert", 0);
				$arrayCheckConfig["PIGPIO Server"]["Status"] = "aktiviert";
				$arrayCheckConfig["PIGPIO Server"]["Color"] = "#00FF00";
			}
			else {
				$this->SendDebug("CheckConfig", "PIGPIO-Server ist deaktiviert!", 0);
				IPS_LogMessage("GeCoS_IO CheckConfig", "PIGPIO-Server ist deaktiviert!");
				$arrayCheckConfig["PIGPIO Server"]["Status"] = "deaktiviert";
				$arrayCheckConfig["PIGPIO Server"]["Color"] = "#FF0000";
			}
			
		}
			
	return serialize($arrayCheckConfig);
	}
	
	private function ConnectionTest()
	{
	      $result = false;
	      If (Sys_Ping($this->ReadPropertyString("IPAddress"), 2000)) {
			//IPS_LogMessage("GeCoS_IO Netzanbindung","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert");
		      	$this->SendDebug("Netzanbindung", "Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert", 0);
		      
			$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8888, $errno, $errstr, 10);
				if (!$status) {
					IPS_LogMessage("GeCoS_IO Netzanbindung","Port ist geschlossen!");
					$this->SendDebug("Netzanbindung", "Port ist geschlossen!", 0);
					// Versuchen PIGPIO zu starten
					IPS_LogMessage("GeCoS_IO Netzanbindung","Versuche PIGPIO per SSH zu starten...");
					$this->SendDebug("Netzanbindung", "Versuche PIGPIO per SSH zu starten...", 0);
					$this->SSH_Connect("sudo pigpiod");
					$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8888, $errno, $errstr, 10);
					if (!$status) {
						IPS_LogMessage("GeCoS_IO Netzanbindung","Port ist geschlossen!");
						$this->SendDebug("Netzanbindung", "Port ist geschlossen!", 0);
						$this->SetStatus(104);
					}
					else {
						fclose($status);
						//IPS_LogMessage("GeCoS_IO Netzanbindung","Port ist geöffnet");
						$this->SendDebug("Netzanbindung", "Port ist geoeffnet", 0);
						$result = true;
						$this->SetStatus(102);
					}
	   			}
	   			else {
	   				fclose($status);
					//IPS_LogMessage("GeCoS_IO Netzanbindung","Port ist geöffnet");
					$this->SendDebug("Netzanbindung", "Port ist geoeffnet", 0);
					$result = true;
					$this->SetStatus(102);
	   			}
		}
		else {
			IPS_LogMessage("GeCoS_IO Netzanbindung","IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			$this->SendDebug("Netzanbindung", "IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!", 0);
			$this->SetStatus(104);
		}
	return $result;
	}
	
	private function InstanceArraySearch(String $SearchKey, Int $SearchValue)
	{
		$Result = 0;
		$I2CInstanceArray = Array();
		$I2CInstanceArray = unserialize($this->GetBuffer("I2CInstanceArray"));
		If (count($I2CInstanceArray, COUNT_RECURSIVE) >= 5) {
			foreach ($I2CInstanceArray as $Type => $Properties) {
				foreach ($Properties as $Property => $Value) {
					If (($Property == $SearchKey) AND ($Value == $SearchValue)) {
						$Result = $Type;
					}
				}
			}
		}
	return $Result;
	}
	
	private function InstanceArrayHandleSearch(String $DeviceBus, Int $DeviceAddress)
	{
		$Result = -1;
		$I2CInstanceArray = Array();
		$I2CInstanceArray = unserialize($this->GetBuffer("I2CInstanceArray"));
		If (count($I2CInstanceArray, COUNT_RECURSIVE) >= 5) {
			foreach ($I2CInstanceArray as $Type => $Properties) {
				If (($I2CInstanceArray[$Type]["DeviceBus"] == $DeviceBus) AND ($I2CInstanceArray[$Type]["DeviceAddress"] == $DeviceAddress)) {
				    $Result = $I2CInstanceArray[$Type]["Handle"];
				}
			}
		}
	return $Result;
	}
	
	private function ResetI2CHandle($MinHandle)
	{
		$Handle = $this->CommandClientSocket(pack("L*", 54, 1, 1, 4, 0), 16);
		for ($i = $MinHandle; $i <= $Handle ; $i++) {
			$this->CommandClientSocket(pack("L*", 55, $i, 0, 0), 16);
		}
	}
	
	private function ResetSerialHandle()
	{
		$SerialHandle = $this->CommandClientSocket(pack("L*", 76, $this->ReadPropertyInteger('Baud'), 0, strlen($this->ReadPropertyString('ConnectionString')) ).$this->ReadPropertyString('ConnectionString'), 16);
		for ($i = 0; $i <= $SerialHandle; $i++) {
			$this->CommandClientSocket(pack("L*", 77, $i, 0, 0), 16);
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
		$MUX_Handle = $this->GetBuffer("MUX_Handle");
		If ($Port == 1) {
			$this->CommandClientSocket(pack("L*", 60, $MUX_Handle, 0, 0), 16);
		}
		else {
			$this->CommandClientSocket(pack("L*", 60, $MUX_Handle, $Port, 0), 16);
		}
	return;
	}
	
	private function GetOnboardI2CHandle($DeviceAddress)
	{
		// Handle ermitteln
		$Handle = $this->CommandClientSocket(pack("L*", 54, 1, $DeviceAddress, 4, 0), 16);	
	return $Handle;
	}
	
  	public function SearchI2CDevices()
	{
		$DeviceArray = Array();
		$DeviceName = Array();
		$SearchArray = Array();
		// 16In
		for ($i = 16; $i <= 23; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "16 Input";
		}
		// 16Out
		for ($i = 25; $i <= 31; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "16 Output";
		}
		// 4CurrentSense
		for ($i = 76; $i <= 79; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "4 CurrentSense";
		}
		// 16PWMOut
		for ($i = 80; $i <= 87; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "16 PWM Output";
		}
		// 4RGBW
		for ($i = 88; $i <= 95; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "4 RGBW Output";
		}
		// 4AnalogIn
		for ($i = 105; $i <= 107; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "4 Analog Input";
		}		
		$k = 0;
		
		for ($j = 4; $j <= 5; $j++) {
			$this->SetMUX($j);
			for ($i = 0; $i < count($SearchArray); $i++) {
				// Prüfen ob diese Device Addresse schon registriert wurde
				$Handle = $this->InstanceArrayHandleSearch($j, $SearchArray[$i]);
				if ($Handle >= 0) {
					// Das Gerät ist bereits registriert
					// Testweise lesen
					$this->SendDebug("SearchI2CDevices", "Device bekannt - Handle: ".$Handle." Adresse: ".$SearchArray[$i], 0);
					
					$Result = $this->CommandClientSocket(pack("L*", 59, $Handle, 0, 0), 16);
					$this->SendDebug("SearchI2CDevices", "Ergebnis des Test-Lesen: ".$Result, 0);
					
					$DeviceArray[$k][0] = $DeviceName[$i];
					$DeviceArray[$k][1] = $SearchArray[$i];
					$DeviceArray[$k][2] = $j - 4;
					
					If ($Result >= 0) {
						$DeviceArray[$k][3] = $this->InstanceArraySearch("Handle", $Handle);
						$DeviceArray[$k][4] = "OK";
						// Farbe grün für erreichbare und registrierte Instanzen
						$DeviceArray[$k][5] = "#00FF00";						
					}
					else {
						$DeviceArray[$k][3] = 0;
						$DeviceArray[$k][4] = "Inaktiv";
						// Farbe rot für nicht erreichbare aber registrierte Instanzen
						$DeviceArray[$k][5] = "#FF0000";
					}
					$k = $k + 1;
				}
				else {
					// Handle ermitteln
					$Handle = $this->CommandClientSocket(pack("L*", 54, 1, $SearchArray[$i], 4, 0), 16);
									
					if ($Handle >= 0) {
						// Testweise lesen
						$Result = $this->CommandClientSocket(pack("L*", 59, $Handle, 0, 0), 16);
						
						If ($Result >= 0) {
							$this->SendDebug("SearchI2CDevices", "Device unbekannt - Handle: ".$Handle." Adresse: ".$SearchArray[$i], 0);
							$this->SendDebug("SearchI2CDevices", "Ergebnis des Test-Lesen: ".$Result, 0);
							$DeviceArray[$k][0] = $DeviceName[$i];
							$DeviceArray[$k][1] = $SearchArray[$i];
							$DeviceArray[$k][2] = $j - 4;
							$DeviceArray[$k][3] = 0;
							$DeviceArray[$k][4] = "OK";
							// Farbe gelb für erreichbare aber nicht registrierte Instanzen
							$DeviceArray[$k][5] = "#FFFF00";
							$k = $k + 1;
							$this->SendDebug("SearchI2CDevices", "Ergebnis: ".$DeviceName[$i]." DeviceAddresse: ".$SearchArray[$i]." an Bus: ".($j - 4), 0);
							//IPS_LogMessage("GeCoS_IO I2C-Suche","Ergebnis: ".$DeviceName[$i]." DeviceAddresse: ".$SearchArray[$i]." an Bus: ".($j - 4));
						}
						// Handle löschen
						$Result = $this->CommandClientSocket(pack("L*", 55, $Handle, 0, 0), 16);
						//$this->SendDebug("SearchI2CDevices", "Ergebnis des Handle-Loeschen: ".$Result, 0);
					}
				}	
			}
		}
	return serialize($DeviceArray);
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
			10625090 => "Rev.a22042 2 Model B PCB-Rev. 1.2 1GB Embest", 9437330 => "Rev.900092 Zero PCB-Rev. 1.2 512MB Sony", 9437331 => "Rev.900093 Zero PCB-Rev. 1.3 512MB Sony", 9437377 => "Rev.9000c1 Zero W PCB-Rev. 1.1 512MB Sony", 
			10494082 => "Rev.a02082 3 Model B PCB-Rev. 1.2 1GB Sony", 10625154 => "Rev.a22082 3 Model B PCB-Rev. 1.2 1GB Embest", 44044353 => "Rev.2a01041 2 Model B PCB-Rev. 1.1 1GB Sony (overvoltage)");
		If (array_key_exists($RevNumber, $Hardware)) {
			$HardwareText = $Hardware[$RevNumber];
		}
		else {
			$HardwareText = "Unbekannte Revisions Nummer!";
		}
		// Einige Besonderheiten setzen
		If (($RevNumber == 10494082) OR ($RevNumber == 10625154)) {
			$this->SetBuffer("Default_Serial_Bus", 1);
		}
		else {
			$this->SetBuffer("Default_Serial_Bus", 0);
		}
	return $HardwareText;
	}
	
	private function GetOWHardware(string $FamilyCode)
	{
		$OWHardware = array("10" => "DS18S20 Temperatur", "12" => "DS2406 Switch", "1D" => "DS2423 Counter" , "28" => "DS18B20 Temperatur", "3A" => "DS2413 2 Ch. Switch", "29" => "DS2408 8 Ch.Switch", "05" => "DS2405 Switch", "26" => "DS2438 Batt.Monitor");
		If (array_key_exists($FamilyCode, $OWHardware)) {
			$OWHardwareText = $OWHardware[$FamilyCode];
		}
		else {
			$OWHardwareText = "Unbekannter 1-Wire-Typ!";
		}
		
	return $OWHardwareText;
	}
	
	private function OWInstanceArraySearch(String $SearchKey, String $SearchValue)
	{
		$Result = 0;
		$OWInstanceArray = Array();
		$OWInstanceArray = unserialize($this->GetBuffer("OWInstanceArray"));
		If (count($OWInstanceArray, COUNT_RECURSIVE) >= 4) {
			foreach ($OWInstanceArray as $Type => $Properties) {
				foreach ($Properties as $Property => $Value) {
					If (($Property == $SearchKey) AND ($Value == $SearchValue)) {
						$Result = $Type;
					}
				}
			}
		}
	return $Result;
	}
	
	public function OWSearchStart()
	{
		$OWDeviceArray = Array();
		$this->SetBuffer("OWDeviceArray", serialize($OWDeviceArray));
		$Result = 1;
		$SearchNumber = 0;
		while($Result == 1) {
		   	$Result = $this->OWSearch($SearchNumber);
			$SearchNumber++;
		}
	}
	
	private function DS2482Reset() 
	{
    		$this->SendDebug("DS2482Reset", "Function: Resetting DS2482", 0);
		$Result = $this->CommandClientSocket(pack("L*", 60, $this->GetBuffer("OW_Handle"), 240, 0), 16); //reset DS2482
		
		If ($Result < 0) {
			$this->SendDebug("DS2482Reset", "DS2482 Reset Failed", 0);
    		}
	}
	
	private function OWSearch(int $SearchNumber)
	{
		$this->SendDebug("SearchOWDevices", "Suche gestartet", 0);

    		$bitNumber = 1;
    		$lastZero = 0;
  		$deviceAddress4ByteIndex = 1; //Fill last 4 bytes first, data from onewire comes LSB first.
     		$deviceAddress4ByteMask = 1;
 		
		if ($this->GetBuffer("owLastDevice")) {
			$this->SendDebug("SearchOWDevices", "OW Suche beendet", 0);
			$this->SetBuffer("owLastDevice", 0);
			$this->SetBuffer("owLastDiscrepancy", 0);
			$this->SetBuffer("owDeviceAddress_0", 0xFFFFFFFF);
			$this->SetBuffer("owDeviceAddress_1", 0xFFFFFFFF);
		}
		else {
			if (!$this->OWReset()) { //if there are no parts on 1-wire, return false
			    	$this->SetBuffer("owLastDiscrepancy", 0);
			return 0;
			}
			$this->OWWriteByte(240); //Issue the Search ROM command
			do { // loop to do the search
				if ($bitNumber < $this->GetBuffer("owLastDiscrepancy")) {
					if ($this->GetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex) & $deviceAddress4ByteMask) {
						$this->SetBuffer("owTripletDirection", 1);
					} 
					else {
						$this->SetBuffer("owTripletDirection", 0);
					}
				} 
				else if ($bitNumber == $this->GetBuffer("owLastDiscrepancy")) { //if equal to last pick 1, if not pick 0
					$this->SetBuffer("owTripletDirection", 1);
				} 
				else {
					$this->SetBuffer("owTripletDirection", 0);
				}

				if (!$this->OWTriplet()) {
					return 0;
				}

				//if 0 was picked then record its position in lastZero
				if ($this->GetBuffer("owTripletFirstBit") == 0 && $this->GetBuffer("owTripletSecondBit") == 0 && $this->GetBuffer("owTripletDirection") == 0) {
					$lastZero = $bitNumber;
				}

				 //check for no devices on 1-wire
				if ($this->GetBuffer("owTripletFirstBit") == 1 && $this->GetBuffer("owTripletSecondBit") == 1) {
					break;
				}

				//set or clear the bit in the SerialNum byte serial_byte_number with mask
				if ($this->GetBuffer("owTripletDirection") == 1) {
					$this->SetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex, $this->GetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex) | $deviceAddress4ByteMask);
					//$this->SendDebug("SearchOWDevices", "owTripletDirection = 1 ".$this->GetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex), 0);
				} 
				else {
					$this->SetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex, $this->GetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex) & (~$deviceAddress4ByteMask));
					//$this->SendDebug("SearchOWDevices", "owTripletDirection = 0 ".$this->GetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex), 0);
				}
				$bitNumber++; //increment the byte counter bit number
				$deviceAddress4ByteMask = $deviceAddress4ByteMask << 1; //shift the bit mask left

				if ($deviceAddress4ByteMask == 0) { //if the mask is 0 then go to other address block and reset mask to first bit
					$deviceAddress4ByteIndex--;
					$deviceAddress4ByteMask = 1;
            			}
        		} while ($deviceAddress4ByteIndex > -1);
			
			if ($bitNumber == 65) { //if the search was successful then
            			$this->SetBuffer("owLastDiscrepancy", $lastZero);
            			if ($this->GetBuffer("owLastDiscrepancy") == 0) {
                			$this->SetBuffer("owLastDevice", 1);
            			} 
				else {
                			$this->SetBuffer("owLastDevice", 0);
            			}
			    
				$SerialNumber = sprintf("%X", $this->GetBuffer("owDeviceAddress_0")).sprintf("%X", $this->GetBuffer("owDeviceAddress_1"));
				$FamilyCode = substr($SerialNumber, -2);
				$this->SendDebug("SearchOWDevices", "OneWire Device Address = ".$SerialNumber, 0);
				$this->SendDebug("SearchOWDevices", "OneWire Device Address = ".$this->GetBuffer("owDeviceAddress_0")." ".$this->GetBuffer("owDeviceAddress_1"), 0);
				$OWDeviceArray = Array();
 				$OWDeviceArray = unserialize($this->GetBuffer("OWDeviceArray"));
				$OWDeviceArray[$SearchNumber][0] = $this->GetOWHardware($FamilyCode); // Typ
				$OWDeviceArray[$SearchNumber][1] = $SerialNumber; // Seriennumber
				$OWDeviceArray[$SearchNumber][2] =  $this->OWInstanceArraySearch("DeviceSerial", $SerialNumber); // Instanz
				$OWDeviceArray[$SearchNumber][3] = "OK"; // Status
				If ($OWDeviceArray[$SearchNumber][2] == 0) {
					// Farbe gelb für nicht registrierte Instanzen
					$OWDeviceArray[$SearchNumber][4] = "#FFFF00";
				}
				else {
					// Farbe grün für erreichbare und registrierte Instanzen
					$OWDeviceArray[$SearchNumber][4] = "#00FF00";
				}
				$OWDeviceArray[$SearchNumber][5] = $this->GetBuffer("owDeviceAddress_0"); // erster Teil der dezimalen Seriennummer
				$OWDeviceArray[$SearchNumber][6] = $this->GetBuffer("owDeviceAddress_1"); // zweiter Teil der dezimalen Seriennummer
				$this->SetBuffer("OWDeviceArray", serialize($OWDeviceArray));
				
				if ($this->OWCheckCRC()) {
					return 1;
			    	} 
				else {
					$this->SendDebug("SearchOWDevices", "OneWire device address CRC check failed", 0);
					return 1;
			    	}   
        		}
			
   		}
 		$this->SendDebug("SearchOWDevices", "No One-Wire Devices Found, Resetting Search", 0);
   		$this->SetBuffer("owLastDiscrepancy", 0);
  		$this->SetBuffer("owLastDevice", 0);
    	return 0;
	}			
			
	private function OWCheckCRC() 
	{
    		$crc = 0;
     		$j = 0;
     		$da32bit = $this->GetBuffer("owDeviceAddress_1");
    		for($j = 0; $j < 4; $j++) { //All four bytes
			$crc = $this->AddCRC($da32bit & 0xFF, $crc);
			//server.log(format("CRC = %.2X", crc));
        		$da32bit = $da32bit >> 8; //Shift right 8 bits
		}	
		$da32bit = $this->GetBuffer("owDeviceAddress_0");
		for($j = 0; $j < 3; $j++) { //only three bytes
        		$crc = $this->AddCRC($da32bit & 0xFF, $crc);
        		//server.log(format("CRC = %.2X", crc));
        		$da32bit = $da32bit >> 8; //Shift right 8 bits
    		}
		$this->SendDebug("OWCheckCRC", "CRC = ".$crc, 0);
		$this->SendDebug("OWCheckCRC", "DA  = ".$da32bit, 0);
    		
    		if (($da32bit & 0xFF) == $crc) { //last byte of address should match CRC of other 7 bytes
        		$this->SendDebug("OWCheckCRC", "CRC Passed", 0);
        		return 1; //match
    		}
	return 0; //bad CRC
	}
	
	private function AddCRC($inbyte, $crc) 
	{
	    	$j = 0;
    		for($j = 0; $j < 8; $j++) {
        		$mix = ($crc ^ $inbyte) & 0x01;
			//$mix = (pow($crc, $inbyte)) & 0x01;
        		$crc = $crc >> 1;
        		if ($mix) {
				$crc = $crc ^ 0x8C;
				//$crc = pow($crc, 0x8C);
			}
        		$inbyte = $inbyte >> 1;
    		}
    	return $crc;
	}
	
	private function OWReset() 
	{
    		$this->SendDebug("OWReset", "I2C Reset", 0);
		// Write Byte to Handle
		$Result = $this->CommandClientSocket(pack("L*", 60, $this->GetBuffer("OW_Handle"), 180, 0), 16);//1-wire reset
		
		If ($Result < 0) {
			$this->SendDebug("OWReset", "I2C Reset Failed", 0);
			return 0;
    		}
		
     		$loopcount = 0;
    		while (true) {
        		$loopcount++;
			// Read Byte from Handle
			$Data = $this->CommandClientSocket(pack("L*", 59, $this->GetBuffer("OW_Handle"), 0, 0), 16);//Read the status register
        		If ($Result < 0) {
				$this->SendDebug("OWReset", "I2C Read Status Failed", 0);
				return 0;
    			}
			else {
				$this->SendDebug("OWReset", "Read Status Byte: ".$Data, 0);
            			if ($Data & 0x01) { // 1-Wire Busy bit
                			//server.log("One-Wire bus is busy");
                			if ($loopcount > 100) {
                    				$this->SendDebug("OWReset", "One-Wire busy too long", 0);
                    				return 0;
                			}
                			IPS_Sleep(10);//Wait, try again
            			} 
				else {
					//server.log("One-Wire bus is idle");
					if ($Data & 0x04) { //Short Detected bit
						$this->SendDebug("OWReset", "One-Wire Short Detected: ".$Data, 0);
						return 0;
					}
					if ($Data & 0x02) { //Presense-Pulse Detect bit
						$this->SendDebug("OWReset", "One-Wire Devices Found: ".$Data, 0);
						break;
					} 
					else {
						$this->SendDebug("OWReset", "No One-Wire Devices Found: ".$Data, 0);
						return 0;
					}
            			}
        		}
    		}
    	return 1;
	}
	
	private function OWWriteByte($byte) 
	{
		//$this->SendDebug("OWWriteByte", "Function: Write Byte to One-Wire", 0);
    		
		$Result = $this->CommandClientSocket(pack("LLLLCC", 57, $this->GetBuffer("OW_Handle"), 0, 2, 225, 240), 16); //set read pointer (E1) to the status register (F0)

		If ($Result < 0) {
			$this->SendDebug("OWWriteByte", "I2C Write Failed", 0);
			return -1;
    		}
		
    		$loopcount = 0;
    		while (true) {
        		$loopcount++;
        		$Data = $this->CommandClientSocket(pack("L*", 59, $this->GetBuffer("OW_Handle"), 0, 0), 16);//Read the status register
			If ($Result < 0) {
				$this->SendDebug("OWWriteByte", "I2C Read Status Failed", 0);
				return -1;
    			} 
			else {
            			//server.log(format("Read Status Byte = %d", data[0]));
				if ($Data & 0x01) { // 1-Wire Busy bit
					//server.log("One-Wire bus is busy");
					if ($loopcount > 100) {
						$this->SendDebug("OWWriteByte", "One-Wire busy too long", 0);
						return -1;
					}
					IPS_Sleep(10);//Wait, try again
				} 
				else {
					//$this->SendDebug("OWWriteByte", "One-Wire bus is idle", 0);
					break;
				}
        		}
    		}
   
		$Result = $this->CommandClientSocket(pack("LLLLCC", 57, $this->GetBuffer("OW_Handle"), 0, 2, 165, $byte), 16); //set write byte command (A5) and send data (byte)

		If ($Result < 0) { //Device failed to acknowledge
        		$this->SendDebug("OWWriteByte", "I2C Write Byte Failed. Data: ".$byte, 0);
        		return -1;
    		}
    		$loopcount = 0;
    		while (true) {
        		$loopcount++;
 			$Data = $this->CommandClientSocket(pack("L*", 59, $this->GetBuffer("OW_Handle"), 0, 0), 16);//Read the status register
			If ($Result < 0) {
            			$this->SendDebug("OWWriteByte", "I2C Read Status Failed", 0);
            			return -1;
        		} 
			else {
            			//server.log(format("Read Status Byte = %d", data[0]));
            			if ($Data & 0x01) { // 1-Wire Busy bit
                			$this->SendDebug("OWWriteByte", "One-Wire bus is busy", 0);
                			if ($loopcount > 100) {
                    				$this->SendDebug("OWWriteByte", "One-Wire busy for too long", 0);
                    				return -1;
                			}
                			IPS_Sleep(10);//Wait, try again
            			} 
				else {
                			//$this->SendDebug("OWWriteByte", "One-Wire bus is idle", 0);
                			break;
            			}
        		}
    		}
    	//$this->SendDebug("OWWriteByte", "One-Wire Write Byte complete", 0);
    	return 0;
	}
	
	private function OWTriplet() 
	{
		//$this->SendDebug("OWTriplet", "Function: OneWire Triplet", 0);
		if ($this->GetBuffer("owTripletDirection") > 0) {
			$this->SetBuffer("owTripletDirection", 255);
		}

		$Result = $this->CommandClientSocket(pack("LLLLCC", 57, $this->GetBuffer("OW_Handle"), 0, 2, 120, $this->GetBuffer("owTripletDirection")), 16); //send 1-wire triplet and direction

		If ($Result < 0) { //Device failed to acknowledge message
        		$this->SendDebug("OWTriplet", "OneWire Triplet Failed", 0);
        		return 0;
    		}
	    	$loopcount = 0;
		while (true) {
			$loopcount++;
			
			$Data = $this->CommandClientSocket(pack("L*", 59, $this->GetBuffer("OW_Handle"), 0, 0), 16);//Read the status register
			If ($Result < 0) {
            			$this->SendDebug("OWTriplet", "I2C Read Status Failed", 0);
            			return -1; 
			} 
			else {		
		    		//server.log(format("Read Status Byte = %d", data[0]));
		    		if ($Data & 0x01) { // 1-Wire Busy bit
					$this->SendDebug("OWTriplet", "One-Wire bus is busy", 0);
					if ($loopcount > 100) {
			    			$this->SendDebug("OWTriplet", "One-Wire busy for too long", 0);
			    			return -1;
					}
					IPS_Sleep(10);//Wait, try again
		    		} 
				else {
					//$this->SendDebug("OWTriplet", "One-Wire bus is idle", 0);
					if ($Data & 0x20) {
						$this->SetBuffer("owTripletFirstBit", 1);
					} 
					else {
						$this->SetBuffer("owTripletFirstBit", 0);
					}
					if ($Data & 0x40) {
						$this->SetBuffer("owTripletSecondBit", 1);
					} 
					else {
						$this->SetBuffer("owTripletSecondBit", 0);
					}
					if ($Data & 0x80) {
						$this->SetBuffer("owTripletDirection", 1);
					} 
					else {
						$this->SetBuffer("owTripletDirection", 0);
					}
				return 1;
				}
			}
		}
	}
	
	private function OWSelect() 
	{
    		$this->SendDebug("OWSelect", "Selecting device", 0);
    		$this->OWWriteByte(85); //Issue the Match ROM command 55Hex
    		
    		for($i = 1; $i >= 0; $i--) {
        		$da32bit = $this->GetBuffer("owDeviceAddress_".$i);
        		for($j = 0; $j < 4; $j++) {
            			//server.log(format("Writing byte: %.2X", da32bit & 0xFF));
            			$this->OWWriteByte($da32bit & 255); //Send lowest byte
            			$da32bit = $da32bit >> 8; //Shift right 8 bits
        		}
    		}
	}
	
	private function OWRead_18B20_Temperature() 
	{
    		$data = Array();
		$celsius = -99;

    		for($i = 0; $i < 5; $i++) { //we only need 5 of the bytes
        		$data[$i] = $this->OWReadByte();
        		//server.log(format("read byte: %.2X", data[i]));
    		}
 
    		$raw = ($data[1] << 8) | $data[0];
    		$SignBit = $raw & 0x8000;  // test most significant bit
    		if ($SignBit) {
			$raw = ($raw ^ 0xffff) + 1;
		} // negative, 2's compliment
		$cfg = $data[4] & 0x60;
		if ($cfg == 0x60) {
			$this->SendDebug("OWReadTemperature", "12 bit resolution", 0);
			//server.log("12 bit resolution"); //750 ms conversion time
		} 
		else if ($cfg == 0x40) {
			$this->SendDebug("OWReadTemperature", "11 bit resolution", 0);
			//server.log("11 bit resolution"); //375 ms
			$raw = $raw & 0xFFFE;
		} 
		else if ($cfg == 0x20) {
			$this->SendDebug("OWReadTemperature", "10 bit resolution", 0);
			//server.log("10 bit resolution"); //187.5 ms
			$raw = $raw & 0xFFFC;
		} 
		else { //if (cfg == 0x00)
			$this->SendDebug("OWReadTemperature", "9 bit resolution", 0);
			//server.log("9 bit resolution"); //93.75 ms
			$raw = $raw & 0xFFF8;
		}
		//server.log(format("rawtemp= %.4X", raw));

		$celsius = $raw / 16.0;
		if ($SignBit) {
			$celsius = $celsius * (-1);
		}
		//server.log(format("Temperature = %.1f °C", celsius));
		$SerialNumber = sprintf("%X", $this->GetBuffer("owDeviceAddress_0")).sprintf("%X", $this->GetBuffer("owDeviceAddress_1"));
		$this->SendDebug("OWRead_18B20_Temperature", "OneWire Device Address = ".$SerialNumber. "Temperatur = ".$celsius." °C", 0);
	return $celsius;
	}
	
	private function OWRead_18S20_Temperature() 
	{
    		$data = Array();
		$celsius = -99;

    		for($i = 0; $i < 2; $i++) { //we only need 2 of the bytes
        		$data[$i] = $this->OWReadByte();
        		//server.log(format("read byte: %.2X", data[i]));
    		}
 
    		$raw = ($data[1] << 8) | $data[0];
    		$SignBit = $raw & 0x8000;  // test most significant bit
    		if ($SignBit) {
			$raw = ($raw ^ 0xffff) + 1;
		} // negative, 2's compliment
		
		//server.log(format("rawtemp= %.4X", raw));

		$celsius = $raw / 2.0;
		if ($SignBit) {
			$celsius = $celsius * (-1);
		}
		//server.log(format("Temperature = %.1f °C", celsius));
		$SerialNumber = sprintf("%X", $this->GetBuffer("owDeviceAddress_0")).sprintf("%X", $this->GetBuffer("owDeviceAddress_1"));
		$this->SendDebug("OWRead_18S20_Temperature", "OneWire Device Address = ".$SerialNumber. "Temperatur = ".$celsius." °C", 0);
	return $celsius;
	}
	
	private function OWRead_2413_State() 
	{
		$result = -99;
    		$result = $this->OWReadByte();
		$SerialNumber = sprintf("%X", $this->GetBuffer("owDeviceAddress_0")).sprintf("%X", $this->GetBuffer("owDeviceAddress_1"));
    		$this->SendDebug("OWRead_2413_State", "OneWire Device Address = ".$SerialNumber. "State = ".$result, 0);
	return $result;
	}
	
	private function OWReadByte() 
	{
    		//See if the 1wire bus is idle
    		//server.log("Function: Read Byte from One-Wire");
    		$Result = $this->CommandClientSocket(pack("LLLLCC", 57, $this->GetBuffer("OW_Handle"), 0, 2, 225, 240), 16); //set read pointer (E1) to the status register (F0)

		If ($Result < 0) { //Device failed to acknowledge
			$this->SendDebug("OWReadByte", "I2C Write Failed", 0);
			return -1;
    		}
		
    		$loopcount = 0;
   		while (true) {
        		$loopcount++;
			$Data = $this->CommandClientSocket(pack("L*", 59, $this->GetBuffer("OW_Handle"), 0, 0), 16);//Read the status register
			If ($Result < 0) {
				$this->SendDebug("OWReadByte", "I2C Read Status Failed", 0);
				return -1;
    			} 
			else {
            			//server.log(format("Read Status Byte = %d", data[0]));
            			if ($Data & 0x01) { // 1-Wire Busy bit
                			//server.log("One-Wire bus is busy");
                			if ($loopcount > 100) {
                    				$this->SendDebug("OWReadByte", "One-Wire busy for too long", 0);
                    				return -1;
					}
					IPS_Sleep(10); //Wait, try again
				} 
				else {
					//server.log("One-Wire bus is idle");
					break;
				}
        		}
    		}
   
    		//Send a read command, then wait for the 1wire bus to finish
		$Result = $this->CommandClientSocket(pack("L*", 60, $this->GetBuffer("OW_Handle"), 150, 0), 16); //send read byte command (96)
		If ($Result < 0) {
			$this->SendDebug("OWReadByte", "I2C Write read-request Failed", 0);
			return -1;
		} 
    
    		$loopcount = 0;
    		while (true) {
        		$loopcount++;
        		
			$Data = $this->CommandClientSocket(pack("L*", 59, $this->GetBuffer("OW_Handle"), 0, 0), 16);//Read the status register
			If ($Result < 0) {
            			$this->SendDebug("OWReadByte", "I2C Read Status Failed", 0);
            			return -1; 
			} 
			else {
            			//server.log(format("Read Status Byte = %d", data[0]));
            			if ($Data[0] & 0x01) { // 1-Wire Busy bit
                			//server.log("One-Wire bus is busy");
                			if ($loopcount > 100) {
                    				$this->SendDebug("OWReadByte", "One-Wire busy for too long", 0);
                    				return -1;
                			}
                			IPS_Sleep(10); //Wait, try again
            			} 
				else {
					//server.log("One-Wire bus is idle");
					break;
				}
        		}
    		}
   
		//Go get the data byte
		$Result = $this->CommandClientSocket(pack("LLLLCC", 57, $this->GetBuffer("OW_Handle"), 0, 2, 225, 225), 16); //set read pointer (E1) to the read data register (E1)

		If ($Result < 0) { //Device failed to acknowledge
			$this->SendDebug("OWReadByte", "I2C Write Failed", 0);
			return -1;
		}
		$Data = $this->CommandClientSocket(pack("L*", 59, $this->GetBuffer("OW_Handle"), 0, 0), 16);//Read the status register
		If ($Result < 0) {
			$this->SendDebug("OWReadByte", "I2C Read Status Failed", 0);
			return -1;
		} 
		else {
			//server.log(format("Read Data Byte = %d", data[0]));
		}
    		//server.log("One-Wire Read Byte complete");
    	return $Data;
	}
}
?>
