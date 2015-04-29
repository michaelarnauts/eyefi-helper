#!/usr/bin/php
<?php

// Start autoloader
require_once('vendor/autoload.php');

use Monolog\Logger;
use Monolog\Handler\SyslogHandler;
use MichaelArnauts\FileMover\FileMover;
use MichaelArnauts\EyeFiLogParser\EyeFiLogParser;
use MichaelArnauts\GeolocationLookup\GoogleGeolocationLookup;

// Load config
if (!file_exists(__DIR__ . '/config.php')) {
  echo "config.php file not found.\n";
  exit(1);
}
$config = require_once('config.php');

// Check parameters
if (!isset($argv[1])) {
    echo sprintf("Syntax: process.php [FILENAME.JPG]\n");
    exit(1);
}

// Setup logging
$logger = new Logger('eyefi-helper');
//$logger->pushHandler(new SyslogHandler('eyefi-helper'));        

// Create FileMover
try {
    $objFileMover = new FileMover($logger, $argv[1], $config['destination']);
} catch (Exception $ex) {
    $logger->error(sprintf("Could not open file: %s\n", $ex->getMessage()));
    exit(1);
}

// Geocode
if ($config['geocode']) {

    try {

        // Load Log
        $objEyeFiLogParser = new EyeFiLogParser($objFileMover->getPath() . '.log');
        $arrEyeFiAccessPoints = $objEyeFiLogParser->GetAccessPoints($objFileMover->getFilename());

        if ($arrEyeFiAccessPoints) {

            // Convert Access Points
            $arrAccessPoints = array();
            foreach ($arrEyeFiAccessPoints as $objEyeFiAccessPoint) {
                $objAccessPoint = new MichaelArnauts\GeolocationLookup\AccessPoint();
                $objAccessPoint->age = $objEyeFiAccessPoint->Age * 1000;
                $objAccessPoint->macaddress = $objEyeFiAccessPoint->MacAddress;
                $objAccessPoint->snr = intval(log10($objEyeFiAccessPoint->SNR / 100) * 10 - 50);
                $arrAccessPoints[] = $objAccessPoint;
            }
        
            // Query GPS details
            $objGoogleGeolocationLookup = new GoogleGeolocationLookup();
            $objLookup = $objGoogleGeolocationLookup->Lookup($arrAccessPoints);

            // Set EXIF data
            $objFileMover->setCoordinates($objLookup->latitude, $objLookup->longitude);
        }
        
    } catch (Exception $ex) {
        $logger->error(sprintf("Could not geocode: %s\n", $ex->getMessage()));
    }

}

// Move file to destination
$objFileMover->move();
