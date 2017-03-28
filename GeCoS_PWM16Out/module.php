<?
    // Klassendefinition
    class GeCoS_PWM16Out extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 80);
		$this->RegisterPropertyInteger("DeviceBus", 4);
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
 		
		$arrayOptions = array();
		for ($i = 80; $i <= 87; $i++) {
		    	$arrayOptions[] = array("label" => $i." dez. / 0x".strtoupper(dechex($i))."h", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "GeCoS I²C-Bus");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 0", "value" => 4);
		$arrayOptions[] = array("label" => "GeCoS I²C-Bus 1", "value" => 5);
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );
				
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}           
	  
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
            	//Connect to available splitter or create a new one
	    	$this->ConnectParent("{5F50D0FC-0DBB-4364-B0A3-C900040C5C35}");
	    	
	    	// Profil anlegen
		$this->RegisterProfileInteger("Intensity.4096", "Intensity", "", "%", 0, 4096, 1);
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 15; $i++) {
			$this->RegisterVariableBoolean("Output_Bln_X".$i, "Ausgang X".$i, "~Switch", ($i + 1) * 10);
			$this->EnableAction("Output_Bln_X".$i);	
			$this->RegisterVariableInteger("Output_Flt_X".$i, "Ausgang X".$i, "Intensity.4096", (($i + 1) * 10) + 5);
			$this->EnableAction("Output_Flt_X".$i);	
		}
		
		If (IPS_GetKernelRunlevel() == 10103) {
			// Logging setzen
			
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			$this->SetReceiveDataFilter($Filter);
		
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				
				// Setup
				$this->Setup();
				$this->GetOutput();
				$this->SetStatus(102);
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
			   case "get_used_i2c":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->ApplyChanges();
				}
				break;
			   case "status":
			   	If ($data->DeviceIdent == $this->GetBuffer("DeviceIdent")) {
				   	$this->SetStatus($data->Status);
			   	}
			   	break;
			  case "set_i2c_data":
			  	
			  	break;
	 	}
 	}
	
		public function RequestAction($Ident, $Value) 
	{
		switch($Ident) {
		case "Output_Bln_X0":
		case "Output_Flt_X0":
	            $this->SetOutputPin(0, $Value);
	            break;
	        case "Output_Bln_X1":
		case "Output_Flt_X1":
	            $this->SetOutputPin(1, $Value);
	            break;
	        case "Output_Bln_X2":
		case "Output_Flt_X2":
	            $this->SetOutputPin(2, $Value);
	            break;
	        case "Output_Bln_X3":
		case "Output_Flt_X3":
	            $this->SetOutputPin(3, $Value);
	            break;
	        case "Output_Bln_X4":
		case "Output_Flt_X4":
	            $this->SetOutputPin(4, $Value);
	            break;
	        case "Output_Bln_X5":
		case "Output_Flt_X5":
	            $this->SetOutputPin(5, $Value);
	            break;    
	        case "Output_Bln_X6":
		case "Output_Flt_X6":
	            $this->SetOutputPin(6, $Value);
	            break;
	        case "Output_Bln_X7":
		case "Output_Flt_X7":
	            $this->SetOutputPin(7, $Value);
	            break;
	        case "Output_Bln_X8":
		case "Output_Flt_X8":
	            $this->SetOutputPin(8, $Value);
	            break;
	        case "Output_Bln_X9":
		case "Output_Flt_X9":
	            $this->SetOutputPin(9, $Value);
	            break;
	        case "Output_Bln_X10":
		case "Output_Flt_X10":
	            $this->SetOutputPin(10, $Value);
	            break;
	        case "Output_Bln_X11":
		case "Output_Flt_X11":
	            $this->SetOutputPin(11, $Value);
	            break;
	        case "Output_Bln_X12":
		case "Output_Flt_X12":
	            $this->SetOutputPin(12, $Value);
	            break;
	        case "Output_Bln_X13":
		case "Output_Flt_X13":
	            $this->SetOutputPin(13, $Value);
	            break;    
	        case "Output_Bln_X14":
		case "Output_Flt_X14":
	            $this->SetOutputPin(14, $Value);
	            break;
	        case "Output_Bln_X15":
		case "Output_Flt_X15":
	            $this->SetOutputPin(15, $Value);
	            break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	    
	// Beginn der Funktionen
	public function SetOutputPin(Int $Output, Bool $Status, Int $Value)
	{ 
		$Output = min(15, max(0, $Output));
		$Status = min(1, max(0, $Status));
		$Value = min(255, max(0, $Value));
		
		$ByteArray = array();
		$StartAddress = ($Output * 4) + 6;
		
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			
			$this->GetOutput();
		}
	}
	
	private function GetOutput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_read_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $this->ReadPropertyInteger("DeviceAddress"), "Count" => 2)));
		}
	}    
	    
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$ByteArray = array();
			$ByteArray[0] = 0;
			$ByteArray[1] = 16;
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "ByteArray" => serialize($ByteArray) )));
			IPS_Sleep(10);
			$ByteArray = array();
			$ByteArray[0] = 254;
			$ByteArray[1] = 61;
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "ByteArray" => serialize($ByteArray) )));
			$ByteArray = array();
			$ByteArray[0] = 0;
			$ByteArray[1] = 0;
			$this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "i2c_write_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "ByteArray" => serialize($ByteArray) )));

		}
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
