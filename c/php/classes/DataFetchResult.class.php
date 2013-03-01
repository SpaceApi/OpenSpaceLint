<?php

class DataFetchResult
{
    const NONE = 0;
    const CONTANT_GREATER_10_MEGS = 1;
    const BAD_STATUS = 2;
    
    private $url = "";
    private $http_code = 0;
    private $content_length = 0;
    private $content = null;
    private $error_code = 0;
    //private $headers = null;
    
    // limit is not very useful as a fetch result
    private $limit = true;
    
    public function __construct($url = "", $http_code = 0, $content_length = 0, $content = null, /*$headers = null,*/ $limit = true, $error_code = self::NONE)
    {
        $this->url = $url;
        $this->http_code = $http_code;
        $this->content_length = $content_length;
        $this->content = $content;
        $this->error_code = $error_code;
        //$this->headers = $headers;
        $this->limit = $limit;
    }
    
    /*******************************/
    // GETTER
    
    public function url()
    {
        return $this->url;
    }
    
    public function http_code()
    {
        return $this->http_code;
    }
    
    public function content_length()
    {
        return $this->content_length;
    }
    
    public function content()
    {
        return $this->content;
    }
    
    public function error_code()
    {
        return $this->error_code;
    }
    
    /*
    public function headers()
    {
        return $this->headers();
    }
    */
    
    public function limit()
    {
        return $this->limit();
    }
    
    /*******************************/
    // SETTER
    
    public function set_url($url)
    {
        $this->url = $url;
    }
    
    public function set_http_code($http_code)
    {
        $this->http_code = $http_code;
    }
    
    public function set_content_length($content_length)
    {
        $this->content_length = $content_length;
    }
    
    public function set_content($content)
    {
        $this->content = $content;
    }
    
    public function set_error_code($error_code)
    {
        $this->error_code = $error_code;
    }
    
    public function set_limit($limit)
    {
        $this->limit = $limit;
    }
}