eyefi-helper
============

This tool can be used in combination with other projects like https://code.launchpad.net/eyefi to handle the parsing and moving of the file.

# Configuration of eyefi-heper

You only need to copy the `config.php.sample` to `config.php` and modify the values.
```php
<?php
return [

  /**
   * destination contains the full path to the location where the files should be moved after processing.
   * You can use strftime placeholders to move the file to a specific location based on the date.
   * The full path will be automatically created when a file is moved.
   */
  'destination' => '/path/to/images/%Y/%Y-%m-%d/',

  /**
   * geocode the image based on the information from the .log files that your EyeFi card provides
   */
  'geocode' => true,

];
```

# Configuration of eyefi

I'm using the following config in `/etc/eyefi.conf`. The `run` parameter should match with the full path to process.php from this project.

    [__main__]
    port = 59278
    flickr_api = XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX:XXXXXXXXXXXXXXXX
    googleapis_key = XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

    [ffffffffffff]
    active = True
    macaddress = ffffffffffff
    uploadkey = XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    folder = /mnt/data/eyefi-upload/
    date_folders = False
    date_format = %%Y-%%m-%%d
    extract_preview = False
    geotag = False
    geotag_sidecar = False
    geotag_xmp = False
    geotag_delete_log = False
    run = /opt/eyefi-helper/process.php
    geeqie = False
    flickr = False
    flickr_public = False
