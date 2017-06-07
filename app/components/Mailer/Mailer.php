<?php

namespace Mailer;

class Mailer
{


  /**
   * Mailer
   *
   * @param string $credentials   Mailer credentials
   * @param string $name          Name of the user for whom the message refers
   * @param string $message       Message to send
   * @return string               A success message or an error message
   **/

  public static function mail($credentials, $name, $message)
  {

    $headers = "Sender: {$credentials['from']}\r\n" .
               "From: {$credentials['fromName']} <{$credentials['from']}>\r\n" .
               "Reply-To: {$credentials['fromName']} <{$credentials['from']}>\r\n" .
               "CC: {$credentials['fromName']} <{$credentials['from']}>\r\n" .
               "X-Mailer: PHP/". phpversion();
    try {
      mail($credentials['to'], $credentials['subjgt'] . ": {$name}", $message, $headers);
      $status = "Success";
    } catch (Exception $e) {
      $status = $e;
    }
    return $status;
  }
}