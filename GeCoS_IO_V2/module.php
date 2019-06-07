<?
class GeCoS_IO_V2 extends IPSModule
{
 	private $Socket = false;
	
	public function __construct($InstanceID) 
	{
            	parent::__construct($InstanceID);
	}
	
	public function Create() 
	{
	    	parent::Create();
	    
	    	// Modul-Eigenschaftserstellung
	    	$this->RegisterPropertyBoolean("Open", false);
	    	$this->RegisterPropertyString("IPAddress", "127.0.0.1");
		$this->RegisterPropertyString("User", "User");
	    	$this->RegisterPropertyString("Password", "Passwort");
		$this->RegisterPropertyString("I2C_Devices", "");
		$this->RegisterPropertyString("OW_Devices", "");
		$this->RegisterPropertyString("Raspi_Config", "");
		$this->RegisterPropertyInteger("SerialDevice", 1);
		$this->RegisterPropertyInteger("Baud", 9600);
            	$this->RegisterPropertyString("ConnectionString", "/dev/serial0");
		$this->RegisterTimer("RTC_Data", 0, 'GeCoSIOV2_GetRTC_Data($_IPS["TARGET"]);');
	    	$this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
		
		// Profile anlegen
		$this->RegisterProfileInteger("IPS2CeCoSIO.Boardversion", "Information", "", "", 0, 1, 1);
		IPS_SetVariableProfileAssociation("IPS2CeCoSIO.Boardversion", 0, "Version 1", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2CeCoSIO.Boardversion", 1, "Version 2", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2CeCoSIO.Boardversion", 99, "Unbekannter Fehler!", "Alert", -1);
		
		// Statusvariablen anlegen
		$this->RegisterVariableString("Hardware", "Hardware", "", 20);
		
		$this->RegisterVariableInteger("Boardversion", "GeCoS-Server", "IPS2CeCoSIO.Boardversion", 25);
			
		$this->RegisterVariableInteger("SoftwareVersion", "SoftwareVersion", "", 30);
			
		$this->RegisterVariableFloat("RTC_Temperature", "RTC Temperatur", "~Temperature", 40);
			
		$this->RegisterVariableInteger("RTC_Timestamp", "RTC Zeitstempel", "~UnixTimestamp", 50);
			
		$this->RegisterVariableInteger("LastKeepAlive", "Letztes Keep Alive", "~UnixTimestamp", 60);
		
		$ModulesArray = Array();
		$this->SetBuffer("ModulesArray", serialize($ModulesArray));
		
		$OWDeviceArray = array();
		$this->SetBuffer("OWDeviceArray", serialize($OWDeviceArray));
	}
  
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Datenverbindung ist gestört");
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
 		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "IPAddress", "caption" => "IP");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Zugriffsdaten des Raspberry Pi SSH:");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "User", "caption" => "User");
		$arrayElements[] = array("type" => "PasswordTextBox", "name" => "Password", "caption" => "Password");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Funktion der Seriellen Schnittstelle:");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "DMX", "value" => 1);
		$arrayOptions[] = array("label" => "ModBus", "value" => 2);
		$arrayOptions[] = array("label" => "RS232", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "SerialDevice", "caption" => "Nutzung", "options" => $arrayOptions );
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
		$arrayElements[] = array("type" => "Label", "label" => "Analyse der Raspberry Pi Konfiguration:");
		$arraySort = array();
		$arraySort = array("column" => "ServiceTyp", "direction" => "ascending");
		$arrayColumns = array();
		$arrayColumns[] = array("label" => "Service", "name" => "ServiceTyp", "width" => "200px", "add" => "");
		$arrayColumns[] = array("label" => "Status", "name" => "ServiceStatus", "width" => "auto", "add" => "");
		$ServiceArray = array();
		$ServiceArray = unserialize($this->CheckConfig());
		$arrayValues[] = array("ServiceTyp" => "I²C", "ServiceStatus" => $ServiceArray["I2C"]["Status"], "rowColor" => $ServiceArray["I2C"]["Color"]);
		$arrayValues[] = array("ServiceTyp" => "Serielle Schnittstelle (RS232)", "ServiceStatus" => $ServiceArray["Serielle Schnittstelle"]["Status"], "rowColor" => $ServiceArray["Serielle Schnittstelle"]["Color"]);
		$arrayValues[] = array("ServiceTyp" => "Shell Zugriff", "ServiceStatus" => $ServiceArray["Shell Zugriff"]["Status"], "rowColor" => $ServiceArray["Shell Zugriff"]["Color"]);
		$arrayValues[] = array("ServiceTyp" => "PIGPIO Server", "ServiceStatus" => $ServiceArray["PIGPIO Server"]["Status"], "rowColor" => $ServiceArray["PIGPIO Server"]["Color"]);
		
		$arrayElements[] = array("type" => "List", "name" => "Raspi_Config", "caption" => "Konfiguration", "rowCount" => 4, "add" => false, "delete" => false, "sort" => "", "columns" => $arrayColumns, "values" => $arrayValues);
	
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		
		$arraySort = array();
		$arraySort = array("column" => "DeviceTyp", "direction" => "ascending");
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
			/*
			// I²C-Devices einlesen und in das Values-Array kopieren
			$DeviceArray = array();
			$DeviceArray = unserialize($this->SearchI2CDevices());
			$arrayValues = array();
			If (count($DeviceArray , COUNT_RECURSIVE) >= 4) {
				for ($i = 0; $i < Count($DeviceArray); $i++) {
					$arrayValues[] = array("DeviceTyp" => $DeviceArray[$i][0], "DeviceAddress" => $DeviceArray[$i][1], "DeviceBus" => $DeviceArray[$i][2], "InstanceID" => $DeviceArray[$i][3], "DeviceStatus" => $DeviceArray[$i][4], "rowColor" => $DeviceArray[$i][5]);
				}
				$arrayElements[] = array("type" => "List", "name" => "I2C_Devices", "caption" => "I²C-Devices", "rowCount" => 5, "add" => false, "delete" => false, "sort" => "", "columns" => $arrayColumns, "values" => $arrayValues);
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
				$arrayOWValues = array();
				for ($i = 0; $i < Count($OWDeviceArray); $i++) {
					$arrayOWValues[] = array("DeviceTyp" => $OWDeviceArray[$i][0], "DeviceSerial" => $OWDeviceArray[$i][1], "InstanceID" => $OWDeviceArray[$i][2], "DeviceStatus" => $OWDeviceArray[$i][3], "rowColor" => $OWDeviceArray[$i][4]);
				}
				$arrayElements[] = array("type" => "List", "name" => "OW_Devices", "caption" => "1-Wire-Devices", "rowCount" => 5, "add" => false, "delete" => false, "sort" => "", "columns" => $arrayOWColumns, "values" => $arrayOWValues);
			}
			else {
				$arrayElements[] = array("type" => "Label", "label" => "Es wurden keine 1-Wire-Devices gefunden.");
			}
			*/
			$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
			$arrayElements[] = array("type" => "Label", "label" => "Setzen der Real-Time-Clock auf IPS-Zeit:");
			$arrayElements[] = array("type" => "Button", "label" => "RTC setzen", "onClick" => 'GeCoSIOV2_SetRTC_Data($id);');		
		}
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
			$this->SetBuffer("ModuleReady", 0);
			
			$ParentID = $this->GetParentID();
			
			If ($ParentID > 0) {
				If (IPS_GetProperty($ParentID, 'Host') <> $this->ReadPropertyString('IPAddress')) {
		                	IPS_SetProperty($ParentID, 'Host', $this->ReadPropertyString('IPAddress'));
				}
				If (IPS_GetProperty($ParentID, 'Port') <> 8000) {
		                	IPS_SetProperty($ParentID, 'Port', 8000);
				}
				If (IPS_GetProperty($ParentID, 'Open') <> $this->ReadPropertyBoolean("Open")) {
		                	IPS_SetProperty($ParentID, 'Open', $this->ReadPropertyBoolean("Open"));
				}
				If (IPS_GetName($ParentID) == "Client Socket") {
		                	IPS_SetName($ParentID, "GeCoS");
				}
				if(IPS_HasChanges($ParentID))
				{
				    	$Result = @IPS_ApplyChanges($ParentID);
					If ($Result) {
						$this->SendDebug("ApplyChanges", "Einrichtung des Client Socket erfolgreich", 0);
					}
					else {
						$this->SendDebug("ApplyChanges", "Einrichtung des Client Socket nicht erfolgreich!", 0);
					}
				}
			}
						
		        // Änderung an den untergeordneten Instanzen
		        $this->RegisterMessage($this->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
		        $this->RegisterMessage($this->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
		        // INSTANCEMESSAGE
		        $this->RegisterMessage($ParentID, 10505); // Status hat sich geändert
						
			If (($this->ConnectionTest()) AND ($this->ReadPropertyBoolean("Open") == true))  {
				$this->SetSummary($this->ReadPropertyString('IPAddress'));
				$this->SendDebug("ApplyChanges", "Starte Vorbereitung", 0);
				$this->CheckConfig();
			
				// Vorbereitung beendet
				$this->SendDebug("ApplyChanges", "Beende Vorbereitung", 0);
				$this->SetBuffer("ModuleReady", 1);
				
				// Ermitteln der genutzten I2C-Adressen
				$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"get_used_modules")));
				
				// Starttrigger für 1-Wire-Instanzen
				$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_start_trigger")));
				
				// Sucht nach Modulen
				$Result = $this->ClientSocket("{MOD}");
				
				$this->SetStatus(102);
				$this->SetTimerInterval("RTC_Data", 300 * 1000);
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
	  	$JsonArray = array( "Host" => $this->ReadPropertyString('IPAddress'), "Port" => 8000, "Open" => $this->ReadPropertyBoolean("Open"));
	  	$Json = json_encode($JsonArray);        
	  	return $Json;
	}  
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case 10100:
				If ($Data[0] == 10103) {
					$this->SendDebug("MessageSink", "IPS-Kernel ist bereit und läuft", 0);
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
				If (is_array($I2CInstanceArray) == true) {
					If (array_key_exists($SenderID, $I2CInstanceArray)) {
						unset ($I2CInstanceArray[$SenderID]);
					}
				}
				$this->SetBuffer("I2CInstanceArray", serialize($I2CInstanceArray));
				$OWInstanceArray = Array();
				$OWInstanceArray = unserialize($this->GetBuffer("OWInstanceArray"));
				If (is_array($OWInstanceArray) == true) {
					If (array_key_exists($SenderID, $OWInstanceArray)) {
						unset ($OWInstanceArray[$SenderID]);
					}
				}
				$this->SetBuffer("OWInstanceArray", serialize($OWInstanceArray));
				$this->UnregisterMessage($SenderID, 11101);
				$this->UnregisterMessage($SenderID, 11102);
				break;				
			case 10505:
				If ($Data[0] == 102) {
					$this->SendDebug("MessageSink", "Uebergeordnete Instanz ".$SenderID." meldet Status OK", 0);
					$this->ApplyChanges();
				}
				elseif (($Data[0] >= 200) AND ($this->ReadPropertyBoolean("Open") == true)) {
					$this->SendDebug("MessageSink", "Uebergeordnete Instanz ".$SenderID." meldet Status fehlerhaft", 0);
					$this->ConnectionTest();
				}
				break;
		}
		
    	}
	  
	 public function ForwardData($JSONString) 
	 {
		 // Empfangene Daten von der Device Instanz
	    	 $data = json_decode($JSONString);
		 $Result = -999;
	    	 $I2CInstanceArray = Array();
		 $I2CInstanceArray = unserialize($this->GetBuffer("I2CInstanceArray"));
		 $OWInstanceArray = Array();
		 $OWInstanceArray = unserialize($this->GetBuffer("OWInstanceArray"));
	 	
		 switch ($data->Function) {
		 // interne Kommunikation
		   	// I2C Kommunikation
		   	case "set_used_modules":		   	
				$this->SendDebug("set_used_modules", "Ausfuehrung", 0);
				 If ($this->GetBuffer("ModuleReady") == 1) {
					// die genutzten Device Adressen anlegen
					$I2CInstanceArray[$data->InstanceID]["InstanceID"] = $data->InstanceID; 
					$I2CInstanceArray[$data->InstanceID]["DeviceBus"] = $data->DeviceBus;
					$I2CInstanceArray[$data->InstanceID]["DeviceAddress"] = $data->DeviceAddress;
					$I2CInstanceArray[$data->InstanceID]["Status"] = "Angemeldet";
					$this->SetBuffer("I2CInstanceArray", serialize($I2CInstanceArray));
					 $this->SendDebug("set_used_modules", serialize($I2CInstanceArray), 0);
					// Messages einrichten
					$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden
					$this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt
					$Result = true;
				}
				else {
					$Result = false;
				}
				break;
			case "getBoardVersion":
					$Board = 2; // aktuell eine Konstante
				 	$Result = $Board;
				break;  
			// Kommunikation zur Server-Software
			case "SAO": // Module 16Out
				// Auslesen des aktuellen Status
				$Result = $this->ClientSocket("{SAO}");
				break;   
			case "SOM": // Module 16Out
				// Setzen des Status
				$DeviceBus = intval($data->DeviceBus);
				$DeviceAddress = intval($data->DeviceAddress);
				$Value = intval($data->Value);
				$Result = $this->ClientSocket("{SOM;".$DeviceBus.";0x".dechex($DeviceAddress).";".$Value."}");
				break; 
			
			case "SAI": // Module 16In
				// Auslesen des aktuellen Status
				$Result = $this->ClientSocket("{SAI}");
				break;   
			
			
			// Raspberry Pi Kommunikation
		    	case "get_RPi_connect":
				// SSH Connection
				If ($data->IsArray == false) {
					// wenn es sich um ein einzelnes Kommando handelt
					//IPS_LogMessage("IPS2GPIO SSH-Connect", $data->Command );
					$Result = $this->SSH_Connect($data->Command);
					//IPS_LogMessage("IPS2GPIO SSH-Connect", $Result );
					$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_RPi_connect", "InstanceID" => $data->InstanceID, "CommandNumber" => $data->CommandNumber, "Result"=>utf8_encode($Result), "IsArray"=>false  )));
				}
				else {
					// wenn es sich um ein Array von Kommandos handelt
					$Result = $this->SSH_Connect_Array($data->Command);
					$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"set_RPi_connect", "InstanceID" => $data->InstanceID, "CommandNumber" => $data->CommandNumber, "Result"=>utf8_encode($Result), "IsArray"=>true  )));
				}
			break;
				 
		}
	Return $Result;
	}
	
	 public function ReceiveData($JSONString) {	
		 // Empfangene Daten vom I/O
	    	 $Data = json_decode($JSONString);
	    	 $Message = utf8_decode($Data->Buffer);
		 $this->SendDebug("ReceiveData", "Datenempfang: ".$Message, 0);
		 
		 $DataArray = array();
		 preg_match_all('({[^}]*})', $Message, $DataArray);
		 $this->SendDebug("ReceiveData", "Datenaufloesung: ".serialize($DataArray), 0);
		
		 If (count($DataArray,  COUNT_RECURSIVE) <= 1) {
    			  $this->SendDebug("ReceiveData", "Keine sinnvollen Daten erhalten", 0);
			 return;
		 }
		 
		 for ($i = 0; $i <= Count($DataArray) - 1; $i++) {
		    	$Value = str_replace(array("{", "}"), "", $DataArray[0][$i]);
		    	$ValueArray = explode(";", $Value);
		    	// Erstes Datenfeld enthält die Befehle
			$Command = $ValueArray[0];
			$DeviceBus = intval($ValueArray[1]);
			$DeviceAddress = hexdec($ValueArray[2]);
			$this->SendDebug("ReceiveData", "Bus: ".$DeviceBus." Adresse: ".$DeviceAddress, 0);
			
			switch ($Command) {
			case "SAO":
				$this->SendDebug("ReceiveData", "SAO", 0);
				$InstanceID = $this->InstanceIDSearch($DeviceBus, $DeviceAddress);
				$this->SendDebug("ReceiveData", "Instant ID: ".$InstanceID, 0);
				$Value = intval($ValueArray[3]);
				$StatusMessage = $ValueArray[4];
				$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"SAO", "InstanceID" => $InstanceID, "Value" => $Value, "StatusMessage" => $StatusMessage)));
				break;
			case "SOM":
				$this->SendDebug("ReceiveData", "SOM", 0);
				break;
			case "SAI":
				$this->SendDebug("ReceiveData", "SAI", 0);
				$InstanceID = $this->InstanceIDSearch($DeviceBus, $DeviceAddress);
				$this->SendDebug("ReceiveData", "Instant ID: ".$InstanceID, 0);
				$Value = intval($ValueArray[3]);
				$StatusMessage = $ValueArray[4];
				$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"SAI", "InstanceID" => $InstanceID, "Value" => $Value, "StatusMessage" => $StatusMessage)));
				break;
			case "MOD":
				$this->SendDebug("ReceiveData", "MOD", 0);
				$ModulesArray = Array();
				$ModulesArray = unserialize($this->GetBuffer("ModulesArray"));
				/*
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
				*/	
				break;
			}
		}
					
	}
	
	private function ClientSocket(String $Message)
	{
		$Success = false;
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$Success = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => utf8_encode($Message))));
			$Success = true;
			$this->SendDebug("ClientSocket", "Text: ".$Message." Erfolg: ".$Success, 0);
			
		}
	Return $Success;
	}
	
	public function GetRTC_Data()
	{
		$this->SendDebug("GetRTC_Data", "Ausfuehrung", 0);
	}
	
	private function SSH_Connect(String $Command)
	{
	        If (($this->ReadPropertyBoolean("Open") == true) ) {
			set_include_path(__DIR__.'/../libs');
			require_once (__DIR__ . '/../libs/Net/SSH2.php');
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
	
	private function SSH_Connect_Array(String $Command)
	{
	        If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			set_include_path(__DIR__.'/../libs');
			require_once (__DIR__ . '/../libs/Net/SSH2.php');
			$ssh = new Net_SSH2($this->ReadPropertyString("IPAddress"));
			$login = @$ssh->login($this->ReadPropertyString("User"), $this->ReadPropertyString("Password"));
			if ($login == false)
			{
			    	IPS_LogMessage("GeCoS_IO SSH-Connect","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
				$this->SendDebug("SSH-Connect", "Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!", 0);
			    	$Result = "";
				return false;
			}
			$ResultArray = Array();
			$CommandArray = unserialize($Command);
			for ($i = 0; $i < Count($CommandArray); $i++) {
				$ResultArray[key($CommandArray)] = $ssh->exec($CommandArray[key($CommandArray)]);
				next($CommandArray);
			}
			$ssh->disconnect();
			$Result = serialize($ResultArray);
		}
		else {
			$ResultArray = Array();
			$Result = serialize($ResultArray);
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
			set_include_path(__DIR__.'/../libs');
			require_once (__DIR__ .'/../libs/Net/SFTP.php');
			
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
		      
			$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8000, $errno, $errstr, 10);
				if (!$status) {
					IPS_LogMessage("GeCoS_IO Netzanbindung","Port ist geschlossen!");
					$this->SendDebug("Netzanbindung", "Port ist geschlossen!", 0);
					// Versuchen PIGPIO zu starten
					IPS_LogMessage("GeCoS_IO Netzanbindung","Versuche PIGPIO per SSH zu starten...");
					$this->SendDebug("Netzanbindung", "Versuche Server-Software per SSH zu starten...", 0);
					// Hier muss das Skript gestartet werden
					//$this->SSH_Connect("sudo pigpiod");
					$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8000, $errno, $errstr, 10);
					if (!$status) {
						IPS_LogMessage("GeCoS_IO Netzanbindung","Port ist geschlossen!");
						$this->SendDebug("Netzanbindung", "Port ist geschlossen!", 0);
						$this->SetStatus(201);
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
			$this->SetStatus(201);
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
	
	private function InstanceIDSearch(Int $DeviceBus, Int $DeviceAddress)
	{
		// Ermittelt anhand der Daten die Instanz-ID
		$Result = -1;
		$I2CInstanceArray = Array();
		$I2CInstanceArray = unserialize($this->GetBuffer("I2CInstanceArray"));
		If (count($I2CInstanceArray, COUNT_RECURSIVE) >= 4) {
			foreach ($I2CInstanceArray as $Type => $Properties) {
				If (($I2CInstanceArray[$Type]["DeviceBus"] == $DeviceBus) AND ($I2CInstanceArray[$Type]["DeviceAddress"] == $DeviceAddress)) {
				    $Result = $I2CInstanceArray[$Type]["InstanceID"];
				}
			}
		}
	return $Result;
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
  	
	
	
  	private function SearchI2CDevices()
	{
		$this->SendDebug("SearchI2CDevices", "Ausfuehrung", 0);
		
		
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
}
?>
