<?php

class NiceFileName
{  
    /**
     * Creates a 'good' file name by replacing any non alphanumerical characters
     * with whitespaces.
     */
    public static function get($space_name)
    {
        // filter some characters which could cause some trouble
        // instead of preg_replace strtr() would be an alternative
        $file_name = preg_replace("/[^a-zA-Z0-9]/i", "_", $space_name);
        $file_name = strtolower($file_name);
        
        return $file_name;
    }

    /**
     * Creates a 'good' file name by replacing any non alphanumerical characters
     * with whitespaces. The file extension '.json' is appended
     */    
    public static function json($space_name)
    {
        return NiceFileName::get($space_name) . ".json";
    }
}