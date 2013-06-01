<?php

    require_once("config/config.php");
	error_reporting( (DEBUG_MODE) ? E_ALL : 0 );

    class Page
	{
		private $prefetch_assets = array();
        private $scripts = array();
        private $stylesheets = array();
		private $inline_styles = array();
        private $content = "";
		private $active_page = "";
        
        /* not yet used */
        private $routes = array();
        
        public function __construct() {}
        
        public function addScript($script)
        {
            $this->scripts[] = $script;
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