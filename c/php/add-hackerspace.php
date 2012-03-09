<?php
header('Content-type: application/json');

require_once('recaptchalib.php');

$publickey = "6LdMsM4SAAAAAItMjpij0bI7j_2wIqlGlehNBlL3";
$privatekey = "";

if(isset($_GET["recaptcha_response_field"])) {
        $resp = recaptcha_check_answer (
                $privatekey,
                $_SERVER["REMOTE_ADDR"],
                stripslashes(strip_tags($_GET["recaptcha_challenge_field"])),
                stripslashes(strip_tags($_GET["recaptcha_response_field"]))
        );
        
        $response = array("ok" => false, "message" => "");
        
        if ($resp->is_valid){
            $response["ok"] = true;
            
            // TODO: filter these variables
            $url = stripslashes(strip_tags($_GET["url"]));
            $space = stripslashes(strip_tags($_GET["space"]));

            if(empty($url) || empty($space)){
                $response["ok"] = false;
                $response["message"] = "Incomplete data were sent to the server.";
            }
            else{
                $file = "spacehandlers/directory.json";
                $dirjson = file_get_contents($file);
                $dirarr = json_decode($dirjson, true);
                
                if(!in_array($url, $dirarr)){
                    $dirarr[$space] = $url;
                    $dirjson = json_encode($dirarr);
                    file_put_contents($file, $dirjson);
                    $response["message"] = "The space got added to the directory0.";
                }
                else
                    $response["message"] = "The space is already in the directory.";                            
            }
        }
        else
            $response["message"] = $resp->error;
        
        //print_r($response);
        echo json_encode($response);        
}
?>