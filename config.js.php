<?php

// in some javascript files we need certain settings from the
// config.php so this script here generates the content for
// config.js to be included before all other javascript files
// which require these settings.

header("Content-type: application/x-javascript");

require("config.php");

echo "site_url = 'http://$site_url';";
echo "recaptcha_public_key = '" . $recaptcha_key["public"] . "';";
