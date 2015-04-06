<?php
require_once('vendor/autoload.php');

/*
 * Load Log
 */
$objEyeFiLogParser = new MichaelArnauts\EyeFiLogParser\EyeFiLogParser('DSC_9240.JPG.log');
//$arrEyeFiAccessPoints = $objEyeFiLogParser->GetAccessPoints('DSC_9240.JPG');
$arrEyeFiAccessPoints = $objEyeFiLogParser->GetAccessPoints('DSC_8737.JPG');

/*
 * Convert Access Points
 */
$arrAccessPoints = array();
foreach ($arrEyeFiAccessPoints as $objEyeFiAccessPoint) {
    $objAccessPoint = new MichaelArnauts\GeolocationLookup\AccessPoint();
    $objAccessPoint->age = $objEyeFiAccessPoint->Age * 1000;
    $objAccessPoint->macaddress = $objEyeFiAccessPoint->MacAddress;
    $objAccessPoint->snr = intval(log10($objEyeFiAccessPoint->SNR / 100) * 10 - 50);
    $arrAccessPoints[] = $objAccessPoint;
}

/*
 * Query GPS details
 */
$objGoogleGeolocationLookup = new MichaelArnauts\GeolocationLookup\GoogleGeolocationLookup();
$objLookup = $objGoogleGeolocationLookup->Lookup($arrAccessPoints);

var_dump($objLookup);