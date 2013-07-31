<?php

class Page
{
    private $prefetch_assets = array();
    private $scripts = array();
    private $require_global_scripts = array();
    private $stylesheets = array();
    private $inline_styles = array();
    private $content = "";
    private $app_dir = "";

    /* not yet used */
    private $routes = array();

    public function __construct($page_id)
    {
        $this->app_dir = _APPSDIR . $page_id;
    }

    public function process_backend_route($delegator = "", $action = "", $resource = "")
    {
        // get the headers list before processing the route
        // $before = headers_list();

        ob_start();
        Route::execute($delegator, $action, $resource);
        $result = ob_get_contents();
        ob_end_clean();

        // get the possibly changed headers list
        //$after = headers_list();

        // recover the old headers
        //header_remove();
        /*
        // doesn't work, the content type is application/json anyway
        // after the loop
        foreach(array_diff($after, $before) as $header)
        {
            $header = preg_replace("|:.*|", "", $header);
            header_remove($header);
        }
        */

        // we need to fix the content-type
        // if other headers were set they possibly need to be
        // overridden too if they make trouble, think also about
        // not visible effects
        header("Content-Type: text/html");
        return $result;
    }

    /**
     * Adds an app script to be loaded in the header
     * @param string $script
     * @param boolean $is_global Flag to denote if it's a script from the global to be included
     */
    // TODO: this method should allow a flag whether the script should be loaded in the head or on the bottom of the page
    public function addScript($script, $is_global = false)
    {
        $path = "";
        if(! $is_global)
            $path = $this->app_dir .'/';

        $this->scripts[] = $path . $script;
    }

    /**
     * Adds a script to the required scripts array which must be loaded before the app scripts
     * @param $script
     */
    public function requireScript($script)
    {
        $this->require_global_scripts[] = $script;
    }

    public function requireScripts()
    {
        return $this->require_global_scripts;
    }

    /**
     * @param $stylesheet
     * @param boolean $is_global Flag to denote if it's a script from the global to be included
     */
    public function addStylesheet($stylesheet, $is_global = false)
    {
        $path = "";
        if(! $is_global)
            $path = $this->app_dir .'/';

        $this->stylesheets[] = $path . $stylesheet;
    }

    public function addInlineStyle($style)
    {
        $style = $this->processPlaceholders($style);
        $this->inline_styles[] = $style;
    }

    public function inlineStyles()
    {
        return $this->inline_styles;
    }

    private function processPlaceholders($str)
    {
        $str = str_replace("%APPDIR%", $this->app_dir, $str);
        $str = str_replace("%SITEURL%", SITE_URL, $str);

        return $str;
    }

    public function addContent($content)
    {
        $content = $this->processPlaceholders($content);
        $this->content .= $content;
    }

    public function addPrefetchAsset($asset)
    {
        $asset = $this->processPlaceholders($asset);
        $this->prefetch_assets[] = $asset;
    }

    public function content()
    {
        return $this->content;
    }

    public function scripts()
    {
        return $this->scripts;
    }

    public function stylesheets()
    {
        return $this->stylesheets;
    }

    public function prefetchAssets()
    {
        return $this->prefetch_assets;
    }

    public function activePage()
    {
        return basename($this->app_dir);
    }
}