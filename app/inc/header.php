<?php

require_once __DIR__ . "/../components/autoload.php";

session_start();
date_default_timezone_set('America/New_York');

/**
 * Defaults for all pages
 *
 * @param string $qa            Setting that describes whether to use IMC QA or IMC Prod
 **/

$qa = filter_input( INPUT_GET, "qa", FILTER_SANITIZE_NUMBER_INT );
if ($qa !== null || !array_key_exists("qa", $_SESSION)) $_SESSION['qa'] = $qa;

$query = $_GET;
unset($query['qa']);
$query = http_build_query($query,'','&');
$plain = filter_input( INPUT_GET, "plain", FILTER_VALIDATE_BOOLEAN );
if ($plain !== null || !array_key_exists("plain", $_SESSION)) $_SESSION['plain'] = $plain;

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
    <?php if ($_SESSION['plain']): ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway:400,600" rel="stylesheet" type="text/css">
    <?php else: ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet" type="text/css">
    <?php endif ?>
    <link rel="stylesheet" href="css/main.css" type="text/css">
    <?php if ($_SESSION['plain']): ?>
    <style type="text/css">
        body {
            font-family: raleway,"Helvetica Neue",Helvetica,Helvetica,Arial,sans-serif;
        }
        .container {
            padding-left: 0;
            padding-right: 0;
        }
    </style>
    <?php endif ?>
</head>
<body>
<?php if ($_SESSION['qa'] == 1): ?>
<div class="alert"><a href="/?<?php echo $query;?>&qa=0">Currently using the IMC QA database. <span>Switch to IMC Prod.</span></a></div>
    <?php endif ?>
<?php if (!$_SESSION['plain']): ?>
<div class="container logo">
    <a href="http://fordham.edu">
        <img src="img/fordham.png" width="165" alt="Fordham University">
    </a>
</div>
<?php endif ?>