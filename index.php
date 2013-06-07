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

    function dump($mixed, $is_html = false)
    {
        //if($is_html)
            //echo "<pre>" . print_r($mixed, true) . "</pre>";
        //else
            echo "<pre>" . htmlspecialchars(print_r($mixed, true)) . "</pre>";
    }

    function dumpx($mixed)
    {
        dump($mixed);
        exit();
    }

    function make_columns($data, $amount_columns, $attributes = array())
    {
        if(count($data) == 0)
            return;

        $list_type = isset($attributes['list_type']) ? $attributes['list_type'] : 'ul';
        $row_id = isset($attributes['row_id']) ? $attributes['row_id'] : '';
        $row_class = isset($attributes['row_class']) ? $attributes['row_class'] : '';
        $before_text = isset($attributes['before_text']) ? $attributes['before_text'] : '';
        $list_class = isset($attributes['list_class']) ? $attributes['list_class'] : '';

        // number used for the bootstrap span class
        $bootstrap_span_columns = floor(12/$amount_columns);

        // calculate the amount of elements per column
        $amounts = amount_per_column($data, $amount_columns);

        $columns = "";
        $list_item_number = 0;

        foreach($amounts as $column_number => $amount)
        {
            $column = <<<COLUMN
                    <div class="span$bootstrap_span_columns">
                        <$list_type class="$list_class">
                            %LISTITEMS%
                        </$list_type>
                    </div>
COLUMN;
            $list_items = "";
            for($i=0; $i<$amount; $i++)
            {
                $list_item_number++;
                $list_element = array_shift($data);

                if(! is_object($list_element))
                    $list_items .= '<li value="'. $list_item_number .'">'. $list_element .'</li>';
                else
                {
                    // href and label must be defined when generating the html later
                    $href = isset($list_element->href) ? $list_element->href : "#";
                    $label = isset($list_element->label) ? $list_element->label : "";

                    // generate a string with all the attributes
                    $link_attr = array();
                    foreach(get_object_vars($list_element) as $key => $content)
                    {
                        // the label is already processed
                        if($key != "label")
                        {
                            if(is_string($content))
                                $link_attr[] = $key.'="'. $content .'"';

                            if(is_array($content) && $key == "data")
                            {
                                foreach($content as $k => $d)
                                {
                                    $link_attr[] = 'data-'.$k.'="'. $d .'"';
                                }
                            }
                        }
                    }

                    $link_attr_str = join(' ', $link_attr);

                    $list_items .= <<<LI
                            <li value="$list_item_number">
                                <a $link_attr_str>
                                   $label
                                </a>
                            </li>
LI;
                }
            }

            $column = str_replace('%LISTITEMS%', $list_items, $column);
            $columns .= $column;
        }

        $html = <<<HTML
                    <div class="row $row_class" id="$row_id">
                        <div class="span12">
                            $before_text
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        $columns
                    </div>
HTML;

        return $html;
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