<?php
include_once __DIR__ . "/Mailchimp.php";
use \DrewM\MailChimp\MailChimp;

$emailInput = filter_input( INPUT_GET, "email", FILTER_SANITIZE_EMAIL );
$qa = filter_input( INPUT_GET, "qa", FILTER_SANITIZE_NUMBER_INT );
$credentials = parse_ini_file(__DIR__ . "/../data.ini", true);
$credentialsMC = ($qa == 1) ? $credentials["mailchimpqa"] : $credentials["mailchimp"];


$MailChimp = new MailChimp($credentialsMC["api_key"]);

$subscriber_hash = $MailChimp->subscriberHash($emailInput);

/**
 *
 * $result = $MailChimp->patch("lists/{$credentialsMC["list_id"]}/members/$subscriber_hash", [
 *   "merge_fields" => [
 *     "PREFEVENTS" => "^Receptions^,^Retreats^,^Reunions^,^Travel Program^"
 *   ]
 * ]);
 *
**/

 $result = $MailChimp->get("lists/{$credentialsMC["list_id"]}/members/$subscriber_hash");

if ($MailChimp->success()) {
  print_r($result);
} else {
  echo $MailChimp->getLastError();
}