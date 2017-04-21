<?php
/**
 * Mailer
 *
 * @param string $name          The Encoded ID of the recipient to update
 * @param string $newemail      The Email address of the user (only used if $recipientId and $encodedId are blank
 * @param string $id            An associative array of user data returned from IMC
 * @return string               A success message or an error message
 **/

function mailer($name, $newemail, $id) {
  $credentials = parse_ini_file(__DIR__ . "/../data.ini", true);
  $credentialsMailer = $credentials['mailer'];

  $headers = "Sender: {$credentialsMailer['from']}
From: {$credentialsMailer['fromName']} <{$credentialsMailer['from']}>
Reply-To: {$credentialsMailer['fromName']} <{$credentialsMailer['from']}>
CC: {$credentialsMailer['fromName']} <{$credentialsMailer['from']}>";

  $message = "Can you please update the preferred email for the following constituent:
  
{$id}
{$name}
{$newemail}";

  $status = "Not Tried";
  if (filter_var($newemail, FILTER_VALIDATE_EMAIL)) {
    try {
      mail($credentialsMailer['to'], $credentialsMailer['subject'] . ": $name", $message, $headers);
      $status = "Success";
    }
    catch (Exception $e) {
      $status = "Mailer Failed:\n\n{$e}";
    }
  }
  return $status;
}