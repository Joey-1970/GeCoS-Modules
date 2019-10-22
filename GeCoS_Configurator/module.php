<?
    // Klassendefinition
    class GeCoS_Configurator extends IPSModule 
    {
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->ConnectParent("{5F1C0403-4A74-4F14-829F-9A217CFB2D05}");
		
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "I²C-Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arraySort = array();
		$arraySort = array("column" => "DeviceBus", "direction" => "ascending");
		
		$arrayColumns = array();
		$arrayColumns[] = array("caption" => "I²C-Bus", "name" => "DeviceBus", "width" => "100px", "visible" => true);
		$arrayColumns[] = array("caption" => "Typ", "name" => "DeviceType", "width" => "250px", "visible" => true);
		$arrayColumns[] = array("caption" => "Adresse", "name" => "DeviceAddress", "width" => "auto", "visible" => true);
		
		$DeviceArray = array();
		If ($this->HasActiveParent() == true) {
			$DeviceArray = unserialize($this->GetData());
		}
		$arrayValues = array();
		for ($i = 0; $i < Count($DeviceArray); $i++) {
			$arrayCreate = array();
			
			$arrayCreate[] = array("moduleID" => "{47286CAD-187A-6D88-89F0-BDA50CBF712F}", 
					       "configuration" => array("StationID" => 0, "Timer_1" => 10));
			
			$arrayValues[] = array("DeviceBus" => $DeviceArray[$i]["DeviceBus"], "DeviceType" => $DeviceArray[$i]["DeviceType"], "DeviceAddress" => $DeviceArray[$i]["DeviceAddress"],
					       "instanceID" => 0, "create" => $arrayCreate);
			
		}
		
		$arrayElements[] = array("type" => "Configurator", "name" => "GeCoS_Modules", "caption" => "GeCoS-Module", "rowCount" => 10, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "caption" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/';");
		
		$arrayActions = array();
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		If (IPS_GetKernelRunlevel() == 10103) {	
			If ($this->HasActiveParent() == true) {
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}
		}
	}
	    
	// Beginn der Funktionen
	private function GetData()
	{
		$DeviceArray = array();
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "MOD")));
		$DeviceArray = unserialize($Result);
		$this->SendDebug("GetData", $Result, 0);
		$Devices = array();
		$i = 0;
		foreach($DeviceArray as $Key => $Device) {
			$Devices[$i]["DeviceType"] = $Device[0];
			$Devices[$i]["DeviceAddress"] = $Device[1];
			$Devices[$i]["DeviceBus"] = $Device[2];
			
			$i = $i + 1;
		}
		
		//$arrayValues[] = array("DeviceTyp" => $Value[0], "DeviceAddress" => $Value[1], "DeviceBus" => $Value[2], "InstanceID" => $Value[3], "DeviceStatus" => $Value[4], "rowColor" => $Value[5]);

		
		
	return serialize($DeviceArray);
	}
	
	function GetGeCoSInstanceID()
	{
		$guid = "{47286CAD-187A-6D88-89F0-BDA50CBF712F}";
	    	$Result = 0;
	    	// Modulinstanzen suchen
	    	$InstanceArray = array();
	    	$InstanceArray = (IPS_GetInstanceListByModuleID($guid));
	    	foreach($InstanceArray as $Module) {
        		If (strtolower(IPS_GetProperty($Module, "StationID")) == strtolower($StationID)) {
            			$this->SendDebug("GetStationInstanceID", "Gefundene Instanz: ".$Module, 0);
				$Result = $Module;
				break;
        		}
        		else {
            			$Result = 0;
        		}
    		}
	return $Result;
	}
}
?>
