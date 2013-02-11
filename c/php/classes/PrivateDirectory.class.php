<?php

class PrivateDirectory extends SpaceDirectory
{    
    /**
     * Creates an instance of the private directory.
     */
    public function __construct()
    {
        parent::__construct("private");
    }
    
    /**
     * Adds a space to the private directory. This method should only be called
     * in the controller delegator 'directory' with the 'add' action. The passed
     * SpaceApiFile is exptected to have the status URL set.
     *
     * The direct status URL is inserted into the private directory.
     * 
     * @param SpaceApiFile $space_api_file A space api file.
     */
    public function add_space($space_api_file)
    {
        global $logger;
        
        $space_name = $space_api_file->name();
        
        if(! parent::has_space($space_name))
        {
            parent::add_space(
                $space_name,
                $space_api_file->status_url()
            );
        }
        else
            $logger->logNotice("The space '$space_name' is already in the private directory");
    }
    
}