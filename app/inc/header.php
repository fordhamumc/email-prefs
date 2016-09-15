<?php

/**
 * Defaults for all pages
 *
 * @param array  $options       An associative array preference options
 * @param array  $credentials   An associative array containing the connection parameters for IMC
 **/

session_start();
date_default_timezone_set('America/New_York');
define("IMC_DIR", __DIR__."/../imc_connector");
require_once IMC_DIR."/ImcConnector.php";
$options = json_decode(file_get_contents(IMC_DIR."/prefOptions.json"), TRUE);
$credentials = parse_ini_file(IMC_DIR."/authData.ini", true);

ImcConnector::getInstance($credentials["imc"]["baseUrl"]);
ImcConnector::getInstance()->authenticateRest(
    $credentials["imc"]["client_id"],
    $credentials["imc"]["client_secret"],
    $credentials["imc"]["refresh_token"]
);

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
<div class="container logo">
    <a href="http://fordham.edu">
        <img src="img/fordham.png" width="165" alt="Fordham University">
    </a>
</div>