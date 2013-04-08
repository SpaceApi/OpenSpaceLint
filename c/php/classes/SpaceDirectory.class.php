<?php

// TODO: document and add the note that removal operations should be done by the subclasses
abstract class SpaceDirectory
{
    /**
     * Holds the file path to the directory json file.
     * Known subclasses of SpaceDirectory are PrivateDirectory
     * and PublicDirectory. Other subclasses may be added in the future.
     */
    protected $filepath = "";
    
    
    /**
     * The directory type. Known types are 'public' and 'private'.
     * The type is considered when entering
     */
    //protected $type = "";
    
    /**
     * Holds the reference to the logger instace
     */
    protected $logger = null;
    
    
    /**
     * The original content from the directory file
     */
    protected $dir_json = "";
    
    
    /**
     * The directory as an array
     */
    protected $dir_array = array();
    
    
    /**
     * The directory as an object
     */    
    protected $dir_obj = null;
    
    
    /**
     * A flag which defines whether the directory file could be loaded or not
     */
    protected $is_loaded = false;
    
    /**
     * Creates an instance of the private or public directory. It must
     * be explicitly defined whether to use the private or the public variant.
     * Whenever this class is used it should be obvious with which variant we
     * are dealing.
     * 
     * @throws Exception when the variant is not explicitly set to private or public
     */
    public function __construct($type)
    {
        global $logger;
        $this->logger = $logger;
        $this->logger->logDebug("Creating a directory instance");
     
        /*   
        if($variant != "private" && $variant != "public")
        {
            $this->logger->logDebug("A wrong variant was given.");
            throw new Exception("Wrong variant");
            $variant = "private";
        }
        */
        
        $this->filepath = DIRECTORYDIR . 'directory.json.' . $type;;
        $this->load();
    }
    
    /**
     * Returns true if a space is in the directory.
     *
     * @param string $space_name A space name. It can be sanitized or not.
     * @param bool $sanitized A flag which denotes the space name sanitized if it'ss true 
     */
    public function has_space($space_name, $sanitized = false)
    {
        if($sanitized)
            $space_name = $this->get_original_space_name($space_name);
            
        return in_array($space_name, array_keys($this->dir_array));
    }
    
    
    /**
     * Returns the original space name from a sanitized one. If it
     * cannot be found an empty string is returned.
     *
     * @param string $sanitized_space_name A sanitized space name
     */
    public function get_original_space_name($sanitized_space_name)
    {
        foreach($this->dir_array as $space => $url)
            if(NiceFileName::get($space) == $sanitized_space_name)
                return $space;
            
        return "";
    }
    
    
    /**
     * Returns the URL for a space name. An empty string is returned if the space is not
     * in the directory.
     * 
     * @param string $space_name A space name. It can be sanitized or not.
     * @param bool $sanitized A flag which denotes the space name sanitized if it'ss true
     */
    // TODO: $sanitized is deprecated, check where it's used
    public function get_url($space_name, $sanitized = false)
    {
        $space_name = NiceFileName::get($space_name);
        $space_name = $this->get_original_space_name($space_name);
            
        if($this->has_space($space_name))
            return $this->dir_array[$space_name];
        else
            return "";
    }

    /**
     * Loads the directory
     */
    protected function load()
    {
        if(file_exists($this->filepath))
        {
            $this->dir_json = file_get_contents($this->filepath);
            $this->dir_array = json_decode($this->dir_json, true);
            $this->dir_obj = json_decode($this->dir_json);
            
            // when the json was successfully decoded we flag the directory as loaded
            if($this->dir_array !== null && $this->dir_obj !== null)
                $this->is_loaded = true;
            else
                $this->logger->logError("Could not decode the json");
        }    
    }

    
    /**
     * Depending on the GET parameters the directory is returned as a json string,
     * an array with a specific format or a subset of it.
     */
    public function get()
    {
        if($this->special_array_format_requested())
            return $this->get_special_array();
        
        if($this->subset_by_list_requested())
            return $this->get_subset_by_list();
        
        
        if($this->subset_by_filter_requested())
            return $this->get_subset_by_filter();
        
        if($this->subset_by_api())
            return $this->get_subset_by_api();
        
        return $this->dir_json;   
    }
    
    
    /**
     * Returns the stdClass object of the directory.
     */
    public function get_stdClass()
    {
        return $this->dir_obj;
    }

    
    /**
     * Returns true if the special array format should be printed
     */
    protected function special_array_format_requested()
    {
        if(isset($_GET['fmt']) && $_GET['fmt']=='a')
            return true;
        
        global $argv;
        if(isset($argv))
            foreach($argv as $val)
                if($val == "fmt=a")
                    return true;
                
        return false;
    }


    /**
     * Returns true if a directory subset should be printed from a given list
     */
    protected function subset_by_list_requested()
    {
        if(isset($_GET['space']))
           return true;
        
        global $argv;
        if(isset($argv))
            foreach($argv as $val)
                if(preg_match("/space=.*/", $val))
                    return true;
                
        return false;
    }
    
    
    /**
     * Returns true if a directory subset should be printed from given filter keys
     */
    protected function subset_by_filter_requested()
    {
        if(isset($_GET['filter']))
           return true;
        
        global $argv;
        if(isset($argv))
            foreach($argv as $val)
                if(preg_match("/filter=.*/", $val))
                    return true;
                
        return false;
    }


