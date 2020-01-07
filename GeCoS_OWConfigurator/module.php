<?
    // Klassendefinition
    class GeCoS_OWConfigurator extends IPSModule 
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
		
		// 1-Wire Devices
		$arrayElements = array(); 
		$arraySort = array();
		$arraySort = array("column" => "OWType", "direction" => "ascending");
		
		$arrayColumns = array();
		$arrayColumns[] = array("caption" => "Typ", "name" => "OWType", "width" => "250px", "visible" => true);
		$arrayColumns[] = array("caption" => "Serial", "name" => "OWSerial", "width" => "auto", "visible" => true);
		$OWArray = array();
		If ($this->HasActiveParent() == true) {
			$OWArray = unserialize($this->GetData());
		}
		$arrayValues = array();
		for ($i = 0; $i < Count($OWArray); $i++) {
			$arrayCreate = array();
			$FamilyCode = substr($OWArray[$i]["OWSerial"], 0, 2);
			If (($FamilyCode == "10") OR ($FamilyCode == "28")) {
				$arrayCreate[] = array("moduleID" => $this->FamilyCodeToGUID($FamilyCode), 
					       "configuration" => array("DeviceAddress" => $OWArray[$i]["OWSerial"], "Open" => true) );
				$arrayValues[] = array("OWType" => $OWArray[$i]["OWType"], "OWSerial" => $OWArray[$i]["OWSerial"],
					       "instanceID" => $OWArray[$i]["Instance"], "create" => $arrayCreate);
			}
			else {
				$arrayValues[] = array("OWType" => $OWArray[$i]["OWType"], "OWSerial" => $OWArray[$i]["OWSerial"],
					       "instanceID" => $OWArray[$i]["Instance"]);
			}
		}
		
		$arrayElements[] = array("type" => "Configurator", "name" => "OWDevices", "caption" => "1-Wire-Komponenten", "rowCount" => 10, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);
		
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
		$OWArray = array();
		$Devices = array();
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{47113C57-29FE-4A60-9D0E-840022883B89}", "Function" => "OWS")));	
		$OWArray = unserialize($Result);
		If (is_array($OWArray)) {
			$this->SetStatus(102);
			$this->SendDebug("GetOWData", $Result, 0);
			$Devices = array();
			$i = 0;
			foreach($OWArray as $Key => $Device) {
				$Devices[$i]["OWType"] = $Device;
				$Devices[$i]["OWSerial"] = $Key;
				$Devices[$i]["Instance"] = $this->GetGeCoSInstanceID($Key);
				$i = $i + 1;
			}
		}
	
	return serialize($Devices);
	}    
	    
	private function GetGeCoSInstanceID(string $OWSerial)
	{
	    	$Result = 0;
		$OWFamilyCode = substr($OWSerial, 0, 2);
		$GUID = $this->FamilyCodeToGUID($OWFamilyCode);
		// Modulinstanzen suchen
		$InstanceArray = array();
		$InstanceArray = @(IPS_GetInstanceListByModuleID($GUID));
		If (is_array($InstanceArray)) {
			foreach($InstanceArray as $Module) {
				If (@IPS_GetProperty($Module, "DeviceAddress") == $OWSerial) {
					$this->SendDebug("GetGeCoSInstanceID", "Gefundene Instanz: ".$Module, 0);
					$Result = $Module;
					break;
				}
				else {
					$Result = 0;
				}
			}
		}
		
	return $Result;
	}
	    
	private function FamilyCodeToGUID(string $FamilyCode)
	{
		$FamilyCodeArray = array("10" => "{8179FCFF-E441-4FAC-BCC3-1B97E9D45052}", 
				     "28" => "{18CFA944-CFC9-4A72-8D2A-231604FF7D2A}");
		$GUID = $FamilyCodeArray[$FamilyCode];
	return $GUID;
	}
}
?>
