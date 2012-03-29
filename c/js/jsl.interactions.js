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
                
		var versions = ["8", "9", "11", "12"];
                var report, uri, version;
		
                for(index=0; index < versions.length; index++)
                {
			version = versions[index];
			
			var schema = apienv.findSchema("http://openspace.slopjong.de/specs"+ version);
			report = apienv.validate( JSON.parse(jsonVal), schema);
			uri = report.instance._uri;

			// reset the old messages
			$("#results-specs-"+ version).text("");
                    
			$("#results-specs-container-"+ version).show();

			if(report.errors.length > 0)
			{
                        
				$("#results-specs-header-"+ version).text("Your JSON is not compliant to the specs 0."+ version +" .").wrapInner("<h1>");
				$("#results-specs-container-"+ version).removeClass('success').addClass('error');
				
				// remove the old results    
				$("#results-specs-"+ version).text(""); 
				
				var err, msg;
				for(i=0; i<report.errors.length; i++){

					err = report.errors[i];
					msg = err.message;

					if(err.message === "Instance is not one of the possible values")
						msg = "Property '" + err.uri + "' must be one of the these values: " + err.details.join(", ") + ".";

					if(err.message === "Property is required")
						msg = "Property '" + err.uri + "' is missing.";

					if(err.message === "Instance is not a required type")
						msg = "Property '" + err.uri + "' must be one of these types: " + err.details.join(", ") + ".";

					msg = msg.replace(uri+"/", "");
					$("#results-specs-"+ version).append(msg + "\n");           
				}
			}
			else
			{
				$("#results-specs-header-"+ version).text("Your JSON is compliant to the specs version 0."+ version +" .").wrapInner("<h1>");
				$("#results-specs-container-"+ version).removeClass('error').addClass('success');
			}
                }

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
            
            $('#results-specs-container-0.12').hide();
            $('#results-specs-container-0.11').hide();
            $('#results-specs-container-0.9').hide();
            $('#results-specs-container-0.8').hide();
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
            $('#headerText').html('JSONLint<span class="light">Compressor</span>');
        }

        if (!reformat) {
            $('#headerText').html('JSONLint<span class="light">Lite</span>');
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