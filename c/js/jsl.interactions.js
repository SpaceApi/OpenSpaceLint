/*jslint white: true, devel: true, onevar: true, browser: true, undef: true, nomen: true, regexp: true, plusplus: false, bitwise: true, newcap: true, maxerr: 50, indent: 4 */
var jsl = typeof jsl === 'undefined' ? {} : jsl;

/**
 * Helper Function for Caret positioning
 * Gratefully borrowed from the Masked Input Plugin by Josh Bush
 * http://digitalbush.com/projects/masked-input-plugin
**/
$.fn.caret = function (begin, end) { 
    if (this.length === 0) {
        return;
    }
    if (typeof begin === 'number') {
        end = (typeof end === 'number') ? end : begin;  
        return this.each(function () {
            if (this.setSelectionRange) {
                this.focus();
                this.setSelectionRange(begin, end);
            } else if (this.createTextRange) {
                var range = this.createTextRange();
                range.collapse(true);
                range.moveEnd('character', end);
                range.moveStart('character', begin);
                range.select();
            }
        });
    } else {
        if (this[0].setSelectionRange) {
            begin = this[0].selectionStart;
            end   = this[0].selectionEnd;
        } else if (document.selection && document.selection.createRange) {
            var range = document.selection.createRange();
            begin = -range.duplicate().moveStart('character', -100000);
            end   = begin + range.text.length;
        }
        return {"begin": begin, "end": end};
    }       
};


