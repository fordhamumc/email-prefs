<?php
define("IMC_DIR", __DIR__."/imc_connector");
$postdata = file_get_contents("php://input");
$options = json_decode(file_get_contents(IMC_DIR."/prefOptions.json"), TRUE);

function check_for_none($haystack, $needle, $default = "None") {
    $needle = urlencode($needle);
    if (strpos($haystack, "{$needle}=") === false) {
        $haystack .= "&{$needle}={$default}";
    }
    return $haystack;
}

$postdata = check_for_none($postdata, "Fordham Opt Out");
foreach($options as $option) {
    $postdata = check_for_none($postdata, $option["name"]);
}

$curl_connection = curl_init("https://www.pages02.net/fordham-sugartest/Email_Preferences/Form");

curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($curl_connection, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);

//set data to be posted
curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $postdata);

//perform our request
$result = curl_exec($curl_connection);

//show information regarding the request
print_r(curl_getinfo($curl_connection));
echo curl_errno($curl_connection) . '-' .
    curl_error($curl_connection);

//close the connection
curl_close($curl_connection);
