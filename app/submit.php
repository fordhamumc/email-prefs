<?php
define("IMC_DIR", __DIR__."/imc_connector");
$postdata = file_get_contents("php://input");
$options = json_decode(file_get_contents(IMC_DIR."/prefOptions.json"), TRUE);

function get_field_value($needle, $haystack) {
    if (preg_match("/{$needle}=(.+?)&/", $haystack, $m)) {
        return urldecode($m[1]);
    }
    return false;
}

function check_for_none($needle, $haystack, $default = "None") {
    $needle = urlencode($needle);
    if (strpos($haystack, "{$needle}=") === false) {
        $haystack .= "&{$needle}={$default}";
    }
    return $haystack;
}



/* ==========================================================================
   Set empty fields to 'None'
   ========================================================================== */

$postdata = check_for_none("Fordham Opt Out", $postdata);
foreach($options as $option) {
    $postdata = check_for_none($option["name"], $postdata);
}



/* ==========================================================================
   Update New_email field only if Email has changed
   Email field is updated as well to allow for changes to apply instantly
   in IMC
   ========================================================================== */

$email = get_field_value("Email", $postdata);
$emailNew = get_field_value("EmailNew", $postdata);

if ($email === $emailNew)
    $emailNew = "None";

$postdata .= "&New_email={$emailNew}";



/* ==========================================================================
   Send update to IMC
   ========================================================================== */

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

//success/error messages
if (curl_errno($curl_connection)) {
    $error = "Couldn't send request: " . curl_error($curl_connection);
} else {
    $resultStatus = curl_getinfo($curl_connection, CURLINFO_HTTP_CODE);
    if ($resultStatus != 200) {
        $error = "Request failed: HTTP status code: {$resultStatus}";
    }
}

//close the connection
curl_close($curl_connection);



/* ==========================================================================
   Display error/success message
   ========================================================================== */

$header = (isset($error)) ? "An Error Has Occurred" : "Thank You";
if (isset($error)) {
    $header = "An Error Has Occurred";
    $message = "<p class='error'>{$error}</p>
                <p>To update your preferences, contact <a href='mailto:emailmarketing@fordham.edu'>emailmarketing@fordham.edu</a>.</p>";
} else {
    $header = "Thank You";
    $message = "<p>You have successfully updated your preferences.</p>
                <p>Visit the <a href=\"http://fordham.edu\">Fordham Homepage.</a></p>";
}

include_once "inc/header.php";
?>

<header class="intro container">
    <h1 class="intro-heading"><?php echo $header; ?></h1>
</header>
<div class="container"><?php echo $message; ?></div>
<?php
include_once "inc/footer.php"; ?>
