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
		$arraySort = array("column" => "DeviceType", "direction" => "ascending");
		
		$arrayColumns = array();
		$arrayColumns[] = array("caption" => "Typ", "name" => "DeviceType", "width" => "150px", "visible" => true);
		$arrayColumns[] = array("caption" => "I²C-Bus", "name" => "DeviceBus", "width" => "75", "visible" => true);
		$arrayColumns[] = array("caption" => "Adresse", "name" => "DeviceAddress", "width" => "auto", "visible" => true);
		
		$DeviceArray = array();
		If ($this->HasActiveParent() == true) {
			$DeviceArray = unserialize($this->GetData());
		}
		$arrayValues = array();
		for ($i = 0; $i < Count($DeviceArray); $i++) {
			$arrayCreate = array();
			
			$arrayCreate[] = array("moduleID" => $this->DeviceTypeToGUID($DeviceArray[$i]["DeviceType"]), 
					       "configuration" => array("DeviceAddress" => $DeviceArray[$i]["DeviceAddress"], "DeviceBus" => $DeviceArray[$i]["DeviceBus"], "Open" => true) );
			
			$arrayValues[] = array("DeviceBus" => $DeviceArray[$i]["DeviceBus"], "DeviceType" => $DeviceArray[$i]["DeviceType"], "DeviceAddress" => $DeviceArray[$i]["DeviceAddress"]." / 0x".strtoupper(dechex($DeviceArray[$i]["DeviceAddress"])),
					       "instanceID" => $DeviceArray[$i]["Instance"], "create" => $arrayCreate);
			
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
			$Devices[$i]["Instance"] = $this->GetGeCoSInstanceID($Device[0], $Device[2], $Device[1]);
			$i = $i + 1;
		}
	return serialize($Devices);
	}
	
	function GetGeCoSInstanceID(string $DeviceType, int $DeviceBus, int $DeviceAddress)
	{
	    	$Result = 0;
		If ($DeviceType <> "UNB") {
			$GUID = $this->DeviceTypeToGUID($DeviceType);
			// Modulinstanzen suchen
			$InstanceArray = array();
			$InstanceArray = @(IPS_GetInstanceListByModuleID($GUID));
			If (is_array($InstanceArray)) {
				foreach($InstanceArray as $Module) {
					If ((@IPS_GetProperty($Module, "DeviceAddress") == $DeviceAddress) AND (@IPS_GetProperty($Module, "DeviceBus") == $DeviceBus)) {
						$this->SendDebug("GetGeCoSInstanceID", "Gefundene Instanz: ".$Module, 0);
						$Result = $Module;
						break;
					}
					else {
						$Result = 0;
					}
				}
			}
		}
	return $Result;
	}
	    
	private function DeviceTypeToGUID(string $DeviceType)
	{
		$DeviceArray = array("IN" => "{EF63175E-F346-4A87-A828-F4C422F7F948}", "UNB" => "{}", 
				     "OUT" => "{EC701E32-032F-4FBD-B161-F66890DD0A9C}", 
				     "PWM" => "{E6CD7AEF-064A-42EF-A5CD-B81453DA762C}", 
				     "RGBW" => "{8A40AFDC-979B-4688-A014-3BA2B70550E8}", 
				     "ANA" => "{39E6BA4A-A94E-4058-B099-794A627B63E0}");
		$GUID = $DeviceArray[$DeviceType];
	return $GUID;
	}
}
?>
