<?php

namespace MichaelArnauts\EyeFiLogParser;

class EyeFiLogParser {

    private $arrEvents;
    
    public function __construct($strLogFileName) {
        
        /*
         * Load and parse the logfile
         */
        $this->ParseLog($strLogFileName);
                
    }
    
    public function GetAccessPoints($strPhotoFileName) {
        
        /*
         * Filter out the events of the session where this photo was taken
         */
        list($arrEvents, $objPhotoTakenEvent) = $this->SliceLog($strPhotoFileName);
        
        /*
         * Store the Access Points related to this photo
         */
        $arrAccessPoints = array();
        foreach ($arrEvents as $objEvent) {
            switch (get_class($objEvent)) {
                case 'MichaelArnauts\EyeFiLogParser\AccessPointSightingEvent':
                    $objAccessPoint = new AccessPoint();
                    $objAccessPoint->Age = abs($objPhotoTakenEvent->PowerSecs - $objEvent->PowerSecs);
                    $objAccessPoint->MacAddress = $objEvent->MacAddress;
                    $objAccessPoint->SNR = $objEvent->SNR;

                    // Only keep Access Points within 5 minute time frame and keep only the most recent
                    if ($objAccessPoint->Age <= 300) {
                        if (!isset($arrAccessPoints[$objEvent->MacAddress]) || ($arrAccessPoints[$objEvent->MacAddress]->Age > $objAccessPoint->Age)) {
                            $arrAccessPoints[$objEvent->MacAddress] = $objAccessPoint;
                        }
                    }

                    break;
            }
        }
        
        return $arrAccessPoints;
    }
    
    private function ParseLog($strLogFileName) {

        /*
         * Load file in memory
         */
        $strLog = file_get_contents($strLogFileName);
        $this->arrEvents = array();
        
        $line = strtok($strLog, "\n");
        while ($line !== false) {

            $arrFields = explode(',', $line);

            /*
             * Unknown Event
             */
            if (count($arrFields) < 3) {
                $objEvent = new UnknownEvent();
                $objEvent->Content = $line;
                $this->arrEvents[] = $objEvent;
            } else {
                
                switch ($arrFields[2]) {

                    /*
                     * Power Event
                     */
                    case 'POWERON':
                        $objEvent = new PowerOnEvent();
                        $objEvent->PowerSecs = intval($arrFields[0]);
                        $objEvent->Secs = intval($arrFields[1]);
                        $this->arrEvents[] = $objEvent;
                        break;

                    /*
                     * Access Point found
                     */
                    case 'AP': 
                    case 'NEWAP':
                        $objEvent = new AccessPointSightingEvent();
                        $objEvent->PowerSecs = intval($arrFields[0]);
                        $objEvent->Secs = intval($arrFields[1]);
                        $objEvent->MacAddress = $arrFields[3];
                        $objEvent->SNR = intval($arrFields[4]);
                        $objEvent->Data = $arrFields[5];
                        $this->arrEvents[] = $objEvent;
                        break;

                    /*
                     * New photo taken
                     */
                    case 'NEWPHOTO':
                        $objEvent = new PhotoTakenEvent();
                        $objEvent->PowerSecs = $arrFields[0];
                        $objEvent->Secs = $arrFields[1];
                        $objEvent->FileName = $arrFields[3];
                        $objEvent->Size = $arrFields[4];
                        $this->arrEvents[] = $objEvent;
                        break;

                    /*
                     * Unknown Event
                     */
                    default:
                        $objEvent = new UnknownEvent();
                        $objEvent->PowerSecs = $arrFields[0];
                        $objEvent->Secs = $arrFields[1];
                        $objEvent->Content = $line;
                        $this->arrEvents[] = $objEvent;
                        break;
                }
            }

            $line = strtok("\n");
        }        

    }

    private function SliceLog($strPhotoFileName) {
        
        $arrEvents = array();
        $objPhotoTakenEvent = null;
        
        foreach ($this->arrEvents as $objEvent) {
            switch (get_class($objEvent)) {
                case 'MichaelArnauts\EyeFiLogParser\PowerOnEvent':
                    if ($objPhotoTakenEvent) {
                        break 2;
                    } else {
                        $arrEvents = array();
                    }
                    break;
                
                case 'MichaelArnauts\EyeFiLogParser\PhotoTakenEvent':
                    $arrEvents[] = $objEvent;
                    if ($objEvent->FileName == $strPhotoFileName) {
                        $objPhotoTakenEvent = $objEvent;
                    }
                    break;
                
                default:
                    $arrEvents[] = $objEvent;
                    break;

            }
            
        }
        
        return array($arrEvents, $objPhotoTakenEvent);

    }
    
}

abstract class Event {
    public $PowerSecs;
    public $Secs;
}

class PowerOnEvent extends Event {
}

class AccessPointSightingEvent extends Event {
    public $MacAddress;
    public $SNR;
    public $Data;
}

class PhotoTakenEvent extends Event {
    public $FileName;
    public $Size;
}

class UnknownEvent extends Event {
    public $Content;
}

class AccessPoint {
    public $Age;
    public $MacAddress;
    public $SNR;
}