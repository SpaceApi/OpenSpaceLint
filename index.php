<?php
	require_once("config/config.php");
?>

<!doctype html> 
<html lang="en"> 
    <head>
        <meta charset="utf-8">
        <meta name="description" content="OpenSpace Lint is a web based validator and reformatter for spaceapi JSON.">
            
        <!-- The first icon is used by browsers that don't support animated icons -->
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico"><link rel="shortcut icon" type="image/x-icon" href="favicon.gif">
        
        <title>
            OpenSpaceLint - The OpenSpace Validator.
        </title>

		<script type="text/javascript" src="config.js"></script>
		
        <script type="text/javascript">
            if (typeof JSON === 'undefined') {
                document.write('<sc' + 'ript type="text/javascript" src="c/js/json2.js"></sc' + 'ript>');
            }
        </script>

        
        <script src="c/js/jquery-1.6.1.min.js" type="text/javascript"></script>
	
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js" type="text/javascript"></script>
	
	<script src="c/js/jquery-ui/jquery-ui-1.8.19.custom.min.js" type="text/javascript"></script>
        <!--<link href="c/js/jquery-ui/ui-lightness/jquery-ui-1.8.19.custom.css" type="text/css" rel="stylesheet">-->
	<link href="c/js/jquery-ui/flick-derivate/jquery-ui-1.8.19.custom.css" type="text/css" rel="stylesheet">
	
        <script src="c/js/jquery-linedtextarea/jquery-linedtextarea.js" type="text/javascript"></script>
        <link href="c/js/jquery-linedtextarea/jquery-linedtextarea.css" type="text/css" rel="stylesheet">
	
	<script src="c/js/jquery.makecolumnlists.js" type="text/javascript"></script>

        <script type="text/javascript" src="c/js/jsv/uri/uri.js"></script>
        <script type="text/javascript" src="c/js/jsv/jsv.js"></script>
        <script type="text/javascript" src="c/js/jsv/json-schema-draft-03.js"></script>

		
        <script type="text/javascript" src="c/js/openspace.js"></script>
        <script type="text/javascript" src="c/js/apienv.js"></script>
        
        	        
        <script type="text/javascript" src="c/js/jsl.parser.js"></script>
        <script type="text/javascript" src="c/js/jsl.format.js"></script>

        <script type="text/javascript" src="c/js/jsl.interactions.js"></script>
        
        <script type="text/javascript" src="c/js/reset.js"></script>        
        
        <script type="text/javascript" src="c/js/jquery.router-0.5.3.js"></script>
        <script type="text/javascript" src="c/js/jquery.tools.min.js"></script>
        
        <script type="text/javascript" src="http://www.google.com/recaptcha/api/js/recaptcha_ajax.js"></script>	
        <script type="text/javascript" src="c/js/add-hackerspace.js"></script> 
                
        <link rel="stylesheet" href="c/css/blueprint/compressed/screen.css" type="text/css" media="screen, projection">
        <!--[if IE]><link rel="stylesheet" href="c/css/blueprint/lib/ie.css" type="text/css" media="screen, projection"><![endif]-->
        <link rel="stylesheet" href="c/css/blueprint/plugins/css-classes/css-classes.css" type="text/css" media="screen, projection">
        <link rel="stylesheet" href="c/css/screen.css" type="text/css" media="screen, projection">
	
            
        <style type="text/css">
            
            /**
             * The popup stuff
             */
            .modal {
                background-color: #ffffff;
                border: 2px solid #333333;
                border-radius: 20px 20px 20px 20px;
                box-shadow: 0 0 150px 200px #eeeeee;
                display: none;
                opacity: 1;
                padding: 25px;
                text-align: left;
                width: 442px;
            }
            .modal h2 {
                /*background: url("c/images/info.png") no-repeat scroll 0 80% transparent;*/
                border-bottom: 1px solid #333333;
                font-size: 20px;
                margin: 0;
                /*padding: 10px 0 10px 45px;*/
            }
            
            /**
             * Misc
             */
            
            #directory-container{
                display: none;
            }
	    
			#directory-container td{
				vertical-align: top;
			}
            
        </style>
        
    </head>
    <body>

        <!-- Put this into c/js/add-hackerspace.js and append them with jQuery to this document -->
        <!-- ---------------------------------------------------------------- -->
        <div class="modal valid-overlay" rel="#valid"></div>
        <div class="modal invalid-overlay" rel="#invalid"></div>
        
        <div class="modal" id="valid">
            <h2>Confirm that you are not a bot</h2>
            
			<!--
				The action URL and the method in this form are not relevant. The data are
				transmitted to the URL defined in the submit event handler in add-hackerspace.js
				by using the GET method. @see $('#add-space-form').submit(function(){...})
			-->
            <form id="add-space-form">
                <p>
                    <div id="add-space-form-error" class="error" style="display: none"></div>
                </p>
                <p>
                    <div id="recaptcha_div"></div>
                </p>
                <p>
                    <button type="submit">Confirm</button>
                    <button class="close">Cancel</button>
                </p>
                <input type="hidden" value="" id="add-hackerspace-url" name="url"/>
                <input type="hidden" value="" id="add-hackerspace-space" name="space"/>
            </form>
        </div>
        
        <div class="modal" id="invalid">
        <h2>Error</h2>
        <p>
            <div class="error">
            Your hackerspce got not added to the directory. Please adapt your JSON to be valid against one specification version at least.
            </div>
        </p>
        <p>
            <button class="close">Ok</button>
        </p>
        </div>
        <!-- ---------------------------------------------------------------- -->
    
        <div class="container">
            <div class="banner clear">
                <div class="left">
                    <h1 id="headerText">
                        OpenSpaceLint
                    </h1>
                    <h3>
                        The <a href="https://hackerspaces.nl/spaceapi/" target="_blank">Space Api</a> Validator
                    </h3>
                </div>
                <div class="right span-12 unimportant" style="text-align: right;">
                    <div>
			<a href="http://hackerspaces.org" id="labHint" target="_blank"><img style="margin-right: 9px;" src="c/images/hackerzone.png" alt="Hacker Zone"></a>
                    </div>
                    <div>
                        A tool made for <a href="http://hackerspaces.org" id="sourceLink">hackerspaces.</a><br>
                    </div>
                </div>
            </div>
            
            <hr>

			<script type="text/javascript">
			
			$(document).ready(function(){
				
				$(".directory-back-button").click(function(){
				$("#directory-container").fadeOut(function(){
					$("#validator-container").fadeIn();
					$("#headerText").text("OpenSpaceLint");
				});
				});
				
				$("#directoryButton").click(function(){
				
				$("#validator-container").fadeOut(function(){
					
					$("#directory-container").fadeIn();
					$("#headerText").text("OpenSpaceDirectory");
					
				});
				});
			});
			
			</script>
	
			<script type="text/javascript">
			$(document).ready(function(){
				
				$.getJSON( site_url + "/filterkeys.json" , function(data){
				
				
				// populate the two tables in the tabs
				//   "My space offers ..."
				//   "Filters supported by ..."
				for(i=0; i<2; i++)
				{
					$.each(data[i], function(index, value){
					var l = "<ol>";
					$.each(value.sort(), function(i, v){
						l = l + "<li>"+ v +"</li>";
					});
					l = l + "</ol>";
					$("<tr><td>"+ index +"</td><td>"+ l +"</td></tr>").appendTo($("#directory-list-"+ i));
					});
				}
				
				// sortObject is defined in openspace.js
				$.each(sortObject(data[1]), function(filter, value){
					$("#filters-overview").append("<li>"+ filter +"</li>");
				});
				
				$('#filters-overview').makeacolumnlists({cols:4,colWidth:185,equalHeight:false,startN:1});
				$('#directory-list-0 ol').makeacolumnlists({cols:4,colWidth:170,equalHeight:false,startN:1});
				$('#directory-list-1 ol').makeacolumnlists({cols:4,colWidth:170,equalHeight:false,startN:1});
				});
				
				$(function() {
				$("#directory-tabs").tabs();
				});
			
			});
			</script>
		    
			<div id="directory-container">
				
				<div>
					<a href="#" class="directory-back-button">Back to the validator</a>
				</div>
					
                <p>
                    
                    <h2>Directory</h2>
                
                    <p>
                        The OpenSpace directory is a JSON containing a list of hackerspaces that have implemented the <a href="https://hackerspaces.nl/spaceapi/" target="_blank">Space API</a>. This directory can be loaded by any web and mobile application by requesting the resource <a href="http://<?= SITE_URL ?>/directory.json" target="_blank"><code>http://<?= SITE_URL ?>/directory.json</code></a>
                    </p>
                    <p>
                        If your space is missing go back to the validator page and click on <em>New Space</em>. Your JSON will be checked against the specification. If there are no errors it'll be added after you confirmed that you're not a bot.
                    </p>
                    <p>
                        The directory also offers a preselection feature for those applications that rely on certain fields that must be present in a Space API JSON. As a showing example a video application is only interested in such JSONs which have the <code>stream</code> field. There are many other use cases where the preselection makes life easier.
                    </p>
                    <p>
                        To use the preselection append <code>?filter=</code> to the directory URL and specify a filter which stands for a JSON field. Nested fields are concatenated with a dot e.g. <code>sensors.barometer.value</code> corresponds to the following nested JSON structure:
                        
                        <pre><code>{
  "sensors":[
    "barometer":[
      "value" : 23
    ]									
  ]
}</code></pre>
                    If you wrote an app for monitoring the temperature you would be interested in all the spaces' JSON providing temperature sensor data. You'd get the preselection with <a href="http://<?= SITE_URL ?>/directory.json?filter=sensors.barometer.value" target="_blank"><code>http://<?= SITE_URL ?>/directory.json?filter=sensors.barometer.value</code></a>
                    </p>
                    <p>
                        The filters can also be combined as shown below.
                    </p>
                
				<p>
                    
                    <h2>Filters</h2>
                    
					<!--
					<p>
						<strong>Note:</strong> The filters list got messed up. The space names are not a filter!
					</p>
					-->
					
                    <div id="directory-tabs">
                        
                        <ul>
                            <li><a href="#directory-tab0">Overview</a></li>
                            <li><a href="#directory-tab1">My space offers ...</a></li>
                            <li><a href="#directory-tab2">Filter supported by ...</a></li>
                        </ul>
                        
                        <div id="directory-tab0">
                            <ol id="filters-overview"></ol>
                        </div>
                        
                        <div id="directory-tab1">
                            <table id="directory-list-0"></table>
                        </div>
                        
                        <div id="directory-tab2">
                            <table id="directory-list-1"></table>
                        </div>
                        
                    </div>
				
				</p>
                
                <p>
                    
                    <h2>Combining Filters</h2>
                    
                    Filters can be combined as shown in the following examples.
                    
                    <p>
                        <h3>Disjunction</h3>
                        
                        Get all the space JSONs which include the <code>contact</code> or <code>feeds</code> field.
                        
                        <pre><code>or( contact , feeds )</code></pre>
                        
                        Try <a href="http://<?= SITE_URL ?>/directory.json?filter=or(contact,feeds)" target="_blank">directory.json?filter=or(contact,feeds)</a>
                    </p>
                
                    <p>
                        <h3>Conjunction</h3>
                        
                        Get all the space JSONs which include the <code>contact.irc</code> and <code>contact.phone</code> field.
                        
                        <pre><code>and( contact.irc , contact.phone )</code></pre>
                        
                        Try <a href="http://<?= SITE_URL ?>/directory.json?filter=and(contact.irc,contact.phone)" target="_blank">directory.json?filter=and(contact.irc,contact.phone)</a>
                    </p>
                
                    <p>
                        <h3>Mixed</h3>
                        
                        <p>
                            Get all the space JSONs which include the fields as follows:
                        </p>
                        
                        <p>
                            <ul>
                                <li><code>contact</code> and <code>feeds</code> and <code>sensors</code></li> or
                                <li><code>contact</code> and <code>feeds</code> and <code>stream</code></li>
                            </ul>
                        </p>
                    
                        <pre><code>and( contact , feeds , or( sensors , stream ) )</code></pre>
                        
                        Try <a href="http://<?= SITE_URL ?>/directory.json?filter=and(contact,feeds,or(sensors,stream))" target="_blank">directory.json?filter=and(contact,feeds,or(sensors,stream))</a>
                    </p>
                    
                </p>
                
					
				<div>
					<a href="#" class="directory-back-button">Back to the validator</a>
				</div>
		
            </div>
	    
            <div id="validator-container">
                <div>
                    <div class="styled-select">
                        <select id="spacedirectory"></select>
                    </div>
                    <span id="space-url">
                    &nbsp; <!-- fix for a margin issue in chrome -->
                    </span>
                </div>
            
                <br>
                
                <div>
                    <form id="JSONValidate" method="post" action=".">
                        <input type="hidden" id="reformat" value="1" />
                        <input type="hidden" id="compress" value="0" />
                        <div>
                        <textarea id="json_input" class="json_input" rows="30" cols="100" spellcheck="false" placeholder="Enter the space status JSON or the URL providing it to validate."></textarea>
                        </div>
                        <div class="validateButtons clear">
                        <div class="left">
                            <button id="validate" value="Validate" class="button left bold" onclick="">Validate</button>
                        </div>
                        <div class="right">
                            <a href="#" id="directoryButton" class="bold">Directory</a>
                            <a href="#" id="faqButton" class="bold">FAQ</a>
                            <a href="#" id="propsButton" class="bold">Props</a>
                        </div>
                        </div>
                    </form>
                </div>
                
                <div id="results_header" class="hide">
                    <h3>
                    Results <img title="Loading..." class="reset" alt="Loading" src="c/images/loadspinner.gif" id="loadSpinner">
                    </h3>
                </div>
                
                <div id="results-container">
                    <pre id="results"></pre>
					<div id="results-specs-container-13" class=".spec-result"><div id="results-specs-header-13"></div><pre id="results-specs-13"></pre><div class="specs-link" style="margin-top: 12px; display:none;">Please check the <a style="text-decoration: underline;" href="http://<?= SITE_URL ?>/specs/0.13" target="_blank">specs 0.13</a>. This is still a draft, see the <a style="text-decoration: underline;" href="http://<?= SITE_URL ?>/specs/changelog" target="_blank">changelog</a>.</div></div>
                    <div id="results-specs-container-12" class=".spec-result"><div id="results-specs-header-12"></div><pre id="results-specs-12"></pre><div class="specs-link" style="margin-top: 12px; display:none;">Please check the <a style="text-decoration: underline;" href="http://<?= SITE_URL ?>/specs/0.12" target="_blank">specs 0.12</a>.</div></div>
                    <div id="results-specs-container-11" class=".spec-result"><div id="results-specs-header-11"></div><pre id="results-specs-11"></pre><div class="specs-link" style="margin-top: 12px; display:none;">Please check the <a style="text-decoration: underline;" href="http://<?= SITE_URL ?>/specs/0.11" target="_blank">specs 0.11</a>.</div></div>
                    <div id="results-specs-container-9" class=".spec-result"><div id="results-specs-header-9"></div><pre id="results-specs-9"></pre><div class="specs-link" style="margin-top: 12px; display:none;">Please check the <a style="text-decoration: underline;" href="http://<?= SITE_URL ?>/specs/0.9" target="_blank">specs 0.9</a>.</div></div>
                    <div id="results-specs-container-8" class=".spec-result"><div id="results-specs-header-8"></div><pre id="results-specs-8"></pre><div class="specs-link" style="margin-top: 12px; display:none;">Please check the <a style="text-decoration: underline;" href="http://<?= SITE_URL ?>/specs/0.8" target="_blank">specs 0.8</a>.</div></div>
                </div>
                        
                <div id="props" class="hide">
                    <h3>
                    Props
                    </h3>
                    <hr>
                    Props to <a href="http://www.umbrae.net/">Chris Dary</a> of <a href="http://jsonlint.com/">jsonlint.com</a>,<br />
                    <a href="http://www.crockford.com/">Douglas Crockford</a> of <a href="http://www.json.org">JSON</a> and <a href="http://www.jslint.com">JS Lint</a> and <br />
                    <a href="http://zaa.ch/">Zach Carter</a>, who provided the <a href="https://github.com/zaach/jsonlint">pure JS implementation of jsonlint</a>. <br/><br/>
                    Props to <a href="http://www.guislip.com.br/" target="_blank">Guilherme Rodrigues Soares</a> of the <a href="http://openclipart.org/detail/145861" target="_blank">hacker zone logo</a>,<br/>
                    <a href="http://winsh.me/" target="_blank">Kjell Winblad</a><sup><a href="http://conwaysgameoflife.appspot.com/" target="_blank">1</a></sup> of the <a href="http://www.favicon.cc/?action=icon_list&user_id=43770" target="_blank">animated</a> and <a href="http://porg.es" target="_blank">George Pollard</a> of the <a href="http://www.favicon.cc/?action=icon_list&user_id=24245" target="_blank">static</a> favicon.
                </div>
                
                <div id="faq" class="hide">
                    <h3>FAQ</h3>
                    
                    <hr>
                    <div id="faqAccordion">
                        <h5>
                            What is OpenSpaceLint?
                        </h5>
                        <div>
                            <p>
                            OpenSpaceLint is a validator and reformatter for <a href="http://www.json.org/">JSON</a> and checks if the <a href="https://hackerspaces.nl/spaceapi/" target="_blank">spaceapi</a> was correctly implemented.
                            </p>
                        </div>
                        <h5>
                            What are some common errors?
                        </h5>
                        <div>
                            <dl>
                            <dt>
                                <code>Expecting 'STRING'</code>
                            </dt>
                            <dd>
                                You probably have an extra comma at the end of your collection. Something like: <code>{ "a": "b"<span class="highlight">,</span> }</code>
                            </dd>
                            <dt>
                                <code>Expecting 'STRING', 'NUMBER', 'NULL', 'TRUE', 'FALSE', '{', '['</code>
                            </dt>
                            <dd>
                                You probably have an extra comma at the end of your list. Something like: <code>[ "a", "b"<span class="highlight">,</span> ]</code>
                                <br />
                                You also may have not enclosed your collection keys in quotes. Proper format for a collection is: <code>{ "key": "value" }</code>
                            </dd>
                            </dl>
                            <p>
                            Be sure to follow <a href="http://www.json.org/">JSON's syntax</a> properly. For example, <strong>always use double quotes, always quotify your keys, and remove all callback functions</strong>.
                            </p>
                        </div>
                        <h5>
                            A friend and I pasted the same JSON in and got different results. Wat do?
                        </h5>
                        <div>
                            <p>
                            If you and your friend are on different systems (Win/Unix), this is possible due to the way windows handles newlines. Essentially, if you have just newline characters (\n) in your JSON and paste it into JSONLint from a windows machine, it can validate it as valid erroneously since Windows may need a carriage return (\r) as well to detect newlines properly.
                            </p>
                            <p>
                            The solution: Either use direct URL input, or make sure your content's newlines match the architecture your system expects!
                            </p>
                        </div>
                    </div>
                </div> <!-- end of faq -->
                
            </div> <!-- validator-container -->
            
            <noscript>
                <style type="text/css">
                    #validator-container {display:none;}
                </style>
                <div style="margin-top: 60px; margin-left: 60px">
                    <img src="c/images/nojavascriptenabled.png" alt="No javascript enabled"/>
                </div>
            </noscript>

        </div><!-- container -->
        
        
    </body>
</html>
