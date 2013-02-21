<?php

class SpaceApiValidator
{
    private $errors = null;
    private $valid_versions = array();
    private $invalid_versions = array();
    
    /**
     * Validates the a space api implementation against
     * all the specs versions. If one is valid true is returned
     * else false. If only the api version number doesn't match
     * the allowed value for a specification being checked, the
     * space api implementation is considered correct and appears
     * in the valid array in the test results.
     * 
     * @param string $space_json A space api implementation
     * @return bool True if the space api file is valid against at least one specs version
     */
    public function validate($space_api_file)
    {
        global $logger;
        
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
        $results = new stdClass;
        
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
                    $results->$extended_version = (array) $errors;
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
        $error_messages = json_encode($results);
        $error_messages = str_replace("root.", "", $error_messages);
        $this->errors = json_decode($error_messages);
        
        return (count($this->valid_versions) > 0);
    }
    
    /**
     * Returns the error messages.
     */
    public function get_errors()
    {
        return $this->errors;
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
    
}