<?php

/**
 * Defaults for all pages
 *
 * @param string $qa            Setting that describes whether to use IMC QA or IMC Prod
 * @param array  $options       An associative array preference options
 * @param array  $credentials   An associative array containing the connection parameters for IMC
 **/

session_start();
date_default_timezone_set('America/New_York');
define("IMC_DIR", __DIR__."/../imc_connector");

$qa = filter_input( INPUT_GET, "qa", FILTER_SANITIZE_NUMBER_INT );
if ($qa !== null || !array_key_exists("qa", $_SESSION)) $_SESSION["qa"] = $qa;

require_once IMC_DIR."/ImcConnector.php";
$options = json_decode(file_get_contents(IMC_DIR."/prefOptions.json"), TRUE);

$credentials = parse_ini_file(IMC_DIR . (($_SESSION["qa"] == 1) ? "/authData-qa.ini" : "/authData.ini"), true);

ImcConnector::getInstance($credentials["imc"]["baseUrl"]);
ImcConnector::getInstance()->authenticateRest(
    $credentials["imc"]["client_id"],
    $credentials["imc"]["client_secret"],
    $credentials["imc"]["refresh_token"]
);



/**
 * Remove qa flag from query string
 *
 * @param string $query         String of query parameters (minus qa parameter)
 **/

$query = $_GET;
unset($query["qa"]);
$query = http_build_query($query,'','&');

?>
<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Set Your Email Preferences</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/4.1.1/normalize.min.css" type="text/css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="css/main.css" type="text/css">
</head>
<body>
<?php if ($_SESSION["qa"]) { ?>
<div class="alert"><a href="/?<?php echo $query;?>&qa=0">Currently using the IMC QA database. <span>Switch to IMC Prod.</span></a></div>
<?php } ?>
<div class="container logo">
    <a href="http://fordham.edu">
        <img src="img/fordham.png" width="165" alt="Fordham University">
    </a>
</div>