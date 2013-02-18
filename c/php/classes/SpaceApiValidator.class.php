<?php

class SpaceApiValidator
{
    private $errors_json = "";
    
    /**
     * Validates the a space api implementation against
     * all the specs versions. If one is valid true is returned
     * else false. The value of the api field does not
     * strictly lead to a validation failure towards different
     * versions. In other words, a space api implementation
     * could be compliant to a version other than the specified
     * version.
     * 
     * @param string $space_json A space api implementation
     */
    public function validate($space_api_file)
    {
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
        $ret = new stdClass;
        
        // sort so that the latest version is checked first
        rsort($versions);
        
        $passed_specs_test = false;
        
        //foreach(glob( SPECSDIR ."*.json") as $filename)
        // iterate over the specs version
        foreach($versions as $index => $version)
        {            
            $filename = SPECSDIR . $version . ".json";
            
            try
            {
                $validator = new JsonSchemaValidator($filename);
                //$json_obj = json_decode($space_json);
                $passed_specs_test = $validator->validate($space_api_file->json());
            }
            catch(Exception $e)
            {
                $errors = $validator->getErrors();
                
                // if the first element is the 'wrong version number
                // error, we check if the value is one of the specs
                // versions, if so we delete that error to reduce the
                // noise wherever the error messages are emailed/output
                if(false !== strpos($errors[0], "root.api"))
                {
                    // this iteration is not reduntant, here we look
                    // if the space as specified one valid version
                    foreach($versions as $index => $v)
                    {
                        if(false !== strpos($errors[0], "0.".$v))
                        {
                            array_shift($errors);
                        }
                    }
                }
                
                // we need to prepend 0. because the leading 0
                // is not part of the filename
                if(count($errors)>0)
                {
                    $v = "0.$version";
                    $ret->$v = (array) $errors;
                }
            }
        }
       
        // it's ok to encode an empty array
        $error_messages = json_encode($ret);
        $error_messages = str_replace("root.", "", $error_messages);
        $this->errors_json = $error_messages;
        
        return $passed_specs_test;
    }
    
    /**
     * Returns a json with the error messages.
     */
    public function get_errors()
    {
        return $this->errors_json;
    }
}