    /**
     * Returns true if a directory for a specific specs version should be returned to the client
     */
    protected function subset_by_api()
    {
        if(isset($_GET['api']))
           return true;
        
        global $argv;
        if(isset($argv))
            foreach($argv as $val)
                if(preg_match("/api=.*/", $val))
                    return true;
                
        return false;
    }
    
    
    /**
     * Returns a directory subset with spaces as requested by a list
     */
    // TODO: document, make it public to the world
    protected function get_subset_by_list()
    {
        if(SAPI == 'cli')
        {
            // we already did the check for the existence of the filter argument
            global $argv;
            foreach($argv as $val)
                if(preg_match("/space=.*/", $val))
                    $spaces = str_replace("space=", "", $val);
        }
        else
            $spaces = stripslashes(strip_tags($_GET["space"]));
            
        $spaces = explode(",", $spaces);
        sort($spaces);
        
        $arr = array();
        foreach($spaces as $space)
        {
            if(isset($this->dir_array[$space]))
                $arr[$space] = $this->dir_array[$space];
            else
                $this->logger->logWarn("There's no '$space' in the directory");
        }
           
        return json_encode((object) $arr);
    }

    /**
     * Returns a directory subset with spaces implementing a certain specs version
     */
    // TODO: document, make it public to the world
    protected function get_subset_by_api()
    {
        if(SAPI == 'cli')
        {
            // we already did the check for the existence of the api argument
            global $argv;
            foreach($argv as $val)
                if(preg_match("/api=.*/", $val))
                    $version = str_replace("api=", "", $val);
        }
        else
            $version = stripslashes(strip_tags($_GET["api"]));
        
        $spaces = new stdClass;
        
        foreach(glob( STATUSCACHEDIR ."*.json") as $filename)
        {
            $json = file_get_contents($filename);
            $space_api_file = new SpaceApiFile($json);
            if($space_api_file->version() == $version)
            {
                $space_name = $space_api_file->name();
                $endpoint_url = $this->get_url($space_name);
                if(!empty($endpoint_url))
                    $spaces->$space_name = $endpoint_url;
            }
        }
           
        return json_encode($spaces);    
    }
    
    /**
     * Returns a directory subset by given filter keys
     */
    protected function get_subset_by_filter()
    {
        global $logger;
        
        if(SAPI == 'cli')
        {
            // we already did the check for the existence of the filter argument
            global $argv;
            foreach($argv as $val)
                if(preg_match("/filter=.*/", $val))
                    $filters = str_replace("filter=", "", $val);
        }
        else
            $filters = stripslashes(strip_tags($_GET["filter"]));

        $logger->logInfo("Calculating the directory subset based on the given filter '$filters'");

        //$array_keys_json = file_get_contents("../cache/array_keys.json");
        //$array_keys_arr = json_decode($array_keys_json, true);
        $array_keys_arr = FilterKeys::get();
        
        // a slogix expression or a json can be used to define the filters
        if($slogix = Slogix::decode($filters))
            $filters = $slogix;
        else			
            if($json = json_decode($filters, true))
                $filters = $json;
        
        if(gettype($filters) === "string")
            $filters = array("or" => array($filters));
        
        // input is a boolean expression as an abstract syntax tree
        // and the sets whose keys which are used in the expression
        $spaces = Slogix::evaluate($filters, $array_keys_arr[1]);				
        sort($spaces);
        
        $arr = array();
        foreach($spaces as $space)
        {
            //$arr[$space] = $directory_array[$space];
            if(isset($this->dir_array[$space]))
                $arr[$space] = $this->dir_array[$space];
            else
                $logger->logWarn("'$space' is not in the directory.");
        }
        
        return json_encode($arr);
    }
    
    /**
     * Creates a special formatted array required for some apps. The directory
     * will be output in the format looking like
     *
     *     {
     *       "spaces": [
     *           {
     *               "name": "091 Labs",
     *               "url": "http://scruffy.091labs.com/lolo/json/status.json"
     *           },
     *           {
     *               "name": "Ace Monster Toys",
     *               "url": "http://acemonstertoys.org/status.json"
     *           },
     *           ...
     *       ]
     *     }
     */
    protected function get_special_array()
    {       
        $dir = array();
        
        foreach($this->dir_array as $name => $url)
        {
            $entry = new stdClass;
            $entry->name = $name;
            $entry->url = $url;
            
            $dir[] = $entry;
        }
        
        return json_encode((object) array( 'spaces' => $dir ));
    }
    
    /**
     * Adds a space to the directory. No check is performed if the space has
     * already been added to the directory. It's the responsibility of the
     * subclass to do this test.
     *
     * @param string $space_name A space name. It must not be sanitized.
     * @param string $url The space status URL. It can be a cache URL.
     */
    protected function add_space($space_name, $url)
    {
        global $logger;
        
        $this->dir_array[$space_name] = $url;
        $this->save($this->dir_array);
        $logger->logNotice("The space '$space_name' with the status URL '$url' is added to the directory");
    }
    
    /**
     * Writes the directory back to the file while reformatting the structure.
     *
     * @param mixed $mixed An array, stdClass object or a json string
     */
    protected function save($mixed)
    {
        global $logger;
        
        if(gettype($mixed) == "string")
        {
            $mixed = json_decode($mixed);
            
            // check if the string was really a json
            if( null === $mixed )
            {
                $logger->logError("The json string passed to save() is not a valid.");
                return;
            }
        }
        
        // here $mixed should be an object
        $mixed = (array) $mixed;
        
        // sort the directory alphanumerical
        // TODO: this makes some trouble, when adding a space it's added to the private but not the public directory
        //Utils::ksort($mixed);
        
        $json_str = json_encode($mixed);
        //$logger->logInfo("Writing the following string back to the file:\n". $json_str);
        
        // reformat the json string
        $json_str = Utils::json_pretty_print($json_str);
        //$json_str = str_replace("{", "{\n\t", $json_str);
        //$json_str = str_replace("}", "\n}", $json_str);
        //$json_str = str_replace(",", "\n\t", $json_str);
        
        file_put_contents($this->filepath, $json_str);
    }
}