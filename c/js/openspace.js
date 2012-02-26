function sortObject(o) {
    var sorted = {},
    key, a = [];

    // push the keys to an array
    for (key in o) {
        if (o.hasOwnProperty(key)) {
                a.push(key);
        }
    }

    // sort the array
    a.sort(function(a,b){ 
    
        var compA = a.toLowerCase();
        var compB = b.toLowerCase();
    
        return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
    });

    // create a new object with the elements sorted alphabetically
    for (key = 0; key < a.length; key++) {
        sorted[a[key]] = o[a[key]];
    }
    return sorted;
}


// for testing
/*
$.fn.sort = function sortme(a, b){
    console.log(a);
    console.log(b);
    //console.log("------");
    return 1;//a.innerHTML.toLowerCase() > b.innerHTML.toLowerCase() ? 1 : -1;  
};  
*/

/*
var a, b;

   function sortJsonName(a,b){
     return 1;//a.name > b.name ? 1 : -1;
   };
   
    $(document).ready(function() {
    	//$.getJSON("http://openspace.slopjong.de", function(cats){
      		//directory2 = $(directory2).sort(sortJsonName);
      		
            $.each(directory2, function(index, url){
                console.log(index);
      			//$('#navlist').append('<li>'+cat.name+'</li>');
      
      		});
                                   
        //});
    });
*/

/*
listitems.sort(function(a, b) {
   var compA = $(a).text().toUpperCase();
   var compB = $(b).text().toUpperCase();
   return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
})
//*/

$(document).ready(function(){
    $("#JSONValidate").before('<div ><select id="spacedirectory"></select><span id="space-url"></span></div><br>');
    $("#spacedirectory")
        .append("<option>Choose a known hackerspace</option>")
        .change(function(){
            var selected = $("option:selected",this).first();
            var space = selected.text();
            var url = selected.attr("value");
            
            $("#space-url").text(url);
            
            //console.log(space);
            //console.log(url);
            $("#json_input").val(url);
            $("#validate").click();
        })
        ;

    $.getJSON("http://openspace.slopjong.de/directory.json")
        .success(function(directory){ 
            directory = sortObject(directory); 
            
            $.each(directory, function(space, url){        
                $("#spacedirectory").append('<option value="'+ url +'">'+ space +'</option>');
            });
        })
        .error(function(){ 
            var directory = {
                "RevSpace": "https://revspace.nl/status/status.php",
                "Bitlair": "https://bitlair.nl/statejson.php",
                "TkkrLab": "http://tkkrlab.nl/statejson.php",
                "Frack": "http://frack.nl/spacestate/?api",
                "Fablier": "http://status.fabelier.org/status.json",
                "Syn2cat": "http://www.hackerspace.lu/od/",
                "Tetalab": "http://status.tetalab.org/status.json",
                "ACKspace": "https://ackspace.nl/status.php",
                "Milwaukee Makerspace": "http://apps.2xlnetworks.net/milwaukeemakerspace/",
                "Noisebridge": "http://api.noisebridge.net/spaceapi/",
                "Pumping Station: One": "http://space.pumpingstationone.org:8000/spaceapi/ps1",
                "Void Warranties": "http://we.voidwarranties.be/SpaceAPI/",
                "Makers Local 256": "https://256.makerslocal.org/status.json",
                "HeatSync Labs": "http://intranet.heatsynclabs.org/~access/cgi-bin/spaceapi.rb",
                "Kwartzlab MakerSpace": "http://at.kwartzlab.ca/spaceapi/index.php",
                "MidsouthMakers": "http://midsouthmakers.org/spaceapi/",
                "Hickerspace": "http://hickerspace.org/api/info/",
                "TOG": "http://tog.ie/cgi-bin/space",
                "miLKlabs": "http://status.mlkl.bz/json"
            } // end directory object 
            
            directory = sortObject(directory);
            //console.log(directory);
            
            $.each(directory, function(space, url){        
                $("#spacedirectory").append('<option value="'+ url +'">'+ space +'</option>');
            });
        });
});