<?php

namespace MichaelArnauts\GeolocationLookup;

class CacheGeolocationLookup extends GeolocationLookupInterface {
    
    private $strFile;
    
    public function __construct($file = true) {
        $this->strFile = $file;
    }
    
    public function Lookup($arrAccessPoints) {
        
        return false;
        
    }
    
}
