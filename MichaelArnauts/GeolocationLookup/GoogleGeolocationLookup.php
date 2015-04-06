<?php

namespace MichaelArnauts\GeolocationLookup;

class GoogleGeolocationLookup extends GeolocationLookupInterface {
    
    private $blnSensor;
    
    public function __construct($sensor = true) {
        
        $this->blnSensor = $sensor;
    }
    
    public function Lookup($arrAccessPoints) {
        
        $url = sprintf("https://maps.googleapis.com/maps/api/browserlocation/json?browser=%s&sensor=%s", 'none', ($this->blnSensor ? 'true' : 'false'));
    
        foreach ($arrAccessPoints as $objAccessPoint) {
            
            $param = sprintf('&wifi=mac:%s-%s-%s-%s-%s-%s', substr($objAccessPoint->macaddress, 0, 2), substr($objAccessPoint->macaddress, 2, 2), substr($objAccessPoint->macaddress, 4, 2), substr($objAccessPoint->macaddress, 6, 2), substr($objAccessPoint->macaddress, 8, 2), substr($objAccessPoint->macaddress, 10, 2));
            
            if ($objAccessPoint->ssid) {
                $param .= sprintf('|ssid:%s', $objAccessPoint->ssid);
            }

            if ($objAccessPoint->snr) {
                $param .= sprintf('|ss:%d', $objAccessPoint->snr);
            }

            if ($objAccessPoint->age) {
                $param .= sprintf('|age:%d', $objAccessPoint->age);
            }
            
            $url .= $param;
        }
        
        // Request coordinates from google
        $c = curl_init();
        curl_setopt( $c, CURLOPT_URL, $url );
        curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $c, CURLOPT_FAILONERROR, true);
        
        if (!$output = curl_exec($c)) {
            throw new \Exception(curl_error($c));
        }
  
        $arrDetails = json_decode($output);
        
        if ($arrDetails->status != 'OK') {
            throw new \Exception('Unknown result');
        }
        
        $objLookupResult = new LookupResult();
        $objLookupResult->accuracy = $arrDetails->accuracy;
        $objLookupResult->latitude = $arrDetails->location->lat;
        $objLookupResult->longitude = $arrDetails->location->lng;
        
        return $objLookupResult;
    }
    
}
