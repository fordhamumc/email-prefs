<?php
/**
 * Mailer
 *
 * @param string $name          Name of the user for whom the message refers
 * @param string $message       Message to send
 * @return string               A success message or an error message
 **/

function mailer($name, $message) {
  $credentials = parse_ini_file(__DIR__ . "/../data.ini", true);
  $credentialsMailer = ($_SESSION["qa"] == 1) ? $credentials['mailerqa'] : $credentials['mailer'];

  $headers = "Sender: {$credentialsMailer['from']}
From: {$credentialsMailer['fromName']} <{$credentialsMailer['from']}>
Reply-To: {$credentialsMailer['fromName']} <{$credentialsMailer['from']}>
CC: {$credentialsMailer['fromName']} <{$credentialsMailer['from']}>";

  try {
    mail($credentialsMailer['to'], $credentialsMailer['subject'] . ": $name", $message, $headers);
    $status = "Success";
  }
  catch (Exception $e) {
    $status = "Mailer Failed:\n\n{$e}";
  }
  return $status;
}