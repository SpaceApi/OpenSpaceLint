<?php
header('Content-type: application/json');

require_once('recaptchalib.php');

$publickey = "6LdMsM4SAAAAAItMjpij0bI7j_2wIqlGlehNBlL3";
$privatekey = "";

if(isset($_GET["recaptcha_response_field"])) {
        $resp = recaptcha_check_answer (
                $privatekey,
                $_SERVER["REMOTE_ADDR"],
                $_GET["recaptcha_challenge_field"],
                $_GET["recaptcha_response_field"]
        );
        
        $response = array("ok" => false, "error" => "");
        
        if ($resp->is_valid)
            $response["ok"] = true;
        else
            $response["error"] = $resp->error;
        
        //print_r($response);
        echo json_encode($response);        
}
?>