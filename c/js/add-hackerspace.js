
// TODO: some duplicate with openspace.js
function reload_space_list(){
  var $ = jQuery;
  $.getJSON( site_url + "/directory.json", function(directory){
    
    // the select box
    var list = $("#spacedirectory");
    
    // reset the select box
    list
    .find('option')
    .remove()
    .end()
    .append("<option>Choose a known hackerspace</option>")
    ;
    
    // sort the directory
    directory = sortObject(directory); 
   
    // fill the select box with the new directory  
    $.each(directory, function(space, url){        
        list.append('<option value="'+ url +'">'+ space +'</option>');
    });
        
    // select the item with the current space's name
    try{
      var json = JSON.parse($("#json_input").val());
      
      // remove "selected" from any options that might already be selected
      $('#spacedirectory option[selected="selected"]').each(
          function() {
              $(this).removeAttr('selected');
          }
      );
      
      $("option:contains('"+ json.space +"')").attr("selected", "selected");
    } catch(e) {
      // do nothing
    }
    
  });
}

/**
 * Reloads the filter
 */
function refresh_filters_list()
{
  var $ = jQuery;
  
  $.getJSON( site_url + "/filters.json", function(data){

    // remove all the list items from the overview
    // the makeacolumnlists plugin creates columns which are themselves ordered lists
    // which must be removed too
    $('#filters-overview li').remove();
    $('#directory-tab0  .undefined').remove();
    
    $('#directory-list-0 tbody').remove();
    $('#directory-list-1 tbody').remove();
    
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

    Recaptcha.create(
      recaptcha_public_key, // from config.js
      "recaptcha_div",
      {
          lang: "en",
          theme: "clean",
          callback: Recaptcha.focus_response_field
    });
    
   
    $.router(/^add=(.+)$/, function(m, url) {
        
            // now enter the url in the text field and click
            // on the validate button for the user
            // this is in fact not necessary but it gives a better
            // user experience
            $("#json_input").val(url);
            $("#validate").click();
            
            $.getJSON( site_url + "/validate/?url=" + url)
            .success(function(results){ 
                
              if(results.valid.length>0)
              {
                $("#add-hackerspace-url").val(url);
                
                //console.log(Recaptcha);
                
                try
                {
                  // reload the captcha and focus the text field
                  Recaptcha.reload();
                  Recaptcha.focus_response_field();
                }
                catch(e)
                {
                  if(console != null)
                    if(console.hasOwnProperty("log"))
                      console.log("Could not refresch the captcha fields: " + e);
                }
                
                // reset the error message
                $("#add-space-form-error")
                  .text("")
                  .hide();
                  
                // show the overlay
                jQuery(".valid-overlay").data("overlay").load();
              }
              else
                jQuery(".invalid-overlay").data("overlay").load();
              
            })
            .error(function(){
                alert("There's a problem with AJAX. One reason can be an adblock add-on in your browser that's blocking the request. Disable your adblock add-on when validating your json. If you're sure that this is not your issue please file a ticket here: https://github.com/slopjong/OpenSpaceLint/issues");
            });
        }, function(m, url) {}
    );
    
    // on submit
    // add-space-form is the dialog with the captcha field
    // not to confuse with the add space link with its input field
    $('#add-space-form').submit(function() {
        $.getJSON(
            site_url + "/directory.json",
            $(this).serialize(),
            function(response){
                
                if(response.ok){
                    $("#add-space-form-error").text("").hide();
                    jQuery(".valid-overlay").data("overlay").close();
                    reload_space_list();
                    refresh_filters_list();
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
            
        // reset the select box, results and json input
        reset_results_and_json_input();
        $("#spacedirectory")[0].selectedIndex = 0;
        // set the url label text
        $("#space-url").text(url);
        
        //window.location = "http://openspace.slopjong.de/?#add=" + $("#add-space-input").val();
        window.location.href = site_url + "/?#add=" + $("#add-space-input").val();
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