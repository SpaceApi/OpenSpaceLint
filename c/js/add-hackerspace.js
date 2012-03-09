$(document).ready(function(){	

    function addOnShowHandler(){
        /* if the DIVs that this handler is bounded to are hidden, the script becomes unresponsive */
        $.fn.extend({ 
          onShow: function(callback, unbind){
            return this.each(function(){
              var obj = this;
              var bindopt = (unbind==undefined)?true:unbind; 
              if($.isFunction(callback)){
                if($(this).is(':hidden')){
                  var checkVis = function(){
                    if($(obj).is(':visible')){
                      callback.call();
                      if(bindopt){
                        $('body').unbind('click keyup keydown', checkVis);
                      }
                    }                         
                  }
                  $('body').bind('click keyup keydown', checkVis);
                }
                else{
                  callback.call();
                }
              }
            });
          }
        });
    }
    
    jQuery(".valid-overlay").overlay({
        mask: {
            color: '#ebecff',
            loadSpeed: 200,
            opacity: 0.9,
            closeOnClick: false
        },
        closeOnClick: false
    });

    jQuery(".invalid-overlay").overlay({
        mask: {
            color: '#ebecff',
            loadSpeed: 200,
            opacity: 0.9
        },
        closeOnClick: false
    });			
    
    $.router(/^add=(.+)$/, function(m, url) {

            addOnShowHandler();
            // execute some code after the results are shown		    
            $("#results-specs-header-12").onShow(function(){
                
                // wait 1.5 seconds until the browser rendered the DIVs
                setTimeout(function(){
                    var valid = $('div:contains("Your JSON is compliant")').size();
                    if(valid) {
                        jQuery(".valid-overlay").data("overlay").load();
                        Recaptcha.focus_response_field();
                    } else {
                        jQuery(".invalid-overlay").data("overlay").load();
                    }
                }, 1500);
            
            }, true);
            
            $("#json_input").val(url);
            $("#validate").click();
        
        }, function(m, url) {}
    );
    
    Recaptcha.create(
        "6LdMsM4SAAAAAItMjpij0bI7j_2wIqlGlehNBlL3",
        "recaptcha_div",
        {
            lang: "en",
            theme: "clean",
            callback: Recaptcha.focus_response_field
        });
    
    $('#add-space-form').submit(function() {
        $.getJSON(
            "http://openspace.slopjong.de/add-hackerspace",
            $(this).serialize(),
            function(response){
                //alert("success");
                console.log(response);
                
                if(response.ok){
                    $("#add-space-form-error").text("").hide();
                    jQuery(".valid-overlay").data("overlay").close();				
                }
                else{
                    $("#add-space-form-error")
                        .text("Your captcha was wrong, please retry!")
                        .show();
                    Recaptcha.reload();
                }
            }
        );
        
        return false;
    });		
});