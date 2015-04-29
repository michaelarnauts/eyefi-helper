<?php

namespace MichaelArnauts\FileMover;

use DateTime;
use PHPExiftool\Reader;
use PHPExiftool\Writer;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Metadata\MetadataBag;
use PHPExiftool\Driver\Tag\GPS\GPSLatitudeRef;
use PHPExiftool\Driver\Tag\GPS\GPSLatitude;
use PHPExiftool\Driver\Tag\GPS\GPSLongitudeRef;
use PHPExiftool\Driver\Tag\GPS\GPSLongitude;
use PHPExiftool\Driver\Value\Mono;

class FileMover {
    
    private $source;
    private $destination;
    
    private $filetype;
    private $longitude = null;
    private $latitude = null;
    
    /** @var \Monolog\Logger $logger */
    private $logger;
    
    /**
     * 
     * @param type $file
     * @throws Exception
     */
    public function __construct($logger, $source, $template) {
        
        $this->logger = $logger;
        
        // Check filename
        if (!file_exists($source)) {
            throw new \Exception(sprintf("File %s doesn't exist", $source));
        }
        $this->source = $source;
        
        // Read EXIF data
        $this->readExif();
        
        // Set destination based on timestamp
        $this->destination = strftime($template, $this->timestamp->getTimestamp()) . basename($this->source);
        
    }
    
    /**
     *  
     * @param type $latitude
     * @param type $longitude
     */
    public function setCoordinates($latitude, $longitude) {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }
    
    /**
     * 
     */
    public function move() {
        
        // Ensure destination path exists
        if (!is_dir(dirname($this->destination))) {
            mkdir(dirname($this->destination), 0777, true);
        }
        
        // Copy the file
        rename($this->source, $this->destination);
        
        // Write EXIF data
        $this->writeExif();

    }
    
    /**
     * @returns DateTime
     */
    private function readExif() {

        // Read metadata
        $reader = Reader::create($this->logger);
        $metadatas = $reader->files($this->source)->first();
        
        $this->filetype = $metadatas->executeQuery('File:MIMEType');
        
        switch ($this->filetype) {
            case 'image/jpeg':
            case 'image/x-nikon-nef':
                if ($date = $metadatas->executeQuery('ExifIFD:CreateDate')) {
                    $this->timestamp = DateTime::createFromFormat('Y:m:d H:i:s', $date);
                }
                break;
                
            case 'video/quicktime':
                if ($date = $metadatas->executeQuery('QuickTime:CreateDate')) {
                    $this->timestamp = DateTime::createFromFormat('Y:m:d H:i:s', $date);
                }
                break;                

        }

        if (!$this->timestamp) {
            // Fallback to FileModifyDate
            $this->timestamp = DateTime::createFromFormat('Y:m:d H:i:sP', $metadatas->executeQuery('System:FileModifyDate'));
        }
        
        $this->logger->info(sprintf('Read EXIF info from %s', $this->source));

    }
    
    private function writeExif() {

        // Only write when we have coordinates available
        if (!$this->latitude || !$this->longitude) {
            return false;
        }
        
        switch ($this->filetype) {
            case 'image/jpeg':
            case 'image/x-nikon-nef':
                $Writer = Writer::create($this->logger);

                $bag = new MetadataBag();

                $bag->add(new Metadata(new GPSLatitudeRef(), new Mono( $this->latitude < 0 ? 'S' : 'N' )));
                $bag->add(new Metadata(new GPSLatitude(), new Mono( abs($this->latitude) )));
                $bag->add(new Metadata(new GPSLongitudeRef(), new Mono( $this->longitude < 0 ? 'W' : 'E' )));
                $bag->add(new Metadata(new GPSLongitude(), new Mono( abs($this->longitude) )));

                $Writer->write($this->destination, $bag);

                $this->logger->info(sprintf('Wrote EXIF to %s', $this->destination));
                return true;
                
            case 'video/quicktime':
                return false;

        }

    }
    
    /**
     * 
     * @return string
     */
    public function getFilename() {
        return basename($this->source);
    }
    
    /**
     * 
     * @return type
     */
    public function getPath() {
        return $this->source;
    }
    
    /**
     * 
     * @return string
     */
    public function getFiletype() {
        return $this->sourcetype;
    }
    
    
}
