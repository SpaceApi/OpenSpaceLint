/**
 * This is the javascript part of the 'add new space' feature. It provides
 * the #add=<url> route and adds a 'New Space' link to the DOM.
 */

function poll(url){
  setTimeout(function(){
    
      var valid = $('h1:contains("Your JSON is compliant")').size();
      var invalid = $('h1:contains("Your JSON is not compliant ")').size();
    
      if(valid) {
                              
          // set the space name and url in the form
          // to be sent to the add-hacker-space php script                 
          var json;
          try{
            var jsonVal = $('#json_input').val();
            json = JSON.parse(jsonVal);
          } catch(e){
            alert("Something went wrong: " + e.message);
            // no extra exception handling is required because
            // JSON should already be checked by json lint
            return;
          }
          if(json.hasOwnProperty("url")){
             // TODO: check if json.url is part of the passed url argument.
             //       This should prevent to override an existent entry
          }
          $("#add-hackerspace-url").val(url);
           
          if(json.hasOwnProperty("space"))
            $("#add-hackerspace-space").val(json.space);
          
        
          // reload the captcha and focus the text field
          Recaptcha.reload();
          Recaptcha.focus_response_field();
          
          // show the overlay
          jQuery(".valid-overlay").data("overlay").load();
          
      } else {
          var total = valid+invalid;
          if(total==4)
            jQuery(".invalid-overlay").data("overlay").load();
          else{
            poll(url);
          }
      }
  }, 200);
}

$(document).ready(function(){	
     
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

            // now enter the url in the text field and click
            // on the validate button for the user
            $("#json_input").val(url);
            $("#validate").click();
            
            // now poll the results
            poll(url);
        
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
    
    // on submit
    // add-space-form is the dialog with the captcha field
    // not to confuse with the add space link with its input field
    $('#add-space-form').submit(function() {
        $.getJSON(
            "http://openspace.slopjong.de/add-hackerspace",
            $(this).serialize(),
            function(response){

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
    
    $(".validateButtons .right").prepend('\
            <span>\
            <div id="add-space-input-box"><input type="url" id="add-space-input" type="text" value="Enter a URL providing a space api JSON."/><a href="#">Add</a><a href="#">Cancel</a></div>\
            <a id="add-space-link" class="bold" href="#">New Space</a>\
            </span>'
    );

    var input = $("#add-space-input");
    
    // initialize validator for a bunch of input fields
    input.validator();
    
    input.click(function(){
      if($(this).val() === "Enter a URL providing a space api JSON.")
      {
        $(this)
          .val("")
          .css("color","#333333");
      }
    });

    input.blur(function(){
      if($(this).val().length == 0){
        $(this)
          .val("Enter a URL providing a space api JSON.")
          .css("color","#888888");
      }
    });
    
    $("#add-space-link").click(function(){
      $("#add-space-input-box").show();
      $(this).hide();
    });

    // the add button
    $("#add-space-input-box a").first().click(function(){
      
      // perform validation programmatically
      $("#add-space-input").data("validator").checkValidity();
      var isUrl = $("#add-space-input").data("validator").checkValidity();
      if(isUrl){
        var url = $("#add-space-input").val();
        if(url.indexOf("http") == -1)
          url = "http://" + url;

        window.location = "http://openspace.slopjong.de/#add=" + $("#add-space-input").val();
        $("#add-space-input-box").hide();
        $("#add-space-link").show();
      }
    })
    
    // the cancel button
    $("#add-space-input-box a").last().click(function(){
      $("#add-space-input-box").hide();
      $("#add-space-link").show();      
    });
    
});