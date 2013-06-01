<?php

//********************************************************************
// do not edit this section

if(!defined("APPSDIR"))
    die("Direct access is not allowed!");

$app_dir = realpath(dirname(__FILE__));
// remove the full path of the document root
$app_dir = str_replace(ROOTDIR, "", $app_dir);

$page->setActivePage(basename($app_dir));

//********************************************************************

$page->addStylesheet("$app_dir/css/screen.css");
$page->addStylesheet("$app_dir/css/style.css");

// TODO: is jQuery UI still needed? This was used to show a message box
//       when a wrong URL format was provided in the 'add hackerspace field'
//$page->addScript("$app_dir/lib/jquery-ui/jquery-ui-1.8.19.custom.min.js");
//$page->addStylesheet("$app_dir/lib/jquery-ui/flick-derivate/jquery-ui-1.8.19.custom.css");
/*
 * In 'Interaction states' the line '.ui-widget :active { outline: none; }' makes the active list element wider
 *
 */

//$page->addStylesheet("$app_dir/lib/jquery-ui/ui-lightness/jquery-ui-1.8.19.custom.css");

 
$page->addScript("$app_dir/lib/jquery-linedtextarea/jquery-linedtextarea.js");
$page->addStylesheet("$app_dir/lib/jquery-linedtextarea/jquery-linedtextarea.css");

$scripts = array(
    "openspace.js",
    "jsl.parser.js",
    "jsl.format.js",
    "jsl.interactions.js",
    "reset.js",
    "recaptcha_ajax.js",
    /*"add-hackerspace.js"*/
);

foreach($scripts as $script)
    $page->addScript("$app_dir/scripts/$script");

unset($scripts);

$html = <<<HTML
        
    <div class="row">
          <div class="span12">
            <div id="validator-container">
                    <div>
                        <div class="styled-select">
                            <select id="spacedirectory"></select>
                        </div>
                        <span id="space-url"></span>
                    </div>
                
                    <br>
                    
                    <div>
                        <form id="JSONValidate" method="post" action=".">
                            <input type="hidden" id="reformat" value="1" />
                            <input type="hidden" id="compress" value="0" />
                            <div>
                              <textarea id="json_input" class="json_input" rows="30" cols="100" spellcheck="false" placeholder="Enter the space status JSON or the URL providing it to validate."></textarea>
                            </div>
                            
                            <div class="row validateButtons clear">
                              <div class="span2">
                                  <button id="validate" value="Validate" class="button left bold" onclick="">Validate</button>
                              </div>
                              <div class="span10" style="text-align: right;">
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
                        
                                            
                        <div id="results-specs-container">
                        </div>
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
            </div> <!-- end span12 -->
        </div> <!-- end row -->
HTML;

$page->addContent($html);