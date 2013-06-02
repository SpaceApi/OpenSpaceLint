<?php

class Utils
{  
    /**
     * Recursively remove a directory.
     * 
     * Source: http://us.php.net/manual/en/function.rmdir.php#108113 (modified)
     */
    public static function rrmdir($dir)
    {
        if(null !== $dir && is_dir($dir))
        {
            $glob = glob($dir . '/*');
            if($glob)
            {
                foreach( $glob as $file)
                {
                    if(is_dir($file))
                        self::rrmdir($file);
                    else
                        unlink($file);
                }
            }
            
            rmdir($dir);
        }
    }
    
    /**
     * Indents a flat JSON string to make it more human-readable.
     *
     * @param string $json The original JSON string to process.
     *
     * @return string Indented version of the original JSON string.
     * @link http://recursive-design.com/blog/2008/03/11/format-json-with-php/
     */
    public static function json_pretty_print($json) {
    
        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;
    
        for ($i=0; $i<=$strLen; $i++) {
    
            // Grab the next character in the string.
            $char = substr($json, $i, 1);
    
            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;
            
            // If this character is the end of an element, 
            // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }
            
            // Add the character to the result string.
            $result .= $char;
    
            // If the last character was the beginning of an element, 
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }
                
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
            
            $prevChar = $char;
        }
    
        return $result;
    }
    
    
    /**
     * Prints the defined constants of a config file.
     *
     * @param string $config_file The full path to a config file
     */
    public static function print_config($config_file)
    {
        global $logger;
        
        if(!file_exists($config_file))
        {
            $logger->logDebug("The config file $config_file doesn't exist.");
            return;
        }
        
        $config_content = file_get_contents($config_file);
        preg_match_all("/define\('([^']+)/", $config_content, $matches);
        preg_match_all('/define\("([^"]+)/', $config_content, $matches2);
        
        // the subpattern is in $mathces[1]
        $constants = array_merge($matches[1], $matches2[1]);
        
        sort($constants);
        
        foreach($constants as $index => $constant)
            echo "$constant: ". constant($constant) ."\n";
    }
    
    
    /**
     * Sorts an array by key case-insensitive, maintaining key to data correlations. This is useful mainly for associative arrays.
     * 
     * @param array $array_arg Array to be sorted by key case-insensitive
     * @param bool $ascendent Sort the array ascendent (when true) or descendent (when false)
     */
    public static function ksort(&$array_arg, $ascendent = true)
    {
        function _ascendent($a, $b)
        {
            return strcasecmp($a, $b);
        }

        function _descendent($a, $b)
        {
            return strcasecmp($b, $a);
        }

        // don't declare one single function where this check is done,
        // in differen php version this seems not work equivalent,
        // the inner function seens not be able to access the outer $ascendent
        // variable in php 5.4.15
        $order = $ascendent ? '_ascendent' : '_descendent';
        uksort($array_arg, $order);
    }
}