/**
 * jsl.interactions - provides support for interactions within JSON Lint.
 *
**/
jsl.interactions = (function () {
    var reformatParam,
        reformat,
        compress;


    /******* UTILITY METHODS *******/

    /**
     * Get the Nth position of a character in a string
     * @searchStr the string to search through
     * @char the character to find
     * @pos int the nth character to find, 1 based.
     *
     * @return int the position of the character found
    **/
    function getNthPos(searchStr, char, pos) {
        var i,
            charCount = 0,
            strArr = searchStr.split(char);

        if (pos === 0) {
            return 0;
        }

        for (i = 0; i < pos; i++) {
            if (i >= strArr.length) {
                return -1;
            }

            // +1 because we split out some characters
            charCount += strArr[i].length + char.length;
        }

        return charCount;
    }

    /**
     * Get a URL parameter from the current windows URL.
     * Courtesy Paul Oppenheim: http://stackoverflow.com/questions/1403888/get-url-parameter-with-jquery
     * @param name the parameter to retrieve
     * @return string the url parameter's value, if any
    **/
    function getURLParameter(name) {
        param = (new RegExp(name + '=' + '(.+?)(&|$)').exec(location.search) || ['', null])[1];
        if (param) {
            return decodeURIComponent(param);
        } else {
            return null;
        }
    }

    /******* INTERACTION METHODS *******/

    /**
     * Validate the JSON we've been given, displaying an error or success message.
     * @return void
    **/
    function validate() {
        var lineNum,
            lineMatches,
            lineStart,
            lineEnd,
            jsonVal,
            result;
            
        jsonVal = $('#json_input').val();

        try {
            result = jsl.parser.parse(jsonVal);

            if (result) {
                $('#results').removeClass('error').addClass('success');
                $('div.linedwrap').removeClass('redBorder').addClass('greenBorder');
                $('#results').text('Valid JSON');


                /********************************************************************************/
                // check against the specs				
				
				// remove any previous spaceapi validation results
				$("#results-specs-container .error").remove();
				$("#results-specs-container .success").remove();
				
				var spaceapi = JSON.parse(jsonVal);
				var api = 0;
				
				if(spaceapi.hasOwnProperty("api"))
				{
					api = parseFloat(spaceapi.api);
				}
				
				// we need to unset some values because if some special characters are used
				// they won't be urlencoded correctly with escape, maybe not all variables
				// are covered so some space api implementation still might fail
				// See http://github.com/slopjong/openspacelint/issues/50
				if(api != 0)
				{
					if(spaceapi.hasOwnProperty("space"))
						spaceapi.space = "";
					
					if(api<0.13)
					{
						if(spaceapi.hasOwnProperty("address"))
							spaceapi.address = "";
						if(spaceapi.hasOwnProperty("state"))
							spaceapi.state = "";
					}
					else
					{
						if(spaceapi.hasOwnProperty("location"))
							if(spaceapi.location.hasOwnProperty("address"))
								spaceapi.location.address = "";
						if(spaceapi.hasOwnProperty("state"))
							if(spaceapi.state.hasOwnProperty("message"))
								spaceapi.state.message = "";
					}
				}
				
				// uglify the json to remove unwanted whitespaces
				var uglifiedJSON = JSON.stringify(spaceapi);
				

				$.getJSON( site_url + "/validate/" + escape(uglifiedJSON))
				// see issue #50
				//$.getJSON( site_url + "/validate/?json=" + encodeURIComponent(uglifiedJSON))
				.success(function(results){ 
					
					if(results.hasOwnProperty("error"))
					{
						alert(results.error + ' If you think that you provided a valid json it could be that you use special characters that are not correctly escaped by javascript. See issue https://github.com/slopjong/OpenSpaceLint/issues/50.');
					}
					else
					{
						if(results.valid.length>0)
						{
							var success_div = $('<div>'+
							'<div><h1>Your JSON is compliant to the specs '+ results.valid.join(", ") +'</h1></div>' +
							'</div>').addClass("success");
							
							$("#results-specs-container").append(success_div).show();
						}
						
			            $.each(results.errors, function(version, item) {
							var error_msg = "";
							for(var i=0; i < item.length; i++)
							{
								error_msg += item[i].msg + "\n";
							}
							
							var error_div = $('<div class=".spec-result">'+
							'<div><h1>Your JSON is not compliant to the specs '+ version +'</h1></div>' +
							'<br><pre>'+ error_msg +'</pre><br>' +
							'Please check the <a style="text-decoration: underline;" href="'+ site_url +'/specs/'+ version +'" target="_blank">specs '+ version +'</a>. ' +
							((version == results.draft) ? 'This is still a draft, see the <a style="text-decoration: underline;" href="http://openspace.slopjong.de/specs/changelog" target="_blank">changelog</a>.' : "") +
							'</div>').addClass("error");
							
							$("#results-specs-container").append(error_div).show();
							
						});
						
						var valid_string = "" + JSON.stringify(results.valid);
						
					}
				})
				.error(function(){
					alert("There's a problem with AJAX. One reason can be an adblock add-on in your browser that's blocking the request. Disable your adblock add-on when validating your json. If you're sure that this is not your issue please file a ticket here: https://github.com/slopjong/OpenSpaceLint/issues");
				});
				
                /********************************************************************************/
                    

                if (reformat) {
                    $('#json_input').val(JSON.stringify(JSON.parse(jsonVal), null, "    "));
                }

                if (compress) {
                    $('#json_input').val(JSON.stringify(JSON.parse(jsonVal), null, ""));
                }
            } else {
                alert("An unknown error occurred. Please contact Slopjong.");
            }
        } catch (parseException) {

            /** 
             * If we failed to validate, run our manual formatter and then re-validate so that we
             * can get a better line number. On a successful validate, we don't want to run our
             * manual formatter because the automatic one is faster and probably more reliable.
            **/
            try {
                if (reformat) {
                    jsonVal = jsl.format.formatJson($('#json_input').val());
                    $('#json_input').val(jsonVal);
                    result = jsl.parser.parse($('#json_input').val());
                }
            } catch(e) {
                parseException = e;
            }

            lineMatches = parseException.message.match(/line ([0-9]*)/);
            if (lineMatches && typeof lineMatches === "object" && lineMatches.length > 1) {
                lineNum = parseInt(lineMatches[1], 10);

                if (lineNum === 1) {
                    lineStart = 0;
                } else {
                    lineStart = getNthPos(jsonVal, "\n", lineNum - 1);
                }

                lineEnd = jsonVal.indexOf("\n", lineStart);
                if (lineEnd < 0) {
                    lineEnd = jsonVal.length;
                }

                $('#json_input').focus().caret(lineStart, lineEnd);
            }

            $('#results').text(parseException.message);
            $('#results').removeClass('success').addClass('error');
            $('div.linedwrap').removeClass('greenBorder').addClass('redBorder');
        }

        $('#loadSpinner').hide();
                
        return false;
    }

    /**
     * Initialize variables, add event listeners, etc.
     *
     * @return void
    **/
    function init() {
		 
        reformatParam = getURLParameter('reformat');
        reformat      = reformatParam !== '0' && reformatParam !== 'no';
        compress      = reformatParam === 'compress',
        jsonParam     = getURLParameter('json');
        
        if (compress) {
            $('#headerText').html('OpenSpace<span class="light">Compressor</span>');
        }

        if (!reformat) {
            $('#headerText').html('OpenSpace<span class="light">Lite</span>');
        }
	
        $('#validate').click(function () {
			
            $('#results_header, #loadSpinner').show();
			reset_results();
			
            var jsonVal = $.trim($('#json_input').val());

            if (jsonVal.substring(0, 4).toLowerCase() === "http") {
                $.post("proxy.php", {"url": jsonVal}, function (responseObj) {
                    $('#json_input').val(responseObj.content);
                    validate();
                }, 'json');
            } else {
                validate();
            }

            return false;
        });

        $('#json_input').keyup(function () {
            $('div.linedwrap').removeClass('greenBorder').removeClass('redBorder');
        }).linedtextarea({
            selectedClass: 'lineselect'
        }).focus();
				
        $('#reset').click(function () {
            $('#json_input').val('').focus();
        });
		
        $('#faqButton').click(function () {
            $('#faq').slideToggle();
        });

        $('#propsButton').click(function () {
            $('#props').slideToggle();
        });
	
        if (jsonParam) {
            $('#json_input').val(jsonParam);
            $('#validate').click();
        }		
    }

    return {
        'init': init
    };
}());


$(function () {
    jsl.interactions.init();    
});