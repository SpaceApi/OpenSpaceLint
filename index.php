<?php

    require_once("config/config.php");
	error_reporting( (DEBUG_MODE) ? E_ALL : 0 );

    // this is in the global space, maybe not a good idea
    // don't put this into a Utils class, if an app is using an autoloader to use the backend classes
    // this Utils might conflict with the backend's Utils.
    function amount_per_column($mixed, $amount_columns)
    {
        $amount = 0;

        switch(true)
        {
            case is_array($mixed): $amount = count($mixed); break;
            case is_object($mixed): $amount = count((array) $mixed); break;
            default: // should never happen
        }

        $amount_per_column = array();
        $equal_amount = floor($amount / $amount_columns);

        for($i=0; $i < $amount_columns; $i++)
            $amount_per_column[] = $equal_amount;

        $rest = $amount % $amount_columns;
        for($i=0; $i < $rest; $i++)
            $amount_per_column[$i]++;

        return $amount_per_column;
    }

    class Page
	{
		private $prefetch_assets = array();
        private $scripts = array();
        private $require_global_scripts = array();
        private $stylesheets = array();
		private $inline_styles = array();
        private $content = "";
		private $active_page = "";
        
        /* not yet used */
        private $routes = array();
        
        public function __construct() {}

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

        public function addScript($script)
        {
            $this->scripts[] = $script;
        }

        public function requireScript($script)
        {
            $this->require_global_scripts[] = $script;
        }

        public function requireScripts()
        {
            return $this->require_global_scripts;
        }
        
        public function addStylesheet($stylesheet)
        {
            $this->stylesheets[] = $stylesheet;
        }
		
		public function addInlineStyle($style)
		{
			$this->inline_styles[] = $style;
		}
		
		public function inlineStyles()
		{
			return $this->inline_styles;
		}
        
        public function addContent($content)
        {
            $this->content .= $content;
        }
		
		public function addPrefetchAsset($asset)
		{
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
		
		public function setActivePage($page)
		{
			$this->active_page = $page;
		}
		
		public function activePage()
		{
			return $this->active_page;
		}
    }

    $page = new Page();
    
    // get the page that should be displayed, note that REDIRECT_URL
    // is set by apache after the rewrite rule was processed
    $requested_app = str_replace("/", "", $_SERVER['REDIRECT_URL']);

	// if no app is requested the introduction page will be shown
	if( empty($requested_app) )
		$requested_app = "intro";
	
	// we must whitelist the input
	$load_app = APPSDIR . "error-page";
    foreach(glob( APPSDIR . "*") as $app_dir)
    {
        $app = str_replace(APPSDIR , "", $app_dir);
		if ( "$requested_app"    == $app )
			$load_app = $app_dir;
    }
	include($load_app . "/app.php");
    
	$output = file_get_contents("template.html");
	$output = str_replace("%CONTENT%", $page->content(), $output);
	
	/*************************************************************/
	
	$prefetch_tags = "";
	foreach($page->prefetchAssets() as $asset)
	{
		if(! empty($asset))
			$prefetch_tags .= '<link rel="prefetch" href="'. $asset .'" >';
	}
	
	$output = str_replace("%PREFETCHASSETS%", $prefetch_tags, $output);
	
	/*************************************************************/
	
	$script_tags = "";
	foreach($page->scripts() as $script)
	{
		if(! empty($script))
			$script_tags .= '<script src="'. $script .'"></script>';	
	}
	
	$output = str_replace("%SCRIPTS%", $script_tags, $output);
	
	/*************************************************************/

    $global_script_tags = "";
    foreach($page->requireScripts() as $script)
    {
        if(! empty($script))
            $global_script_tags .= '<script src="c/js/'. $script .'"></script>';
    }

    $output = str_replace("%REQUIRE_GLOBAL_SCRIPTS%", $global_script_tags, $output);

    /*************************************************************/

	$stylesheet_tags = "";	
	foreach($page->stylesheets() as $stylesheet)
	{
		if(! empty($stylesheet))
			$stylesheet_tags .= '<link type="text/css" rel="stylesheet" href="'. $stylesheet .'">';
	}
	
	$output = str_replace("%STYLESHEETS%", $stylesheet_tags, $output);
	
	/*************************************************************/

	$inline_style_tag = "<style>". join("", $page->inlineStyles()) ."</style>";
	$output = str_replace("%INLINESTYLES%", $inline_style_tag, $output);
	
	/*************************************************************/
	
	// populate the menu
	include( APPSDIR . "menu.php");
	
	$menu_tags = "";
	foreach($menu as $key => $label)
	{
		$class = "";
		if( $key == $page->activePage() )
			$class = "active";
		
		$menu_tags .= '<li class="'. $class .'"><a href="'. $key .'">'. $label .'</a></li>';
	}
	
	$output = str_replace("%MENU%", $menu_tags, $output);
	
    echo $output;