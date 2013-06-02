<?php

class SpaceApiValidator
{
    private $errors = null;
    private $warnings = array();
    private $valid_versions = array();
    private $invalid_versions = array();
    
    private $plugins = array();
    
    /**
     * Initializes or resets the errors, valid and invalid versions.
     * This allows to reuse the same validator instance.
     */
    private function init()
    {
        $this->errors = new stdClass;
        $this->warnings = array();
        $this->valid_versions = array();
        $this->invalid_versions = array();
    }
    
    /**
     * Validates the a space api implementation against
     * all the specs versions. If one is valid true is returned
     * else false. If only the api version number doesn't match
     * the allowed value for a specification being checked, the
     * space api implementation is considered correct and appears
     * in the valid array in the test results.
     *
     * Every call of this method resets any previous results.
     * 
     * @param string $space_json A space api implementation
     * @return bool True if the space api file is valid against at least one specs version
     */
    public function validate($space_api_file)
    {
        global $logger;
        
        $this->init();
        
        //$space_json = $space_api_file->
        $validator = null;
        
        $versions = array();

        // get all the versions
        foreach(glob( SPECSDIR ."*.json") as $filename)
        {
            $json = file_get_contents($filename);
            $filename = basename($filename);
            
            $versions[] = str_replace(".json", "", $filename);
        }
       
        // we have to use a stdClass and no array because we use
        // the specs version as the index which would lead to an
        // associative array which is always encoded to a json
        // object when the indices are not continuous (0, 1, 2 ...).
        // when using a sdtClass the top level element is always
        // an object no matter it's filled with error messages
        // or not
        //$ = new stdClass;
        
        // sort so that the latest version is checked first
        rsort($versions);
        
        foreach($versions as $index => $version)
        {            
            $filename = SPECSDIR . $version . ".json";
            
            $extended_version = "0.$version";
            
            try
            {
                $validator = new JsonSchemaValidator($filename);
                //$json_obj = json_decode($space_json);
                //$passed_specs_test =
                $validator->validate($space_api_file->json());
                $this->valid_versions[] = $extended_version;
            }
            catch(ValidationException $e)
            {
                // this is not the member but a local variable
                $errors = $validator->getErrors();
                
                // if the error is the 'wrong api version number'
                // error then remove it. to check this we need to
                // iterate over all errors and all specs versions
                foreach($errors as $error_index => $error)
                {
                    // $errors 
                    /*                    
                        "0.13":[
                          {
                            "msg":"Invalid value(s) for [api], allowed values are [0.13]",
                            "description":"The space api version you use"
                          }
                        }
                    */
                    
                    // we mustn't use $version here because we would
                    // the loop has not it's own scope and thus
                    // the previous $version would be overridden
                    foreach($versions as $version_index => $v)
                    {
                        if(false !== strpos($error->msg, "0.$v"))
                        {
                            // * unset() is not appropriate because it leaves a whole in the array
                            //   which leads to an array->object conversion when json-encoding the array
                            // * array_shift is not good because we assume then that the
                            //   'wrong api version' error message is always on top
                            array_splice($errors, $error_index, 1);
                        }
                    }
                    
                }
                
                if(count($errors)>0)
                {
                    $this->errors->$extended_version = (array) $errors;
                    $this->invalid_versions[] = $extended_version;
                }
                else
                    $this->valid_versions[] = $extended_version;
            }
            catch(SchemaException $e)
            {
                $logger->logError($e->getMessage());
            }
        }
        
        // it's ok to encode an empty array
        $this->errors = json_encode($this->errors);
        $this->errors = str_replace("root.", "", $this->errors);
        $this->errors = json_decode($this->errors);
        
        // we must assume that a plugin is changing the errors,
        // warnings, valid versions or invalid versions arrays
        $this->process_plugins($space_api_file);
        
        // the order of versions might not be ascendent after processing the plugins
        $this->sort_member_invalid_versions();
        $this->sort_member_valid_versions();
        $this->sort_member_errors(); // relies on sort_member_invalid_versions()
        
        return (count($this->valid_versions) > 0);
    }
    
    
    private function sort_member_invalid_versions()
    {
        if(! empty($this->invalid_versions))
            $this->invalid_versions = $this->sort_version_array($this->invalid_versions);
    }


    private function sort_member_valid_versions()
    {
        if(! empty($this->valid_versions))
            $this->valid_versions = $this->sort_version_array($this->valid_versions);
    }
    
    
    private function sort_version_array($version_array)
    {                
        global $logger;
        
        // remove the prefix '0.' so that we can define a ordinal order because 0.8 is less than 0.13
        // in the specs but mathematically 0.8 greater than 0.13.
        $va = preg_replace("/0./", "", $version_array);
        
        // sort ascendent
        sort($va);
        $va = array_reverse($va);
        
        // add the '0.' prefix again
        $new_array = array();
        foreach($va as $index => $value)
            $new_array[] = "0.$value";
            
        return $new_array;
    }
    
    /**
     * Sorts the error array. Make sure that the invalid versions array is sorted descendent when calling this method!
     */
    private function sort_member_errors()
    {
        $errors = new stdClass;
        
        foreach($this->invalid_versions as $index => $version)
        {
            if(property_exists($this->errors, $version))
                $errors->$version = $this->errors->$version;
        }
        
        $this->errors = $errors;
    }
    
    /**
     * Returns the error messages.
     */
    public function get_errors()
    {
        return $this->errors;
    }

    /**
     * Returns the warnings.
     */
    public function get_warnings()
    {
        return $this->warnings;
    }
    
    /**
     * Returns the valid versions
     */    
    public function get_valid_versions()
    {
        return $this->valid_versions;
    }
    
    /**
     * Returns the invalid versions
     */
    public function get_invalid_versions()
    {
        return $this->invalid_versions;
    }
    
    /**
     * Register an additional validation checker.
     */
    public function register($validation_function)
    {
        if(! in_array($validation_function, $this->plugins))
            $this->plugins[] = $validation_function;
    }
    
    /**
     * Execute the plugins and return occured error messages.
     *
     * @param SpaceApiFile $space_api_file A space api file
     */
    private function process_plugins($space_api_file)
    {
        global $logger;        
        //$results = array();
        
        //$plugin_arguments = $this->get_plugin_arguments();
        //$plugin_arguments["space_api_file"] = $space_api_file;
        
        // we need this variable because the plugins use it
        // to register themselves
        $space_api_validator = &$this;
        
        // load all the plugins
        foreach(glob(PLUGINDIR ."*.php") as $filename)
            require_once($filename);
        
        // execute the plugins
        foreach($this->plugins as $plugin)
            call_user_func_array($plugin, array($space_api_file, &$this->errors, &$this->warnings, &$this->valid_versions, &$this->invalid_versions));
    }
    
    /**
     * Return a set of arguments which are passed to the plugins
     */
    private function get_plugin_arguments()
    {
        global $logger;
        
        $schemas = array();
        
        // get the schemas
        foreach(glob( SPECSDIR ."*.json") as $filename)
        {
            $json = file_get_contents($filename);
            $filename = basename($filename);
            
            $version = str_replace(".json", "", $filename);
            
            $schemas[$version] = $json;
        }
        
        $directory = new PrivateDirectory;
        
        return array(
            "directory" => $directory->get(),
            "schemas" => $schemas
        );
    }
}