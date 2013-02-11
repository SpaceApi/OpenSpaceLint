<?php

class FilterKeys
{
    public function __construct()
    {
        echo "create " . __CLASS__;
    }
    
    /**
     * Iterate over the passed associative array and find
     * all array key combinations with the nested ones
     * concatenated with a dot.
     *
     * E.g. $arr[a][b] would result in the found keys a and a.b
     *
     * a=contact
     * b=phone
     * -------------
     * nested keys are: contact and contact.phone
     *
     * Numerical indices are skipped and don't appear in any concatenated key.
     *  
     */
    private static function space_filter_keys($array, &$keys, $ancestor_key = "")
    {			
        foreach($array as $key => $value){
                        
            // skip the numerical indices
            if(!is_numeric($key))
                $new_key = $ancestor_key
                            . ((!empty($ancestor_key)) ? "." : "")
                            . $key;
            else
                $new_key = $ancestor_key;
                        
            // don't push the key if it is already present
            if(!in_array($new_key, $keys))									
                array_push($keys, $new_key);
                            
            if(is_array($value))
                self::space_filter_keys($value, $keys, $new_key);									
        }				
    }
    
    
    /**
     * Create an array with two lists listing what member a space uses.
     * The first array element contains a list sorted to the spaces
     * and the second is sorted to the members.
     */
    public static function get()
    {
        $sorted_to_space = array();
        $sorted_to_member = array();
        
        // create a list of what members a certain space supports
        foreach (glob( CACHEDIR ."status/*.json") as $filename)
        {
            $json = json_decode(file_get_contents($filename), true);				
            $members = array();
            self::space_filter_keys($json, $members);
            $sorted_to_space[$json["space"]] = $members;
        }
        
        // Create a list of what space uses a certain member.
        // Each element is an array containing spaces.
        foreach ($sorted_to_space as $space => $members)
        {
            foreach($members as $member)
            {
                if(!isset($sorted_to_member[$member]))
                    $val = array();
                else
                    $val = $sorted_to_member[$member];
                    
                array_push($val, $space);
                $sorted_to_member[$member] = $val;
            }
        }
        
        /*
        foreach($sorted_to_space as $space => $keys)
        $sorted_to_member = array_merge($sorted_to_member, $keys);
        
        $sorted_to_member = array_unique($sorted_to_member);
        sort($sorted_to_member);
        */
        
        return array($sorted_to_space, $sorted_to_member);
    }
}